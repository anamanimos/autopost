<?php

namespace App\Http\Controllers;

use App\Jobs\PublishScheduleJob;
use App\Models\CampaignTarget;
use App\Models\ConnectedAccount;
use App\Models\MediaFile;
use App\Models\ProjectCampaign;
use App\Models\Schedule;
use App\Services\MetaGraphService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $projects = ProjectCampaign::orderBy('name')->get();
        $accounts = ConnectedAccount::where('is_active', true)->orderBy('page_name')->get();
        $statusFilter = $request->get('status', 'all');
        $projectFilter = $request->get('project_id');

        $sort = $request->get('sort', 'date');
        $direction = strtolower($request->get('direction', ''));

        $allowedSorts = ['date', 'campaign', 'status', 'id'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'date';
        }

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = match($sort) {
                'campaign', 'status' => 'asc',
                default => 'desc',
            };
        }

        $query = Schedule::with([
            'projectCampaign.targets.connectedAccount',
            'mediaFile',
            'publishLogs.connectedAccount',
        ]);

        if ($statusFilter !== 'all') {
            if ($statusFilter === 'failed_all') {
                $query->whereIn('schedules.status', ['failed', 'partially_failed']);
            } else {
                $query->where('schedules.status', $statusFilter);
            }
        }

        if ($projectFilter) {
            $query->where('schedules.project_campaign_id', $projectFilter);
        }

        if ($sort === 'campaign') {
            $query->select('schedules.*')
                ->leftJoin('project_campaigns', 'schedules.project_campaign_id', '=', 'project_campaigns.id')
                ->orderBy('project_campaigns.name', $direction)
                ->orderBy('schedules.target_date', 'asc');
        } elseif ($sort === 'status') {
            $query->orderBy('schedules.status', $direction)
                ->orderBy('schedules.target_date', 'desc');
        } elseif ($sort === 'id') {
            $query->orderBy('schedules.id', $direction);
        } else {
            // default 'date'
            $query->orderBy('schedules.target_date', $direction)
                ->orderBy('schedules.target_time', $direction)
                ->orderBy('schedules.id', $direction);
        }

        $schedules = $query->paginate(30)->withQueryString();

        $stats = [
            'total' => Schedule::count(),
            'pending' => Schedule::where('status', 'pending')->count(),
            'completed' => Schedule::where('status', 'completed')->count(),
            'failed' => Schedule::whereIn('status', ['failed', 'partially_failed'])->count(),
        ];

        $currentSort = $sort;
        $currentDirection = $direction;

        $recentMedia = MediaFile::latest()->take(40)->get();

        return view('schedules.index', compact(
            'schedules',
            'projects',
            'accounts',
            'recentMedia',
            'stats',
            'statusFilter',
            'projectFilter',
            'currentSort',
            'currentDirection'
        ));
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

    public function runSingle(Request $request, $id, MetaGraphService $metaService, ?\App\Services\ThreadsService $threadsService = null)
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

            (new PublishScheduleJob($schedule))->handle($metaService, $threadsService ?? app(\App\Services\ThreadsService::class));

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

    public function calendarEvents(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $start = Carbon::parse($request->start)->format('Y-m-d');
        $end = Carbon::parse($request->end)->format('Y-m-d');

        $query = Schedule::with([
            'projectCampaign.targets.connectedAccount',
            'mediaFile',
            'publishLogs.connectedAccount',
        ])->whereDate('schedules.target_date', '>=', $start)
            ->whereDate('schedules.target_date', '<=', $end);

        if ($request->has('project_id') && $request->project_id !== '' && $request->project_id !== 'all') {
            $projectIds = is_array($request->project_id) ? $request->project_id : explode(',', $request->project_id);
            $query->whereIn('schedules.project_campaign_id', $projectIds);
        }

        if ($request->has('status') && $request->status !== '' && $request->status !== 'all') {
            $statuses = is_array($request->status) ? $request->status : explode(',', $request->status);
            if (in_array('failed_all', $statuses)) {
                $statuses = array_diff($statuses, ['failed_all']);
                $statuses[] = 'failed';
                $statuses[] = 'partially_failed';
            }
            $query->whereIn('schedules.status', array_unique($statuses));
        }

        if ($request->has('content_type') && $request->content_type !== '' && $request->content_type !== 'all') {
            $contentTypes = is_array($request->content_type) ? $request->content_type : explode(',', $request->content_type);
            $query->whereHas('projectCampaign', function ($q) use ($contentTypes) {
                $q->whereIn('content_type', $contentTypes);
            });
        }

        if ($request->has('account_id') && $request->account_id !== '' && $request->account_id !== 'all') {
            $accountIds = is_array($request->account_id) ? $request->account_id : explode(',', $request->account_id);
            $query->whereHas('projectCampaign.targets', function ($q) use ($accountIds) {
                $q->whereIn('connected_account_id', $accountIds);
            });
        }

        if ($request->has('platform') && $request->platform !== '' && $request->platform !== 'all') {
            $platforms = is_array($request->platform) ? $request->platform : explode(',', $request->platform);
            $query->whereHas('projectCampaign.targets', function ($q) use ($platforms) {
                $q->whereIn('platform_target', $platforms);
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('schedules.item_code', 'like', "%{$search}%")
                    ->orWhere('schedules.notes', 'like', "%{$search}%")
                    ->orWhereHas('projectCampaign', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('caption', 'like', "%{$search}%");
                    });
            });
        }

        $schedules = $query->orderBy('schedules.target_date', 'asc')
            ->orderBy('schedules.target_time', 'asc')
            ->get();

        $events = $schedules->map(function ($sch) {
            $project = $sch->projectCampaign;
            $media = $sch->mediaFile;

            $targets = [];
            if ($project) {
                foreach ($project->targets as $t) {
                    $acc = $t->connectedAccount;
                    if ($acc) {
                        $targets[] = [
                            'account_id' => $acc->id,
                            'page_name' => $acc->page_name,
                            'platform_target' => $t->platform_target,
                            'has_ig' => !empty($acc->instagram_business_id),
                            'has_fb' => !empty($acc->facebook_page_id),
                        ];
                    }
                }
            }

            $mediaUrl = $sch->media_url ?: ($media?->url ?: '');
            $isVideo = $media?->media_type === 'video' || preg_match('/\.(mp4|mov)$/i', $sch->media_path ?: '');

            return [
                'id' => $sch->id,
                'item_code' => $sch->item_code,
                'campaign_id' => $project?->id,
                'campaign_name' => $project?->name ?: 'Direct / Tanpa Campaign',
                'content_type' => $project?->content_type ?: 'post',
                'caption' => $project?->caption ?: '',
                'target_date' => $sch->target_date ? $sch->target_date->format('Y-m-d') : '',
                'target_date_formatted' => $sch->target_date ? $sch->target_date->translatedFormat('d M Y') : '',
                'target_time' => $sch->target_time ? substr($sch->target_time, 0, 5) : '00:00',
                'status' => $sch->status,
                'notes' => $sch->notes,
                'media_url' => $mediaUrl,
                'is_video' => $isVideo,
                'targets' => $targets,
                'has_logs' => $sch->publishLogs->isNotEmpty(),
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $events->count(),
            'events' => $events,
        ]);
    }

    public function recentMedia(Request $request)
    {
        $media = MediaFile::latest()
            ->take(40)
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'original_name' => $m->original_name,
                    'file_path' => $m->file_path,
                    'url' => $m->url,
                    'media_type' => $m->media_type,
                    'file_size' => $m->file_size,
                    'created_at_human' => $m->created_at?->diffForHumans() ?: '',
                ];
            });

        return response()->json([
            'success' => true,
            'media' => $media,
        ]);
    }

    public function directPost(Request $request, MetaGraphService $metaService, ?\App\Services\ThreadsService $threadsService = null)
    {
        $threadsService = $threadsService ?? app(\App\Services\ThreadsService::class);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'content_type' => 'required|in:story,post',
            'caption' => 'nullable|string',
            'media_file' => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov|max:50000',
            'existing_media_id' => 'nullable|integer|exists:media_files,id',
            'targets' => 'required|array|min:1',
            'targets.*.account_id' => 'required|exists:connected_accounts,id',
            'targets.*.platform_target' => 'required|in:all,both,threads_only,instagram_only,facebook_only,ig_threads,fb_threads',
        ]);

        if (!$request->hasFile('media_file') && !$request->filled('existing_media_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan pilih atau unggah minimal 1 file foto/video untuk diterbitkan.',
            ], 422);
        }

        $mediaFile = null;
        if ($request->hasFile('media_file')) {
            $mediaFile = $this->saveUploadedMedia($request->file('media_file'));
        } elseif ($request->filled('existing_media_id')) {
            $mediaFile = MediaFile::find($request->existing_media_id);
        }

        if (!$mediaFile) {
            return response()->json([
                'success' => false,
                'message' => 'File media tidak ditemukan atau gagal diproses.',
            ], 422);
        }

        $now = Carbon::now();
        $campaignName = trim($request->name ?: ('Post Langsung ' . $now->translatedFormat('d M Y H:i')));

        $project = ProjectCampaign::create([
            'name' => $campaignName,
            'content_type' => $request->content_type,
            'caption' => $request->caption ? trim($request->caption) : null,
            'target_time' => $now->format('H:i'),
            'images_per_post' => 1,
            'repeat_type' => 'once',
            'start_date' => $now->toDateString(),
            'end_date' => null,
            'exclude_days' => [],
            'is_continuous' => false,
            'status' => 'completed',
        ]);

        foreach ($request->targets as $targetData) {
            CampaignTarget::create([
                'project_campaign_id' => $project->id,
                'connected_account_id' => $targetData['account_id'],
                'platform_target' => $targetData['platform_target'] ?? 'both',
            ]);
        }

        $project->mediaFiles()->sync([$mediaFile->id]);

        $itemCode = 'direct_' . $project->id . '_' . $now->format('Ymd_His') . '_' . rand(10, 99);
        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => $itemCode,
            'media_file_id' => $mediaFile->id,
            'media_path' => $mediaFile->file_path,
            'media_paths' => [$mediaFile->file_path],
            'target_date' => $now->toDateString(),
            'target_time' => $now->format('H:i'),
            'status' => 'pending',
            'notes' => 'Post Langsung (Direct Post pada ' . $now->format('d/m/Y H:i') . ' WIB)',
        ]);

        try {
            (new PublishScheduleJob($schedule))->handle($metaService, $threadsService);
        } catch (\Throwable $e) {
            $schedule->update([
                'status' => 'failed',
                'notes' => 'Gagal terbit langsung: ' . $e->getMessage(),
                'executed_at' => Carbon::now(),
            ]);
        }

        $schedule->refresh();
        $schedule->load(['projectCampaign.targets.connectedAccount', 'mediaFile', 'publishLogs.connectedAccount']);

        $isSuccess = in_array($schedule->status, ['completed', 'partially_failed']);

        $message = match($schedule->status) {
            'completed' => 'Konten berhasil diterbitkan ke Meta (Facebook, Instagram & Threads)!',
            'partially_failed' => 'Konten diterbitkan sebagian. Periksa catatan log.',
            default => 'Penerbitan gagal diproses. Periksa catatan log detail.',
        };

        return response()->json([
            'success' => $isSuccess,
            'status' => $schedule->status,
            'message' => $message,
            'schedule' => $schedule,
            'logs' => $schedule->publishLogs,
        ]);
    }

    protected function saveUploadedMedia($file): ?MediaFile
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $fileHash = hash_file('sha256', $file->getRealPath());
        $existing = MediaFile::where('file_hash', $fileHash)->first();
        if ($existing) {
            return $existing;
        }

        $mediaDisk = config('filesystems.media_disk', env('MEDIA_DISK', 'local'));
        $fileName = time() . '_' . rand(100, 999) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $mime = $file->getClientMimeType() ?: $file->getMimeType();
        $fileSize = $file->getSize();
        $mediaType = (str_starts_with($mime, 'video/')) ? 'video' : 'image';

        if ($mediaDisk === 'r2') {
            $storedPath = Storage::disk('r2')->putFileAs('uploads', $file, $fileName, ['visibility' => 'public']);
            $filePath = $storedPath ?: ('uploads/' . $fileName);
        } else {
            $destinationDir = public_path('storage/uploads');
            if (!file_exists($destinationDir)) {
                mkdir($destinationDir, 0777, true);
            }
            $file->move($destinationDir, $fileName);
            $filePath = '/storage/uploads/' . $fileName;
        }

        return MediaFile::create([
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_hash' => $fileHash,
            'mime_type' => $mime,
            'file_size' => $fileSize,
            'media_type' => $mediaType,
        ]);
    }
}