<?php

namespace App\Http\Controllers;

use App\Console\Commands\MaintainScheduleBufferCommand;
use App\Models\CampaignTarget;
use App\Models\ConnectedAccount;
use App\Models\MediaFile;
use App\Models\ProjectCampaign;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = ProjectCampaign::with([
            'targets.connectedAccount',
            'mediaFiles',
            'schedules' => function ($q) {
                $q->where('status', 'pending')->orderBy('target_date');
            },
        ])->latest()->get();

        $accounts = ConnectedAccount::where('is_active', true)->orderBy('page_name')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'projects' => $projects,
                'accounts' => $accounts,
            ]);
        }

        return view('projects.index', compact('projects', 'accounts'));
    }

    public function create()
    {
        $accounts = ConnectedAccount::where('is_active', true)->orderBy('page_name')->get();

        return view('projects.create', compact('accounts'));
    }

    public function show($id)
    {
        $project = ProjectCampaign::findOrFail($id);

        // Self-healing: bersihkan jadwal pending yang sudah tidak valid (misal setelah end_date diperbarui atau exclude_days)
        (new MaintainScheduleBufferCommand())->pruneOrphanedSchedules($project);

        $project->load([
            'targets.connectedAccount',
            'mediaFiles',
            'schedules' => function ($q) {
                $q->orderBy('target_date', 'asc');
            },
            'publishLogs' => function ($q) {
                $q->with('connectedAccount')->latest('executed_at')->take(50);
            },
        ]);

        $furthestDate = $project->schedules()->where('status', 'pending')->max('target_date');
        $furthestDateFormatted = $furthestDate ? Carbon::parse($furthestDate)->translatedFormat('d F Y') : 'Belum Ada Jadwal';

        $pendingCount = $project->schedules()->where('status', 'pending')->count();
        $completedCount = $project->schedules()->where('status', 'completed')->count();
        $failedCount = $project->schedules()->whereIn('status', ['failed', 'partially_failed'])->count();

        return view('projects.show', compact('project', 'furthestDateFormatted', 'pendingCount', 'completedCount', 'failedCount'));
    }

    public function edit($id)
    {
        $project = ProjectCampaign::with(['targets.connectedAccount', 'mediaFiles'])->findOrFail($id);
        $accounts = ConnectedAccount::where('is_active', true)->orderBy('page_name')->get();

        return view('projects.edit', compact('project', 'accounts'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'content_type' => 'required|in:story,post',
                'caption' => 'nullable|string',
                'target_time' => 'required|string',
                'images_per_post' => 'nullable|integer|min:1|max:10',
                'repeat_type' => 'required|in:continuous,once,until_date',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'exclude_days' => 'nullable|array',
                'media_files' => 'required|array|min:1',
                'media_files.*' => 'file|mimes:jpg,jpeg,png,mp4,mov|max:50000',
                'targets' => 'required|array|min:1',
                'targets.*.account_id' => 'required|exists:connected_accounts,id',
                'targets.*.platform_target' => 'required|in:both,instagram_only,facebook_only',
            ]);

            // Validasi Aturan 1x Post: Minimal 30 menit dari jam sekarang
            if ($request->repeat_type === 'once') {
                $targetDateStr = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : Carbon::today()->format('Y-m-d');
                $targetDateTimeStr = $targetDateStr . ' ' . trim($request->target_time);
                $scheduledAt = Carbon::parse($targetDateTimeStr);
                $minAllowedTime = Carbon::now()->addMinutes(30);

                if ($scheduledAt->lt($minAllowedTime)) {
                    $minTimeFormatted = $minAllowedTime->format('H:i');
                    $msg = "Untuk mode 1x Post, waktu penjadwalan minimal 30 menit dari jam sekarang (minimal jam {$minTimeFormatted} WIB).";
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return redirect()->back()->with('error', $msg);
                }
            }

            $project = ProjectCampaign::create([
                'name' => trim($request->name),
                'content_type' => $request->content_type,
                'caption' => $request->caption ? trim($request->caption) : null,
                'target_time' => trim($request->target_time),
                'images_per_post' => (int) ($request->images_per_post ?? 1),
                'repeat_type' => $request->repeat_type,
                'start_date' => $request->start_date ? Carbon::parse($request->start_date) : Carbon::today(),
                'end_date' => $request->end_date ? Carbon::parse($request->end_date) : null,
                'exclude_days' => array_map('intval', $request->input('exclude_days', [])),
                'is_continuous' => ($request->repeat_type === 'continuous'),
                'status' => 'active',
            ]);

            // Simpan Target Akun & Platform Target Per Akun (Bagian 2.1)
            foreach ($request->targets as $targetData) {
                CampaignTarget::create([
                    'project_campaign_id' => $project->id,
                    'connected_account_id' => $targetData['account_id'],
                    'platform_target' => $targetData['platform_target'] ?? 'both',
                ]);
            }

            // Simpan Media Pool dengan SHA-256 Deduplikasi
            $uploadedMediaIds = $this->handleMediaUploads($request->file('media_files'));
            $project->mediaFiles()->sync($uploadedMediaIds);

            // Inisialisasi Buffer Penjadwalan
            $this->seedInitialBuffer($project);

            $msg = "Project '{$project->name}' berhasil dibuat dan antrean jadwal telah diinisialisasi!";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'redirect' => route('projects.show', $project->id),
                ]);
            }

            return redirect()->route('projects.show', $project->id)->with('success', $msg);

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal membuat project: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal membuat project: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $project = ProjectCampaign::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'content_type' => 'required|in:story,post',
                'caption' => 'nullable|string',
                'target_time' => 'required|string',
                'images_per_post' => 'nullable|integer|min:1|max:10',
                'repeat_type' => 'required|in:continuous,once,until_date',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'exclude_days' => 'nullable|array',
                'media_files' => 'nullable|array',
                'media_files.*' => 'file|mimes:jpg,jpeg,png,mp4,mov|max:50000',
                'targets' => 'required|array|min:1',
                'targets.*.account_id' => 'required|exists:connected_accounts,id',
                'targets.*.platform_target' => 'required|in:both,instagram_only,facebook_only',
            ]);

            // Validasi Aturan 1x Post
            if ($request->repeat_type === 'once') {
                $targetDateStr = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : Carbon::today()->format('Y-m-d');
                $targetDateTimeStr = $targetDateStr . ' ' . trim($request->target_time);
                $scheduledAt = Carbon::parse($targetDateTimeStr);
                $minAllowedTime = Carbon::now()->addMinutes(30);

                if ($scheduledAt->lt($minAllowedTime)) {
                    $minTimeFormatted = $minAllowedTime->format('H:i');
                    $msg = "Untuk mode 1x Post, waktu penjadwalan minimal 30 menit dari jam sekarang (minimal jam {$minTimeFormatted} WIB).";
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return redirect()->back()->with('error', $msg);
                }
            }

            $project->update([
                'name' => trim($request->name),
                'content_type' => $request->content_type,
                'caption' => $request->caption ? trim($request->caption) : null,
                'target_time' => trim($request->target_time),
                'images_per_post' => (int) ($request->images_per_post ?? 1),
                'repeat_type' => $request->repeat_type,
                'start_date' => $request->start_date ? Carbon::parse($request->start_date) : $project->start_date,
                'end_date' => $request->end_date ? Carbon::parse($request->end_date) : null,
                'exclude_days' => array_map('intval', $request->input('exclude_days', [])),
                'is_continuous' => ($request->repeat_type === 'continuous'),
            ]);

            // Sync Targets (Hapus yang lama, pasang yang baru)
            $project->targets()->delete();
            foreach ($request->targets as $targetData) {
                CampaignTarget::create([
                    'project_campaign_id' => $project->id,
                    'connected_account_id' => $targetData['account_id'],
                    'platform_target' => $targetData['platform_target'] ?? 'both',
                ]);
            }

            // Tambahkan media baru jika diupload
            if ($request->hasFile('media_files')) {
                $newMediaIds = $this->handleMediaUploads($request->file('media_files'));
                $project->mediaFiles()->attach($newMediaIds);
            }

            // Sinkronkan jadwal otomatis (sesuaikan batas tanggal, hari libur, jam tayang, & buffer)
            $syncResult = $this->syncProjectSchedules($project);

            $msg = "Project '{$project->name}' berhasil diperbarui! Antrean jadwal telah disinkronkan (-{$syncResult['deleted']} dihapus, +{$syncResult['added']} ditambahkan).";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'redirect' => route('projects.show', $project->id),
                ]);
            }

            return redirect()->route('projects.show', $project->id)->with('success', $msg);

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal memperbarui project: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal memperbarui project: ' . $e->getMessage());
        }
    }

    public function addMedia(Request $request, $id)
    {
        try {
            $project = ProjectCampaign::findOrFail($id);

            $request->validate([
                'media_files' => 'required|array|min:1',
                'media_files.*' => 'file|mimes:jpg,jpeg,png,mp4,mov|max:50000',
            ]);

            $newMediaIds = $this->handleMediaUploads($request->file('media_files'));
            $project->mediaFiles()->attach($newMediaIds);

            $msg = "Berhasil menambahkan " . count($newMediaIds) . " media baru ke pool project '{$project->name}'!";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }

            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal menambah media: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal menambah media: ' . $e->getMessage());
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        $project = ProjectCampaign::findOrFail($id);
        $project->status = ($project->status === 'active') ? 'paused' : 'active';
        $project->save();

        // Jika diaktifkan kembali, maintain buffer
        if ($project->status === 'active') {
            (new MaintainScheduleBufferCommand())->maintainProjectBuffer($project);
        }

        $statusLabel = strtoupper($project->status);
        $msg = "Status project '{$project->name}' diubah menjadi {$statusLabel}.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'status' => $project->status,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function destroy(Request $request, $id)
    {
        $project = ProjectCampaign::findOrFail($id);
        $name = $project->name;
        $project->delete();

        $msg = "Project '{$name}' berhasil dihapus.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('projects.index')->with('success', $msg);
    }

    protected function handleMediaUploads(array $files): array
    {
        $destinationDir = public_path('storage/uploads');
        if (!file_exists($destinationDir)) {
            mkdir($destinationDir, 0777, true);
        }

        $uploadedIds = [];

        foreach ($files as $file) {
            if ($file->isValid()) {
                $fileName = time() . '_' . rand(100, 999) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                $file->move($destinationDir, $fileName);
                $savedFilePath = $destinationDir . '/' . $fileName;
                $filePublicUrl = '/storage/uploads/' . $fileName;

                if (file_exists($savedFilePath)) {
                    $fileHash = hash_file('sha256', $savedFilePath);
                    $existing = MediaFile::where('file_hash', $fileHash)->first();

                    if ($existing) {
                        $uploadedIds[] = $existing->id;
                        @unlink($savedFilePath); // Hapus duplikat
                    } else {
                        $mime = $file->getClientMimeType();
                        $mediaType = (str_starts_with($mime, 'video/')) ? 'video' : 'image';

                        $media = MediaFile::create([
                            'original_name' => $file->getClientOriginalName(),
                            'file_path' => $filePublicUrl,
                            'file_hash' => $fileHash,
                            'mime_type' => $mime,
                            'file_size' => filesize($savedFilePath),
                            'media_type' => $mediaType,
                        ]);
                        $uploadedIds[] = $media->id;
                    }
                }
            }
        }

        return $uploadedIds;
    }

    public function addSchedule(Request $request, $id)
    {
        try {
            $project = ProjectCampaign::with('mediaFiles')->findOrFail($id);

            $request->validate([
                'target_date' => 'required|date',
                'target_time' => 'required|string',
                'media_file_id' => 'nullable|exists:media_files,id',
                'notes' => 'nullable|string|max:255',
            ]);

            $mediaFiles = $project->mediaFiles;
            if ($mediaFiles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project belum memiliki materi di Media Pool. Silakan tambahkan media terlebih dahulu.',
                ], 422);
            }

            $targetDate = Carbon::parse($request->target_date)->format('Y-m-d');
            $targetTime = trim($request->target_time);

            $mediaFile = null;
            if ($request->media_file_id) {
                $mediaFile = $mediaFiles->where('id', $request->media_file_id)->first();
            }
            if (!$mediaFile) {
                $mediaFile = $mediaFiles->first();
            }

            $itemCode = 'proj_' . $project->id . '_' . $targetDate . '_' . rand(100, 999);

            $schedule = Schedule::create([
                'project_campaign_id' => $project->id,
                'item_code' => $itemCode,
                'media_file_id' => $mediaFile->id,
                'media_path' => $mediaFile->file_path,
                'media_paths' => [$mediaFile->file_path],
                'target_date' => $targetDate,
                'target_time' => $targetTime,
                'status' => 'pending',
                'notes' => $request->notes ? trim($request->notes) : "Jadwal Manual Project '{$project->name}'",
            ]);

            $dateFormatted = Carbon::parse($targetDate)->translatedFormat('d M Y');
            $msg = "Jadwal tayang tanggal {$dateFormatted} jam {$targetTime} WIB berhasil ditambahkan ke antrean!";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'schedule' => $schedule,
                ]);
            }

            return redirect()->back()->with('success', $msg);

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menambah jadwal: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Gagal menambah jadwal: ' . $e->getMessage());
        }
    }

    public function syncBuffer(Request $request, $id)
    {
        try {
            $project = ProjectCampaign::findOrFail($id);
            $syncResult = $this->syncProjectSchedules($project);

            $msg = "Antrean jadwal berhasil disinkronkan (-{$syncResult['deleted']} dihapus, +{$syncResult['added']} ditambahkan).";

            return response()->json([
                'success' => true,
                'message' => $msg,
                'deleted' => $syncResult['deleted'],
                'added' => $syncResult['added'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyinkronkan jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function syncProjectSchedules(ProjectCampaign $project): array
    {
        $bufferCommand = new MaintainScheduleBufferCommand();

        // 1. Bersihkan jadwal pending yang tidak valid (di luar batas tanggal atau exclude_days)
        $deletedCount = $bufferCommand->pruneOrphanedSchedules($project);

        // 2. Update target_time untuk seluruh jadwal pending tersisa
        Schedule::where('project_campaign_id', $project->id)
            ->where('status', 'pending')
            ->update(['target_time' => $project->target_time]);

        $addedCount = 0;

        // MODE 1: ONCE (Hanya 1x Post)
        if ($project->repeat_type === 'once') {
            $targetDate = $project->start_date ? Carbon::parse($project->start_date)->format('Y-m-d') : Carbon::today()->format('Y-m-d');
            $existing = Schedule::where('project_campaign_id', $project->id)
                ->where('status', 'pending')
                ->where('target_date', $targetDate)
                ->first();

            if (!$existing) {
                $this->seedOnceSchedule($project, $targetDate);
                $addedCount++;
            }

            return ['deleted' => $deletedCount, 'added' => $addedCount];
        }

        // MODE 2 & 3: CONTINUOUS & UNTIL_DATE - Isi kembali tanggal yang kosong / perluas buffer
        if ($project->status === 'active') {
            $addedCount = $bufferCommand->maintainProjectBuffer($project);
        }

        return ['deleted' => $deletedCount, 'added' => $addedCount];
    }

    public function seedOnceSchedule(ProjectCampaign $project, string $dateStr): void
    {
        $mediaFiles = $project->mediaFiles;
        if ($mediaFiles->isEmpty()) return;

        $imagesPerPost = max(1, $project->images_per_post ?: 1);
        $paths = [];
        $primaryMedia = null;

        for ($imgIdx = 0; $imgIdx < $imagesPerPost; $imgIdx++) {
            $picked = $mediaFiles[$imgIdx % $mediaFiles->count()];
            if ($imgIdx === 0) $primaryMedia = $picked;
            $paths[] = $picked->file_path;
        }

        $primaryPath = $paths[0] ?? '';
        $itemCode = 'proj_' . $project->id . '_' . $dateStr . '_' . rand(10, 99);

        Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => $itemCode,
            'media_file_id' => $primaryMedia?->id,
            'media_path' => $primaryPath,
            'media_paths' => $paths,
            'target_date' => $dateStr,
            'target_time' => $project->target_time,
            'status' => 'pending',
            'notes' => "Single Post Project '{$project->name}'",
        ]);
    }

    protected function seedInitialBuffer(ProjectCampaign $project): void
    {
        if ($project->repeat_type === 'once') {
            $targetDate = $project->start_date ? Carbon::parse($project->start_date) : Carbon::today();
            $this->seedOnceSchedule($project, $targetDate->format('Y-m-d'));
            return;
        }

        // MODE 2 & 3: CONTINUOUS & UNTIL_DATE via Buffer Command
        (new MaintainScheduleBufferCommand())->maintainProjectBuffer($project);
    }
}