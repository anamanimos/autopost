<?php

namespace App\Console\Commands;

use App\Models\ProjectCampaign;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MaintainScheduleBufferCommand extends Command
{
    protected $signature = 'meta:maintain-buffer {--project= : ID Project spesifik}';
    protected $description = 'Menjaga antrean jadwal posting tetap terisi untuk seluruh Project Campaign aktif';

    public function handle(): int
    {
        $projectId = $this->option('project');

        $query = ProjectCampaign::query();
        if ($projectId) {
            $query->where('id', $projectId);
        } else {
            $query->where('status', 'active');
        }

        $projects = $query->with('mediaFiles')->get();

        if ($projects->isEmpty()) {
            $this->info('Tidak ada project yang perlu dimaintain antreannya.');
            return Command::SUCCESS;
        }

        $totalAdded = 0;
        $totalPruned = 0;

        foreach ($projects as $project) {
            $pruned = $this->pruneOrphanedSchedules($project);
            $added = $this->maintainProjectBuffer($project);
            $totalAdded += $added;
            $totalPruned += $pruned;
            $this->line("  ✓ Project '{$project->name}': -{$pruned} jadwal kedaluwarsa dibersihkan, +{$added} jadwal baru ditambahkan.");
        }

        // Jika dipanggil global tanpa spesifik project, bersihkan juga jadwal kedaluwarsa pada project paused
        if (!$projectId) {
            $pausedProjects = ProjectCampaign::where('status', '!=', 'active')->get();
            foreach ($pausedProjects as $pausedProj) {
                $pPruned = $this->pruneOrphanedSchedules($pausedProj);
                if ($pPruned > 0) {
                    $totalPruned += $pPruned;
                    $this->line("  ✓ [PAUSED] Project '{$pausedProj->name}': -{$pPruned} jadwal kedaluwarsa dibersihkan.");
                }
            }
        }

        $this->info("Pemeliharaan antrean jadwal selesai. Total -{$totalPruned} jadwal dibersihkan, +{$totalAdded} jadwal baru ditambahkan.");
        return Command::SUCCESS;
    }

    /**
     * Bersihkan jadwal berstatus pending yang sudah tidak valid:
     * 1. Jadwal setelah end_date (mode until_date)
     * 2. Jadwal sebelum start_date
     * 3. Jadwal pada hari yang dikecualikan (exclude_days)
     * 4. Jadwal berlebih pada mode once (1x post)
     */
    public function pruneOrphanedSchedules(ProjectCampaign $project): int
    {
        $deletedCount = 0;

        // 1. Mode Once: hapus semua pending selain target date
        if ($project->repeat_type === 'once') {
            $targetDate = $project->start_date ? $project->start_date->format('Y-m-d') : Carbon::today()->format('Y-m-d');
            $deletedCount += Schedule::where('project_campaign_id', $project->id)
                ->where('status', 'pending')
                ->where('target_date', '!=', $targetDate)
                ->delete();
            return $deletedCount;
        }

        // 2. Mode until_date dengan end_date: hapus semua pending setelah end_date
        if ($project->repeat_type === 'until_date' && $project->end_date) {
            $endDateStr = ($project->end_date instanceof Carbon)
                ? $project->end_date->format('Y-m-d')
                : Carbon::parse($project->end_date)->format('Y-m-d');

            $deletedCount += Schedule::where('project_campaign_id', $project->id)
                ->where('status', 'pending')
                ->where('target_date', '>', $endDateStr)
                ->delete();
        }

        // 3. Hapus pending sebelum start_date (jika start_date diatur di masa depan)
        if ($project->start_date) {
            $startDateStr = ($project->start_date instanceof Carbon)
                ? $project->start_date->format('Y-m-d')
                : Carbon::parse($project->start_date)->format('Y-m-d');

            $deletedCount += Schedule::where('project_campaign_id', $project->id)
                ->where('status', 'pending')
                ->where('target_date', '<', $startDateStr)
                ->delete();
        }

        // 4. Hapus pending yang jatuh pada hari yang dikecualikan (exclude_days)
        $excludeDays = $project->exclude_days ?? [];
        if (!empty($excludeDays)) {
            $excludeDayInts = array_map('intval', $excludeDays);
            $pendingSchedules = Schedule::where('project_campaign_id', $project->id)
                ->where('status', 'pending')
                ->get();

            foreach ($pendingSchedules as $ps) {
                if ($ps->target_date && in_array((int)$ps->target_date->dayOfWeek, $excludeDayInts)) {
                    $ps->delete();
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    public function maintainProjectBuffer(ProjectCampaign $project): int
    {
        // Jalankan pembersihan jadwal orphaned / lewat batas tanggal terlebih dahulu
        $this->pruneOrphanedSchedules($project);

        if ($project->repeat_type === 'once') {
            return 0; // Mode 1x post tidak memakai rolling buffer
        }

        $mediaFiles = $project->mediaFiles;
        if ($mediaFiles->isEmpty() && empty(trim($project->caption ?? ''))) {
            return 0;
        }

        $excludeDays = $project->exclude_days ?? [];
        $imagesPerPost = max(1, $project->images_per_post ?: 1);
        $addedCount = 0;

        $startDate = Carbon::today();
        if ($project->start_date) {
            $startDate = $project->start_date->copy();
            if ($startDate->isPast() && !$startDate->isToday()) {
                $startDate = Carbon::today();
            }
        }

        // Tentukan jumlah hari yang akan dibuat
        if ($project->repeat_type === 'until_date' && $project->end_date) {
            // Mode sampai tanggal tertentu: Langsung buat seluruh jadwal sampai end_date (tanpa limit 29 hari)
            $endDate = $project->end_date->copy()->startOfDay();
            $limitDays = $startDate->diffInDays($endDate, false) + 1;
            if ($limitDays < 1) {
                return 0;
            }
        } else {
            // Mode continuous: jaga antrean rolling 30 hari ke depan
            $limitDays = 30;
        }

        // Ambil rotasi media terakhir untuk continuity
        $lastSchedule = Schedule::where('project_campaign_id', $project->id)->latest('id')->first();
        $mediaIndex = 0;
        if ($lastSchedule && $lastSchedule->media_file_id && $mediaFiles->isNotEmpty()) {
            $lastMediaId = $lastSchedule->media_file_id;
            $foundIdx = $mediaFiles->search(fn($item) => $item->id == $lastMediaId);
            if ($foundIdx !== false) {
                $mediaIndex = ($foundIdx + 1) % $mediaFiles->count();
            }
        }

        for ($i = 0; $i < $limitDays; $i++) {
            $currentDate = $startDate->copy()->addDays($i);

            // Jika mode until_date dan sudah melewati end_date
            if ($project->repeat_type === 'until_date' && $project->end_date && $currentDate->gt($project->end_date)) {
                break;
            }

            // Exclude Days
            if (in_array($currentDate->dayOfWeek, $excludeDays)) {
                continue;
            }

            $dateStr = $currentDate->format('Y-m-d');

            // Jika hari ini dan jam posting sudah terlewat, jangan buat jadwal mundur
            if ($currentDate->isToday()) {
                $targetDateTime = Carbon::parse($dateStr . ' ' . $project->target_time);
                if ($targetDateTime->isPast()) {
                    continue;
                }
            }

            // Cek apakah jadwal sudah ada pada tanggal ini
            $exists = Schedule::where('project_campaign_id', $project->id)
                ->where('target_date', $dateStr)
                ->exists();

            if ($exists) {
                continue;
            }

            // Rotasi Media Pool
            $paths = [];
            $primaryMediaFile = null;

            if ($mediaFiles->isNotEmpty()) {
                for ($imgIdx = 0; $imgIdx < $imagesPerPost; $imgIdx++) {
                    $pickedMedia = $mediaFiles[$mediaIndex % $mediaFiles->count()];
                    if ($imgIdx === 0) {
                        $primaryMediaFile = $pickedMedia;
                    }
                    $paths[] = $pickedMedia->file_path;
                    $mediaIndex++;
                }
            }

            $primaryPath = $paths[0] ?? null;
            $itemCode = 'proj_' . $project->id . '_' . $dateStr . '_' . rand(100, 999);

            Schedule::create([
                'project_campaign_id' => $project->id,
                'item_code' => $itemCode,
                'media_file_id' => $primaryMediaFile?->id,
                'media_path' => $primaryPath,
                'media_paths' => $paths,
                'target_date' => $dateStr,
                'target_time' => $project->target_time,
                'status' => 'pending',
                'notes' => "Project '{$project->name}' (" . strtoupper($project->repeat_type) . ")",
            ]);

            $addedCount++;
        }

        return $addedCount;
    }
}