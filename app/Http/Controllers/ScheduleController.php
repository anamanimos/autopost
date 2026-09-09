<?php

namespace App\Http\Controllers;

use App\Jobs\PublishScheduleJob;
use App\Models\ProjectCampaign;
use App\Models\Schedule;
use App\Services\MetaGraphService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $projects = ProjectCampaign::orderBy('name')->get();
        $statusFilter = $request->get('status', 'all');
        $projectFilter = $request->get('project_id');

        $query = Schedule::with([
            'projectCampaign.targets.connectedAccount',
            'mediaFile',
            'publishLogs.connectedAccount',
        ])->latest('target_date')->latest('target_time');

        if ($statusFilter !== 'all') {
            if ($statusFilter === 'failed_all') {
                $query->whereIn('status', ['failed', 'partially_failed']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        if ($projectFilter) {
            $query->where('project_campaign_id', $projectFilter);
        }

        $schedules = $query->paginate(30)->withQueryString();

        $stats = [
            'total' => Schedule::count(),
            'pending' => Schedule::where('status', 'pending')->count(),
            'completed' => Schedule::where('status', 'completed')->count(),
            'failed' => Schedule::whereIn('status', ['failed', 'partially_failed'])->count(),
        ];

        return view('schedules.index', compact('schedules', 'projects', 'stats', 'statusFilter', 'projectFilter'));
    }

    public function showLogs($id)
    {
        $schedule = Schedule::with([
            'projectCampaign',
            'publishLogs.connectedAccount',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'schedule' => $schedule,
            'logs' => $schedule->publishLogs,
        ]);
    }

    public function runSingle(Request $request, $id, MetaGraphService $metaService)
    {
        try {
            $schedule = Schedule::findOrFail($id);

            // Jika dipaksa terbitkan ulang atau statusnya sudah completed, reset ke pending
            if ($request->boolean('force_republish', false) || $schedule->status === 'completed') {
                $schedule->update([
                    'status' => 'pending',
                    'notes' => 'Di-reset untuk dipublikasikan ulang pada ' . Carbon::now()->format('d/m/Y H:i'),
                ]);
            }

            (new PublishScheduleJob($schedule))->handle($metaService);

            $schedule->refresh();
            $schedule->load('publishLogs.connectedAccount');

            return response()->json([
                'success' => true,
                'message' => "Jadwal #{$schedule->id} selesai diproses dengan status " . strtoupper($schedule->status) . '.',
                'status' => $schedule->status,
                'notes' => $schedule->notes,
                'logs' => $schedule->publishLogs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menjalankan jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function publishNow(Request $request)
    {
        try {
            $force = $request->boolean('force', false);
            $projectId = $request->input('project_id');

            $options = [];
            if ($force) {
                $options['--force'] = true;
            }
            if ($projectId) {
                $options['--project'] = $projectId;
            }

            $exitCode = Artisan::call('meta:publish', $options);
            $output = Artisan::output();

            return response()->json([
                'success' => $exitCode === 0,
                'message' => 'Antrean posting telah diproses via Meta Graph API.',
                'output' => trim($output),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function retryFailed(Request $request)
    {
        try {
            $failedCount = Schedule::whereIn('status', ['failed', 'partially_failed'])->count();

            if ($failedCount === 0) {
                $msg = 'Tidak ada antrean yang berstatus gagal saat ini.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg]);
                }
                return redirect()->back()->with('info', $msg);
            }

            Schedule::whereIn('status', ['failed', 'partially_failed'])->update([
                'status' => 'pending',
                'notes' => 'Di-reset untuk dicoba ulang (Retry by User pada ' . Carbon::now()->format('d/m/Y H:i') . ')',
            ]);

            $msg = "Berhasil me-reset {$failedCount} jadwal gagal ke status PENDING.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }

            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil dihapus.',
            ]);
        }

        return redirect()->back()->with('success', 'Jadwal berhasil dihapus.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,failed,partially_failed,skipped',
        ]);

        $schedule = Schedule::findOrFail($id);
        $oldStatus = $schedule->status;
        $newStatus = $request->status;

        $notes = $schedule->notes;
        if ($newStatus === 'pending') {
            $notes = 'Status di-reset ke PENDING untuk terbit ulang pada ' . Carbon::now()->format('d/m/Y H:i');
        } elseif ($newStatus === 'skipped') {
            $notes = 'Dilewati secara manual pada ' . Carbon::now()->format('d/m/Y H:i');
        } elseif ($newStatus === 'completed') {
            $notes = 'Ditandai selesai secara manual pada ' . Carbon::now()->format('d/m/Y H:i');
        }

        $schedule->update([
            'status' => $newStatus,
            'notes' => $notes,
        ]);

        $statusLabel = strtoupper($newStatus);
        $dateFormatted = $schedule->target_date ? $schedule->target_date->translatedFormat('d M Y') : 'Jadwal';

        return response()->json([
            'success' => true,
            'message' => "Status jadwal tanggal {$dateFormatted} berhasil diubah menjadi {$statusLabel}.",
            'status' => $schedule->status,
        ]);
    }
}