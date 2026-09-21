<?php

namespace App\Services;

use App\Models\MetaCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ThreadsService
{
    protected string $apiVersion = 'v1.0';
    protected string $graphBase = 'https://graph.threads.net/v1.0';
    protected ?string $appId;
    protected ?string $appSecret;

    public const REQUIRED_SCOPES = [
        'threads_basic',
        'threads_content_publish',
    ];

    public function __construct(?MetaCredential $credential = null)
    {
        $cred = $credential ?? MetaCredential::getActive();
        $this->appId = $cred->getThreadsAppId();
        $this->appSecret = $cred->getThreadsAppSecret();
    }

    public function getGraphBaseUrl(): string
    {
        return $this->graphBase;
    }

    /**
     * Generate URL Threads OAuth Login
     */
    public function getAuthorizationUrl(string $redirectUri, string $state = ''): string
    {
        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', self::REQUIRED_SCOPES),
            'response_type' => 'code',
            'state' => $state ?: csrf_token(),
        ];

        return "https://threads.net/oauth/authorize?" . http_build_query($params);
    }

    /**
     * Tukar authorization code dengan short-lived token
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        $url = "https://graph.threads.net/oauth/access_token";

        try {
            $response = Http::asForm()->timeout(30)->post($url, [
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ]);

            $json = $response->json();

            if ($response->successful() && !empty($json['access_token'])) {
                return [
                    'success' => true,
                    'access_token' => $json['access_token'],
                    'user_id' => (string) ($json['user_id'] ?? ''),
                    'data' => $json,
                ];
            }

            Log::warning('Threads OAuth token exchange failed', ['response' => $json]);
            return [
                'success' => false,
                'error' => $json['error'] ?? ['message' => 'Gagal menukar code dengan token Threads.'],
            ];
        } catch (\Throwable $e) {
            Log::error('Threads OAuth Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Tukar Short-Lived Token ke Long-Lived Token (~60 hari)
     */
    public function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        $url = "https://graph.threads.net/access_token";

        try {
            $response = Http::timeout(30)->get($url, [
                'grant_type' => 'th_exchange_token',
                'client_secret' => $this->appSecret,
                'access_token' => $shortLivedToken,
            ]);

            $json = $response->json();

            if ($response->successful() && !empty($json['access_token'])) {
                return [
                    'success' => true,
                    'access_token' => $json['access_token'],
                    'token_type' => $json['token_type'] ?? 'bearer',
                    'expires_in' => $json['expires_in'] ?? 5184000,
                    'data' => $json,
                ];
            }

            Log::warning('Threads long-lived token exchange failed', ['response' => $json]);
            return [
                'success' => false,
                'error' => $json['error'] ?? ['message' => 'Gagal mendapatkan Long-Lived Token Threads.'],
            ];
        } catch (\Throwable $e) {
            Log::error('Threads Long-Lived Token Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Perpanjang Long-Lived Token Threads yang belum kedaluwarsa
     */
    public function refreshLongLivedToken(string $longLivedToken): array
    {
        $url = "https://graph.threads.net/refresh_access_token";

        try {
            $response = Http::timeout(30)->get($url, [
                'grant_type' => 'th_refresh_token',
                'access_token' => $longLivedToken,
            ]);

            $json = $response->json();

            if ($response->successful() && !empty($json['access_token'])) {
                return [
                    'success' => true,
                    'access_token' => $json['access_token'],
                    'token_type' => $json['token_type'] ?? 'bearer',
                    'expires_in' => $json['expires_in'] ?? 5184000,
                    'data' => $json,
                ];
            }

            return [
                'success' => false,
                'error' => $json['error'] ?? ['message' => 'Gagal memperpanjang token Threads.'],
            ];
        } catch (\Throwable $e) {
            Log::error('Threads Refresh Token Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Ambil Profil Pengguna Threads
     */
    public function getUserProfile(string $accessToken): array
    {
        $url = "{$this->graphBase}/me";

        try {
            $response = Http::timeout(20)->get($url, [
                'fields' => 'id,username,name,threads_profile_picture_url,threads_biography',
                'access_token' => $accessToken,
            ]);

            $json = $response->json();

            if ($response->successful() && !empty($json['id'])) {
                return [
                    'success' => true,
                    'id' => (string) $json['id'],
                    'username' => $json['username'] ?? '',
                    'name' => $json['name'] ?? '',
                    'threads_profile_picture_url' => $json['threads_profile_picture_url'] ?? null,
                    'data' => $json,
                ];
            }

            return [
                'success' => false,
                'error' => $json['error'] ?? ['message' => 'Gagal mengambil data profil Threads.'],
            ];
        } catch (\Throwable $e) {
            Log::error('Threads GetProfile Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Cek Limit Kuota Publikasi Threads (Maksimal 250 post per 24 jam rolling)
     */
    public function getPublishingLimit(string $threadsUserId, string $accessToken): array
    {
        $url = "{$this->graphBase}/{$threadsUserId}/threads_publishing_limit";

        try {
            $response = Http::timeout(20)->get($url, [
                'fields' => 'quota_usage,config',
                'access_token' => $accessToken,
            ]);

            $json = $response->json();

            if ($response->successful() && isset($json['data'][0])) {
                $quotaData = $json['data'][0];
                return [
                    'success' => true,
                    'quota_usage' => $quotaData['quota_usage'] ?? 0,
                    'config' => [
                        'quota_total' => $quotaData['config']['quota_total'] ?? 250,
                        'quota_duration' => $quotaData['config']['quota_duration'] ?? 86400,
                    ],
                ];
            }

            // Fallback default
            return [
                'success' => true,
                'quota_usage' => 0,
                'config' => ['quota_total' => 250, 'quota_duration' => 86400],
            ];
        } catch (\Throwable $e) {
            Log::warning('Threads publishing limit check failed: ' . $e->getMessage());
            return [
                'success' => false,
                'quota_usage' => 0,
                'config' => ['quota_total' => 250, 'quota_duration' => 86400],
                'error' => ['message' => $e->getMessage()],
            ];
        }
    }

    /**
     * Cek Status Container Media Threads (Polling FINISHED / IN_PROGRESS / ERROR)
     */
    public function checkContainerStatus(string $containerId, string $accessToken): array
    {
        $url = "{$this->graphBase}/{$containerId}";

        try {
            $response = Http::timeout(20)->get($url, [
                'fields' => 'status,error_message',
                'access_token' => $accessToken,
            ]);

            $json = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $json['status'] ?? 'FINISHED',
                    'error_message' => $json['error_message'] ?? null,
                ];
            }

            return [
                'success' => false,
                'status' => 'ERROR',
                'error' => $json['error'] ?? ['message' => 'Gagal memeriksa status container media Threads.'],
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'status' => 'ERROR', 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Buat Media Container Threads
     */
    public function createContainer(string $threadsUserId, string $accessToken, array $params): array
    {
        $url = "{$this->graphBase}/{$threadsUserId}/threads";
        $params['access_token'] = $accessToken;

        try {
            $response = Http::timeout(45)->post($url, $params);
            $json = $response->json();

            if ($response->successful() && !empty($json['id'])) {
                return [
                    'success' => true,
                    'id' => (string) $json['id'],
                    'data' => $json,
                ];
            }

            Log::error('Threads create container error', ['response' => $json, 'params' => array_diff_key($params, ['access_token' => ''])]);
            return [
                'success' => false,
                'error' => $json['error'] ?? ['message' => 'Gagal membuat container postingan Threads.'],
            ];
        } catch (\Throwable $e) {
            Log::error('Threads createContainer Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Publikasikan Container yang Siap
     */
    public function publishContainer(string $threadsUserId, string $accessToken, string $creationId): array
    {
        $url = "{$this->graphBase}/{$threadsUserId}/threads_publish";

        try {
            $response = Http::timeout(45)->post($url, [
                'creation_id' => $creationId,
                'access_token' => $accessToken,
            ]);

            $json = $response->json();

            if ($response->successful() && !empty($json['id'])) {
                return [
                    'success' => true,
                    'id' => (string) $json['id'],
                    'container_id' => $creationId,
                    'data' => $json,
                ];
            }

            Log::error('Threads publish container error', ['response' => $json, 'creation_id' => $creationId]);
            return [
                'success' => false,
                'container_id' => $creationId,
                'error' => $json['error'] ?? ['message' => 'Gagal mempublikasikan postingan Threads.'],
            ];
        } catch (\Throwable $e) {
            Log::error('Threads publishContainer Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'container_id' => $creationId,
                'error' => ['message' => $e->getMessage()],
            ];
        }
    }

    /**
     * Publikasi Utama ke Threads (Mendukung Single Image, Single Video, Carousel hingga 10 item, atau Text)
     */
    public function publishThreadsPost(
        string $threadsUserId,
        string $accessToken,
        array $mediaUrls = [],
        string $caption = '',
        bool $isVideo = false
    ): array {
        // Threads API membatasi teks maksimal 500 karakter
        $caption = mb_substr(trim($caption), 0, 500);

        // Kasus 1: Text-Only Post
        if (empty($mediaUrls)) {
            if (empty($caption)) {
                return ['success' => false, 'error' => ['message' => 'Postingan Threads teks membutuhkan konten caption.']];
            }

            $containerRes = $this->createContainer($threadsUserId, $accessToken, [
                'media_type' => 'TEXT',
                'text' => $caption,
            ]);

            if (!$containerRes['success']) {
                return $containerRes;
            }

            return $this->publishContainer($threadsUserId, $accessToken, $containerRes['id']);
        }

        // Kasus 2: Single Media Post (1 Gambar atau 1 Video)
        if (count($mediaUrls) === 1) {
            $primaryUrl = $mediaUrls[0];
            $isMediaVideo = $isVideo || (bool) preg_match('/\.(mp4|mov)$/i', $primaryUrl);

            $params = [
                'media_type' => $isMediaVideo ? 'VIDEO' : 'IMAGE',
                'text' => $caption,
            ];

            if ($isMediaVideo) {
                $params['video_url'] = $primaryUrl;
            } else {
                $params['image_url'] = $primaryUrl;
            }

            $containerRes = $this->createContainer($threadsUserId, $accessToken, $params);
            if (!$containerRes['success']) {
                return $containerRes;
            }

            $creationId = $containerRes['id'];

            // Jika video, lakukan polling hingga container status FINISHED
            if ($isMediaVideo) {
                $statusRes = $this->pollContainerReady($creationId, $accessToken);
                if (!$statusRes['success']) {
                    return [
                        'success' => false,
                        'container_id' => $creationId,
                        'error' => $statusRes['error'] ?? ['message' => 'Pemrosesan video Threads gagal atau timeout.'],
                    ];
                }
            }

            return $this->publishContainer($threadsUserId, $accessToken, $creationId);
        }

        // Kasus 3: Carousel Post (2 hingga 10 item)
        $childrenIds = [];
        $carouselItems = array_slice($mediaUrls, 0, 10);

        foreach ($carouselItems as $itemUrl) {
            $isItemVideo = (bool) preg_match('/\.(mp4|mov)$/i', $itemUrl);
            $itemParams = [
                'is_carousel_item' => 'true',
                'media_type' => $isItemVideo ? 'VIDEO' : 'IMAGE',
            ];

            if ($isItemVideo) {
                $itemParams['video_url'] = $itemUrl;
            } else {
                $itemParams['image_url'] = $itemUrl;
            }

            $itemRes = $this->createContainer($threadsUserId, $accessToken, $itemParams);
            if (!$itemRes['success']) {
                return [
                    'success' => false,
                    'error' => [
                        'message' => 'Gagal membuat item carousel Threads: ' . ($itemRes['error']['message'] ?? 'Unknown error'),
                    ],
                ];
            }

            $childId = $itemRes['id'];

            if ($isItemVideo) {
                $statusRes = $this->pollContainerReady($childId, $accessToken);
                if (!$statusRes['success']) {
                    return [
                        'success' => false,
                        'container_id' => $childId,
                        'error' => $statusRes['error'] ?? ['message' => 'Video dalam item carousel Threads gagal diproses.'],
                    ];
                }
            }

            $childrenIds[] = $childId;
        }

        // Buat Parent Carousel Container
        $parentParams = [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childrenIds),
            'text' => $caption,
        ];

        $parentRes = $this->createContainer($threadsUserId, $accessToken, $parentParams);
        if (!$parentRes['success']) {
            return $parentRes;
        }

        return $this->publishContainer($threadsUserId, $accessToken, $parentRes['id']);
    }

    /**
     * Polling container status hingga siap dipublish (FINISHED)
     */
    protected function pollContainerReady(string $containerId, string $accessToken, int $maxAttempts = 10, int $sleepSeconds = 2): array
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $statusRes = $this->checkContainerStatus($containerId, $accessToken);
            if (!$statusRes['success']) {
                return $statusRes;
            }

            $status = strtoupper($statusRes['status'] ?? '');
            if ($status === 'FINISHED') {
                return ['success' => true];
            }

            if ($status === 'ERROR') {
                return [
                    'success' => false,
                    'error' => ['message' => $statusRes['error_message'] ?? 'Status container Threads ERROR'],
                ];
            }

            if ($i < $maxAttempts - 1) {
                sleep($sleepSeconds);
            }
        }

        return [
            'success' => false,
            'error' => ['message' => 'Timeout menunggu container Threads siap (status masih IN_PROGRESS).'],
        ];
    }
}
