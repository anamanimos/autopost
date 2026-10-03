<?php

namespace App\Jobs;

use App\Models\PublishLog;
use App\Models\Schedule;
use App\Services\MetaGraphService;
use App\Services\ThreadsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Schedule $schedule;
    public bool $forceAll;

    public function __construct(Schedule $schedule, bool $forceAll = false)
    {
        $this->schedule = $schedule;
        $this->forceAll = $forceAll;
    }

    public function handle(MetaGraphService $metaService, ?ThreadsService $threadsService = null, ?\App\Services\TikTokService $tikTokService = null): void
    {
        $threadsService = $threadsService ?? app(ThreadsService::class);
        $tikTokService = $tikTokService ?? app(\App\Services\TikTokService::class);
        $schedule = $this->schedule->fresh(['projectCampaign.targets.connectedAccount', 'mediaFile']);
        if (!$schedule || $schedule->status === 'completed') {
            return;
        }

        $schedule->update([
            'status' => 'processing',
            'notes' => 'Sedang mempublikasikan konten via Meta Graph API...',
        ]);

        $project = $schedule->projectCampaign;
        if (!$project) {
            $schedule->update([
                'status' => 'failed',
                'notes' => 'Project campaign terkait tidak ditemukan.',
                'executed_at' => Carbon::now(),
            ]);
            return;
        }

        $targets = $project->targets;
        if ($targets->isEmpty()) {
            $schedule->update([
                'status' => 'failed',
                'notes' => 'Tidak ada target akun yang dikonfigurasi untuk campaign ini.',
                'executed_at' => Carbon::now(),
            ]);
            return;
        }

        $caption = trim($project->caption ?? '');
        $contentType = $project->content_type ?? 'story';

        // Siapkan Media URLs yang absolut / publik
        $mediaUrls = $schedule->media_urls;
        $hasMedia = !empty($mediaUrls);

        if (!$hasMedia && empty($caption)) {
            $schedule->update([
                'status' => 'failed',
                'notes' => 'Konten kosong: Tidak ada media dan tidak ada caption/teks untuk dipublikasikan.',
                'executed_at' => Carbon::now(),
            ]);
            return;
        }

        // Ubah localhost URL agar sesuai konfigurasi APP_URL publik jika diset
        if ($hasMedia) {
            $mediaUrls = array_map(function ($url) {
                if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                    return url($url);
                }
                return $url;
            }, $mediaUrls);
        }

        $primaryUrl = $mediaUrls[0] ?? null;
        $isVideo = $primaryUrl ? (bool) preg_match('/\.(mp4|mov)$/i', $primaryUrl) : false;

        $totalActions = 0;
        $successActions = 0;
        $failedActions = 0;
        $skippedActions = 0;

        foreach ($targets as $target) {
            $account = $target->connectedAccount;
            if (!$account) continue;

            // Jika aset sudah dinonaktifkan (Soft-Deactivation)
            if (!$account->is_active) {
                if ($target->targetsInstagram()) {
                    PublishLog::firstOrCreate([
                        'schedule_id' => $schedule->id,
                        'project_campaign_id' => $project->id,
                        'connected_account_id' => $account->id,
                        'platform' => 'instagram',
                    ], [
                        'content_type' => $contentType,
                        'action_status' => 'skipped',
                        'error_message' => 'Akun dinonaktifkan / tidak ditemukan di Meta (Soft-Deactivation)',
                        'executed_at' => Carbon::now(),
                    ]);
                    $skippedActions++;
                }

                if ($target->targetsFacebook()) {
                    PublishLog::firstOrCreate([
                        'schedule_id' => $schedule->id,
                        'project_campaign_id' => $project->id,
                        'connected_account_id' => $account->id,
                        'platform' => 'facebook',
                    ], [
                        'content_type' => $contentType,
                        'action_status' => 'skipped',
                        'error_message' => 'Akun dinonaktifkan / tidak ditemukan di Meta (Soft-Deactivation)',
                        'executed_at' => Carbon::now(),
                    ]);
                    $skippedActions++;
                }

                if ($target->targetsThreads()) {
                    PublishLog::firstOrCreate([
                        'schedule_id' => $schedule->id,
                        'project_campaign_id' => $project->id,
                        'connected_account_id' => $account->id,
                        'platform' => 'threads',
                    ], [
                        'content_type' => $contentType,
                        'action_status' => 'skipped',
                        'error_message' => 'Akun dinonaktifkan / tidak ditemukan di Meta (Soft-Deactivation)',
                        'executed_at' => Carbon::now(),
                    ]);
                    $skippedActions++;
                }

                if ($target->targetsTikTok()) {
                    PublishLog::firstOrCreate([
                        'schedule_id' => $schedule->id,
                        'project_campaign_id' => $project->id,
                        'connected_account_id' => $account->id,
                        'platform' => 'tiktok',
                    ], [
                        'content_type' => $contentType,
                        'action_status' => 'skipped',
                        'error_message' => 'Akun dinonaktifkan / tidak ditemukan di TikTok (Soft-Deactivation)',
                        'executed_at' => Carbon::now(),
                    ]);
                    $skippedActions++;
                }
                continue;
            }

            // 1. Eksekusi INSTAGRAM (jika target mencakup Instagram)
            if ($target->targetsInstagram()) {
                $totalActions++;

                $existingIgSuccess = PublishLog::where('schedule_id', $schedule->id)
                    ->where('connected_account_id', $account->id)
                    ->where('platform', 'instagram')
                    ->where('action_status', 'success')
                    ->first();

                if ($existingIgSuccess && !$this->forceAll) {
                    $successActions++;
                } else {
                    $existingIgFailed = PublishLog::where('schedule_id', $schedule->id)
                        ->where('connected_account_id', $account->id)
                        ->where('platform', 'instagram')
                        ->where('action_status', '!=', 'success')
                        ->latest('id')
                        ->first();

                    if (!$hasMedia) {
                        $logPayload = [
                            'schedule_id' => $schedule->id,
                            'project_campaign_id' => $project->id,
                            'connected_account_id' => $account->id,
                            'platform' => 'instagram',
                            'content_type' => $contentType,
                            'action_status' => 'failed',
                            'error_message' => 'Instagram mewajibkan file media (gambar atau video). Postingan teks saja tidak didukung oleh Instagram.',
                            'executed_at' => Carbon::now(),
                        ];
                        if ($existingIgFailed) {
                            $existingIgFailed->update($logPayload);
                        } else {
                            PublishLog::create($logPayload);
                        }
                        $failedActions++;
                    } elseif (empty($account->ig_user_id)) {
                        $logPayload = [
                            'schedule_id' => $schedule->id,
                            'project_campaign_id' => $project->id,
                            'connected_account_id' => $account->id,
                            'platform' => 'instagram',
                            'content_type' => $contentType,
                            'action_status' => 'failed',
                            'error_message' => 'Halaman Facebook ini belum terhubung ke Instagram Business Account.',
                            'executed_at' => Carbon::now(),
                        ];
                        if ($existingIgFailed) {
                            $existingIgFailed->update($logPayload);
                        } else {
                            PublishLog::create($logPayload);
                        }
                        $failedActions++;
                    } else {
                        // Cek limit kuota 100 post / 24h
                        $limitInfo = $metaService->getContentPublishingLimit($account->ig_user_id, $account->page_access_token);
                        if ($limitInfo['success'] && ($limitInfo['quota_usage'] ?? 0) >= ($limitInfo['config']['quota_total'] ?? 100)) {
                            $logPayload = [
                                'schedule_id' => $schedule->id,
                                'project_campaign_id' => $project->id,
                                'connected_account_id' => $account->id,
                                'platform' => 'instagram',
                                'content_type' => $contentType,
                                'action_status' => 'failed',
                                'error_message' => 'Instagram Content Publishing Limit tercapai (100 post / 24 jam rolling).',
                                'executed_at' => Carbon::now(),
                            ];
                            if ($existingIgFailed) {
                                $existingIgFailed->update($logPayload);
                            } else {
                                PublishLog::create($logPayload);
                            }
                            $failedActions++;
                        } else {
                            // Publikasi Instagram
                            if ($contentType === 'story') {
                                $igRes = $metaService->publishInstagramStory(
                                    $account->ig_user_id,
                                    $account->page_access_token,
                                    $primaryUrl,
                                    $isVideo
                                );
                            } else {
                                $igRes = $metaService->publishInstagramFeedPost(
                                    $account->ig_user_id,
                                    $account->page_access_token,
                                    $mediaUrls,
                                    $caption,
                                    $isVideo
                                );
                            }

                            if ($igRes['success']) {
                                $logPayload = [
                                    'schedule_id' => $schedule->id,
                                    'project_campaign_id' => $project->id,
                                    'connected_account_id' => $account->id,
                                    'platform' => 'instagram',
                                    'content_type' => $contentType,
                                    'action_status' => 'success',
                                    'media_id' => $igRes['id'] ?? null,
                                    'container_id' => $igRes['container_id'] ?? null,
                                    'response_payload' => $igRes['data'] ?? [],
                                    'error_message' => null,
                                    'error_code' => null,
                                    'error_subcode' => null,
                                    'executed_at' => Carbon::now(),
                                ];
                                if ($existingIgFailed) {
                                    $existingIgFailed->update($logPayload);
                                } else {
                                    PublishLog::create($logPayload);
                                }
                                $successActions++;

                                $account->increment('ig_publishing_quota_usage');
                            } else {
                                $err = $igRes['error'] ?? [];
                                $logPayload = [
                                    'schedule_id' => $schedule->id,
                                    'project_campaign_id' => $project->id,
                                    'connected_account_id' => $account->id,
                                    'platform' => 'instagram',
                                    'content_type' => $contentType,
                                    'action_status' => 'failed',
                                    'container_id' => $igRes['container_id'] ?? null,
                                    'error_message' => $err['message'] ?? 'Gagal mempublish ke Instagram',
                                    'error_code' => $err['code'] ?? null,
                                    'error_subcode' => $err['error_subcode'] ?? null,
                                    'response_payload' => $err,
                                    'executed_at' => Carbon::now(),
                                ];
                                if ($existingIgFailed) {
                                    $existingIgFailed->update($logPayload);
                                } else {
                                    PublishLog::create($logPayload);
                                }
                                $failedActions++;
                            }
                        }
                    }
                }
            } else {
                $existingIgSkipped = PublishLog::where('schedule_id', $schedule->id)
                    ->where('connected_account_id', $account->id)
                    ->where('platform', 'instagram')
                    ->where('action_status', 'skipped')
                    ->first();

                if (!$existingIgSkipped) {
                    PublishLog::create([
                        'schedule_id' => $schedule->id,
                        'project_campaign_id' => $project->id,
                        'connected_account_id' => $account->id,
                        'platform' => 'instagram',
                        'content_type' => $contentType,
                        'action_status' => 'skipped',
                        'error_message' => 'Dikecualikan berdasarkan pengaturan target (' . $target->platform_target . ')',
                        'executed_at' => Carbon::now(),
                    ]);
                }
                $skippedActions++;
            }

            // 2. Eksekusi FACEBOOK PAGE (jika target mencakup Facebook)
            if ($target->targetsFacebook()) {
                $totalActions++;

                $existingFbSuccess = PublishLog::where('schedule_id', $schedule->id)
                    ->where('connected_account_id', $account->id)
                    ->where('platform', 'facebook')
                    ->where('action_status', 'success')
                    ->first();

                $existingFbFailed = PublishLog::where('schedule_id', $schedule->id)
                    ->where('connected_account_id', $account->id)
                    ->where('platform', 'facebook')
                    ->where('action_status', 'failed')
                    ->first();

                if ($existingFbSuccess && !$this->forceAll) {
                    $successActions++;
                } else {
                    $fbRes = $metaService->publishFacebookPage(
                        $account->page_id,
                        $account->page_access_token,
                        $mediaUrls,
                        $caption,
                        $contentType
                    );

                    if ($fbRes['success']) {
                        $logPayload = [
                            'schedule_id' => $schedule->id,
                            'project_campaign_id' => $project->id,
                            'connected_account_id' => $account->id,
                            'platform' => 'facebook',
                            'content_type' => $contentType,
                            'action_status' => 'success',
                            'media_id' => $fbRes['id'] ?? null,
                            'response_payload' => $fbRes['data'] ?? [],
                            'error_message' => null,
                            'error_code' => null,
                            'error_subcode' => null,
                            'executed_at' => Carbon::now(),
                        ];
                        if ($existingFbFailed) {
                            $existingFbFailed->update($logPayload);
                        } else {
                            PublishLog::create($logPayload);
                        }
                        $successActions++;
                    } else {
                        $err = $fbRes['error'] ?? [];
                        $logPayload = [
                            'schedule_id' => $schedule->id,
                            'project_campaign_id' => $project->id,
                            'connected_account_id' => $account->id,
                            'platform' => 'facebook',
                            'content_type' => $contentType,
                            'action_status' => 'failed',
                            'error_message' => $err['message'] ?? 'Gagal mempublish ke Facebook Page',
                            'error_code' => $err['code'] ?? null,
                            'error_subcode' => $err['error_subcode'] ?? null,
                            'response_payload' => $err,
                            'executed_at' => Carbon::now(),
                        ];
                        if ($existingFbFailed) {
                            $existingFbFailed->update($logPayload);
                        } else {
                            PublishLog::create($logPayload);
                        }
                        $failedActions++;
                    }
                }
            } else {
                $existingFbSkipped = PublishLog::where('schedule_id', $schedule->id)
                    ->where('connected_account_id', $account->id)
                    ->where('platform', 'facebook')
                    ->where('action_status', 'skipped')
                    ->first();

                if (!$existingFbSkipped) {
                    PublishLog::create([
                        'schedule_id' => $schedule->id,
                        'project_campaign_id' => $project->id,
                        'connected_account_id' => $account->id,
                        'platform' => 'facebook',
                        'content_type' => $contentType,
                        'action_status' => 'skipped',
                        'error_message' => 'Dikecualikan berdasarkan pengaturan target (' . $target->platform_target . ')',
                        'executed_at' => Carbon::now(),
                    ]);
                }
                $skippedActions++;
            }

            // 3. Eksekusi THREADS (jika target mencakup Threads)
            if ($target->targetsThreads()) {
                $totalActions++;

                $existingThreadsSuccess = PublishLog::where('schedule_id', $schedule->id)
                    ->where('connected_account_id', $account->id)
                    ->where('platform', 'threads')
                    ->where('action_status', 'success')
                    ->first();

                $existingThreadsFailed = PublishLog::where('schedule_id', $schedule->id)
                    ->where('connected_account_id', $account->id)
                    ->where('platform', 'threads')
                    ->where('action_status', 'failed')
                    ->first();

                if ($existingThreadsSuccess && !$this->forceAll) {
                    $successActions++;
                } else {
                    if (!$account->hasThreads()) {
                        $logPayload = [
                            'schedule_id' => $schedule->id,
                            'project_campaign_id' => $project->id,
                            'connected_account_id' => $account->id,
                            'platform' => 'threads',
                            'content_type' => $contentType,
                            'action_status' => 'failed',
                            'error_message' => 'Akun ini belum terhubung ke Meta Threads API.',
                            'executed_at' => Carbon::now(),
                        ];
                        if ($existingThreadsFailed) {
                            $existingThreadsFailed->update($logPayload);
                        } else {
                            PublishLog::create($logPayload);
                        }
                        $failedActions++;
                    } else {
                        // Cek limit kuota 250 post / 24 jam rolling
                        $limitInfo = $threadsService->getPublishingLimit($account->threads_user_id, $account->threads_access_token);
                        if ($limitInfo['success'] && ($limitInfo['quota_usage'] ?? 0) >= ($limitInfo['config']['quota_total'] ?? 250)) {
                            $logPayload = [
                                'schedule_id' => $schedule->id,
                                'project_campaign_id' => $project->id,
                                'connected_account_id' => $account->id,
                                'platform' => 'threads',
                                'content_type' => $contentType,
                                'action_status' => 'failed',
                                'error_message' => 'Threads Content Publishing Limit tercapai (250 post / 24 jam rolling).',
                                'executed_at' => Carbon::now(),
                            ];
                            if ($existingThreadsFailed) {
                                $existingThreadsFailed->update($logPayload);
                            } else {
                                PublishLog::create($logPayload);
                            }
                            $failedActions++;
                        } else {
                            $threadsRes = $threadsService->publishThreadsPost(
                                $account->threads_user_id,
                                $account->threads_access_token,
                                $mediaUrls,
                                $caption,
                                $isVideo
                            );

                            if ($threadsRes['success']) {
                                $logPayload = [
                                    'schedule_id' => $schedule->id,
                                    'project_campaign_id' => $project->id,
                                    'connected_account_id' => $account->id,
                                    'platform' => 'threads',
                                    'content_type' => $contentType,
                                    'action_status' => 'success',
                                    'media_id' => $threadsRes['id'] ?? null,
                                    'container_id' => $threadsRes['container_id'] ?? null,
                                    'response_payload' => $threadsRes['data'] ?? [],
                                    'error_message' => null,
                                    'error_code' => null,
                                    'error_subcode' => null,
                                    'executed_at' => Carbon::now(),
                                ];
                                if ($existingThreadsFailed) {
                                    $existingThreadsFailed->update($logPayload);
                                } else {
                                    PublishLog::create($logPayload);
                                }
                                $successActions++;

                                // Update quota lokal
                                $account->increment('threads_publishing_quota_usage');
                            } else {
                                $err = $threadsRes['error'] ?? [];
                                $logPayload = [
                                    'schedule_id' => $schedule->id,
                                    'project_campaign_id' => $project->id,
                                    'connected_account_id' => $account->id,
                                    'platform' => 'threads',
                                    'content_type' => $contentType,
                                    'action_status' => 'failed',
                                    'container_id' => $threadsRes['container_id'] ?? null,
                                    'error_message' => $err['message'] ?? 'Gagal mempublish ke Meta Threads',
                                    'error_code' => $err['code'] ?? null,
                                    'error_subcode' => $err['error_subcode'] ?? null,
                                    'response_payload' => $err,
                                    'executed_at' => Carbon::now(),
                                ];
                                if ($existingThreadsFailed) {
                                    $existingThreadsFailed->update($logPayload);
                                } else {
                                    PublishLog::create($logPayload);
                                }
                                $failedActions++;
                            }
                        }
                    }
                }
            }

            // 4. Eksekusi TIKTOK (jika target mencakup TikTok)
            if ($target->targetsTikTok()) {
                if ($target->platform_target === 'all' && !$account->hasTikTok()) {
                    // Akun belum menghubungkan TikTok, abaikan secara aman untuk target 'all'
                } else {
                    $totalActions++;

                $existingTikTokSuccess = PublishLog::where('schedule_id', $schedule->id)
                    ->where('connected_account_id', $account->id)
                    ->where('platform', 'tiktok')
                    ->where('action_status', 'success')
                    ->first();

                $existingTikTokFailed = PublishLog::where('schedule_id', $schedule->id)
                    ->where('connected_account_id', $account->id)
                    ->where('platform', 'tiktok')
                    ->where('action_status', 'failed')
                    ->first();

                if ($existingTikTokSuccess && !$this->forceAll) {
                    $successActions++;
                } else {
                    if (!$account->hasTikTok()) {
                        $logPayload = [
                            'schedule_id' => $schedule->id,
                            'project_campaign_id' => $project->id,
                            'connected_account_id' => $account->id,
                            'platform' => 'tiktok',
                            'content_type' => $contentType,
                            'action_status' => 'failed',
                            'error_message' => 'Akun ini belum terhubung ke TikTok API.',
                            'executed_at' => Carbon::now(),
                        ];
                        if ($existingTikTokFailed) {
                            $existingTikTokFailed->update($logPayload);
                        } else {
                            PublishLog::create($logPayload);
                        }
                        $failedActions++;
                    } elseif (!$hasMedia) {
                        $logPayload = [
                            'schedule_id' => $schedule->id,
                            'project_campaign_id' => $project->id,
                            'connected_account_id' => $account->id,
                            'platform' => 'tiktok',
                            'content_type' => $contentType,
                            'action_status' => 'failed',
                            'error_message' => 'TikTok mewajibkan aset video atau foto. Postingan teks murni tidak didukung.',
                            'executed_at' => Carbon::now(),
                        ];
                        if ($existingTikTokFailed) {
                            $existingTikTokFailed->update($logPayload);
                        } else {
                            PublishLog::create($logPayload);
                        }
                        $failedActions++;
                    } else {
                        $tiktokRes = $tikTokService->publishTikTokPost(
                            $account,
                            $mediaUrls,
                            $caption,
                            $isVideo
                        );

                        if ($tiktokRes['success']) {
                            $logPayload = [
                                'schedule_id' => $schedule->id,
                                'project_campaign_id' => $project->id,
                                'connected_account_id' => $account->id,
                                'platform' => 'tiktok',
                                'content_type' => $contentType,
                                'action_status' => 'success',
                                'media_id' => $tiktokRes['id'] ?? ($tiktokRes['publish_id'] ?? null),
                                'response_payload' => $tiktokRes['data'] ?? [],
                                'error_message' => null,
                                'error_code' => null,
                                'error_subcode' => null,
                                'executed_at' => Carbon::now(),
                            ];
                            if ($existingTikTokFailed) {
                                $existingTikTokFailed->update($logPayload);
                            } else {
                                PublishLog::create($logPayload);
                            }
                            $successActions++;

                            $account->increment('tiktok_publishing_quota_usage');
                        } else {
                            $err = $tiktokRes['error'] ?? [];
                            $logPayload = [
                                'schedule_id' => $schedule->id,
                                'project_campaign_id' => $project->id,
                                'connected_account_id' => $account->id,
                                'platform' => 'tiktok',
                                'content_type' => $contentType,
                                'action_status' => 'failed',
                                'error_message' => $err['message'] ?? 'Gagal mempublish ke TikTok',
                                'error_code' => $err['code'] ?? null,
                                'response_payload' => $err,
                                'executed_at' => Carbon::now(),
                            ];
                            try {
                                if ($existingTikTokFailed) {
                                    $existingTikTokFailed->update($logPayload);
                                } else {
                                    PublishLog::create($logPayload);
                                }
                            } catch (\Throwable $dbEx) {
                                if (str_contains($dbEx->getMessage(), 'error_code') || str_contains($dbEx->getMessage(), '1366')) {
                                    $logPayload['error_code'] = null;
                                    if ($existingTikTokFailed) {
                                        $existingTikTokFailed->update($logPayload);
                                    } else {
                                        PublishLog::create($logPayload);
                                    }
                                } else {
                                    throw $dbEx;
                                }
                            }
                            $failedActions++;
                        }
                    }
                }
            }
        }
    }

        // Tentukan Final Status Jadwal
        $finalStatus = 'completed';
        if ($failedActions > 0 && $successActions > 0) {
            $finalStatus = 'partially_failed';
        } elseif ($failedActions > 0 && $successActions === 0) {
            $finalStatus = 'failed';
        }

        $note = sprintf(
            'Eksekusi selesai: %d sukses, %d gagal, %d diskip dari total %d aksi.',
            $successActions,
            $failedActions,
            $skippedActions,
            $totalActions
        );

        $schedule->update([
            'status' => $finalStatus,
            'notes' => $note,
            'executed_at' => Carbon::now(),
        ]);
    }
}