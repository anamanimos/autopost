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

        $query = ProjectCampaign::where('status', 'active');
        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->with('mediaFiles')->get();

        if ($projects->isEmpty()) {
            $this->info('Tidak ada project aktif yang perlu dimaintain antreannya.');
            return Command::SUCCESS;
        }

        $totalAdded = 0;

        foreach ($projects as $project) {
            $added = $this->maintainProjectBuffer($project);
            $totalAdded += $added;
            $this->line("  ✓ Project '{$project->name}': +{$added} jadwal baru ditambahkan.");
        }

        $this->info("Pemeliharaan antrean jadwal selesai. Total {$totalAdded} jadwal baru ditambahkan.");
        return Command::SUCCESS;
    }

    public function maintainProjectBuffer(ProjectCampaign $project): int
    {
        if ($project->repeat_type === 'once') {
            return 0; // Mode 1x post tidak memakai rolling buffer
        }

        $mediaFiles = $project->mediaFiles;
        if ($mediaFiles->isEmpty()) {
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
        if ($lastSchedule && $lastSchedule->media_file_id) {
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

            for ($imgIdx = 0; $imgIdx < $imagesPerPost; $imgIdx++) {
                $pickedMedia = $mediaFiles[$mediaIndex % $mediaFiles->count()];
                if ($imgIdx === 0) {
                    $primaryMediaFile = $pickedMedia;
                }
                $paths[] = $pickedMedia->file_path;
                $mediaIndex++;
            }

            $primaryPath = $paths[0] ?? '';
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