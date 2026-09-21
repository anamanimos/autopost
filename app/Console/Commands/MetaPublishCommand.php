<?php

namespace App\Console\Commands;

use App\Jobs\PublishScheduleJob;
use App\Models\Schedule;
use App\Services\MetaGraphService;
use App\Services\ThreadsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MetaPublishCommand extends Command
{
    protected $signature = 'meta:publish {--id= : Publish specific schedule ID} {--project= : Filter by Project Campaign ID} {--force : Force execute even if time not reached}';
    protected $description = 'Pemicu eksekusi publish jadwal antrean ke Instagram, Facebook, dan Threads via Meta Graph API';

    public function handle(MetaGraphService $metaService, ?ThreadsService $threadsService = null): int
    {
        $threadsService = $threadsService ?? app(ThreadsService::class);
        $specificId = $this->option('id');
        $projectId = $this->option('project');
        $force = $this->option('force');

        if ($specificId) {
            $schedule = Schedule::find($specificId);
            if (!$schedule) {
                $this->error("Jadwal ID #{$specificId} tidak ditemukan.");
                return Command::FAILURE;
            }

            $this->info("Menjalankan jadwal #{$schedule->id} ({$schedule->item_code})...");
            (new PublishScheduleJob($schedule))->handle($metaService, $threadsService);
            $this->info("Jadwal #{$schedule->id} selesai dieksekusi.");
            return Command::SUCCESS;
        }

        $todayStr = Carbon::today()->format('Y-m-d');
        $nowTimeStr = Carbon::now()->format('H:i');

        $query = Schedule::where('status', 'pending');

        if ($projectId) {
            $query->where('project_campaign_id', $projectId);
        }

        if (!$force) {
            $query->where(function ($q) use ($todayStr, $nowTimeStr) {
                $q->where('target_date', '<', $todayStr)
                  ->orWhere(function ($q2) use ($todayStr, $nowTimeStr) {
                      $q2->where('target_date', $todayStr)
                         ->where('target_time', '<=', $nowTimeStr);
                  });
            });
        }

        $schedules = $query->orderBy('target_date')->orderBy('target_time')->get();

        if ($schedules->isEmpty()) {
            $this->info('Tidak ada jadwal antrean yang perlu dipublish saat ini.');
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$schedules->count()} jadwal siap dipublish.");

        foreach ($schedules as $schedule) {
            $this->info("Mengeksekusi jadwal #{$schedule->id} [{$schedule->target_date} {$schedule->target_time}]...");
            (new PublishScheduleJob($schedule))->handle($metaService, $threadsService);
        }

        $this->info('Seluruh jadwal berhasil diproses.');
        return Command::SUCCESS;
    }
}