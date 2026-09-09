<?php

namespace App\Services;

use App\Models\MetaCredential;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaGraphService
{
    protected string $graphVersion;
    protected ?string $appId;
    protected ?string $appSecret;

    public const REQUIRED_SCOPES = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_posts',
        'instagram_basic',
        'instagram_content_publish',
        'business_management',
    ];

    public function __construct(?MetaCredential $credential = null)
    {
        $cred = $credential ?? MetaCredential::getActive();
        $this->graphVersion = $cred->graph_version ?: env('META_GRAPH_VERSION', 'v22.0');
        $this->appId = $cred->app_id ?: env('META_APP_ID');
        $this->appSecret = $cred->app_secret ?: env('META_APP_SECRET');
    }

    public function getGraphBaseUrl(): string
    {
        return "https://graph.facebook.com/{$this->graphVersion}";
    }

    /**
     * Generate URL Facebook OAuth Login for Business
     */
    public function getAuthorizationUrl(string $redirectUri, string $state = ''): string
    {
        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'state' => $state ?: csrf_token(),
            'scope' => implode(',', self::REQUIRED_SCOPES),
            'response_type' => 'code',
        ];

        return "https://www.facebook.com/{$this->graphVersion}/dialog/oauth?" . http_build_query($params);
    }

    /**
     * Tukar authorization code dengan short-lived user token
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        $url = "{$this->getGraphBaseUrl()}/oauth/access_token";
        $response = Http::timeout(20)->get($url, [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $this->parseMetaError($response),
        ];
    }

    /**
     * Tukar short-lived token menjadi Long-Lived User Token (~60 hari)
     */
    public function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        $url = "{$this->getGraphBaseUrl()}/oauth/access_token";
        $response = Http::timeout(20)->get($url, [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $this->parseMetaError($response),
        ];
    }

    /**
     * Periksa validitas & metadata token via /debug_token
     */
    public function debugToken(string $inputToken): array
    {
        $appAccessToken = "{$this->appId}|{$this->appSecret}";
        $url = "{$this->getGraphBaseUrl()}/debug_token";

        $response = Http::timeout(15)->get($url, [
            'input_token' => $inputToken,
            'access_token' => $appAccessToken,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json()['data'] ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $this->parseMetaError($response),
        ];
    }

    /**
     * Ambil daftar Facebook Pages yang dikelola (/me/accounts)
     */
    public function getManagedPages(string $userAccessToken): array
    {
        $url = "{$this->getGraphBaseUrl()}/me/accounts";
        $response = Http::timeout(25)->get($url, [
            'fields' => 'id,name,category,access_token,tasks',
            'limit' => 100,
            'access_token' => $userAccessToken,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json()['data'] ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $this->parseMetaError($response),
        ];
    }

    /**
     * Ambil Instagram Business Account yang terhubung ke Facebook Page
     */
    public function getLinkedInstagramAccount(string $pageId, string $pageAccessToken): ?array
    {
        $url = "{$this->getGraphBaseUrl()}/{$pageId}";
        $response = Http::timeout(15)->get($url, [
            'fields' => 'instagram_business_account{id,username,name,profile_picture_url}',
            'access_token' => $pageAccessToken,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['instagram_business_account'] ?? null;
        }

        return null;
    }

    /**
     * Cek kuota publish Instagram 24 jam rolling (/content_publishing_limit)
     */
    public function getContentPublishingLimit(string $igUserId, string $pageAccessToken): array
    {
        $url = "{$this->getGraphBaseUrl()}/{$igUserId}/content_publishing_limit";
        $response = Http::timeout(15)->get($url, [
            'fields' => 'config,quota_usage',
            'access_token' => $pageAccessToken,
        ]);

        if ($response->successful()) {
            $data = $response->json()['data'][0] ?? [];
            return [
                'success' => true,
                'quota_usage' => $data['quota_usage'] ?? 0,
                'config' => $data['config'] ?? ['quota_total' => 100],
            ];
        }

        return [
            'success' => false,
            'quota_usage' => 0,
            'config' => ['quota_total' => 100],
            'error' => $this->parseMetaError($response),
        ];
    }

    /**
     * Buat Media Container Instagram (POST /{ig_user_id}/media)
     */
    public function createInstagramContainer(string $igUserId, string $pageAccessToken, array $params): array
    {
        $url = "{$this->getGraphBaseUrl()}/{$igUserId}/media";
        $params['access_token'] = $pageAccessToken;

        $response = Http::timeout(30)->post($url, $params);

        if ($response->successful()) {
            return [
                'success' => true,
                'id' => $response->json()['id'] ?? null,
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $this->parseMetaError($response),
        ];
    }

    /**
     * Poll status container (GET /{container_id}?fields=status_code)
     */
    public function checkContainerStatus(string $containerId, string $pageAccessToken): array
    {
        $url = "{$this->getGraphBaseUrl()}/{$containerId}";
        $response = Http::timeout(15)->get($url, [
            'fields' => 'status_code,status',
            'access_token' => $pageAccessToken,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'status_code' => $data['status_code'] ?? 'UNKNOWN',
                'data' => $data,
            ];
        }

        return [
            'success' => false,
            'status_code' => 'ERROR',
            'error' => $this->parseMetaError($response),
        ];
    }

    /**
     * Publish Media Container Instagram (POST /{ig_user_id}/media_publish)
     */
    public function publishInstagramContainer(string $igUserId, string $pageAccessToken, string $creationId): array
    {
        $url = "{$this->getGraphBaseUrl()}/{$igUserId}/media_publish";
        $response = Http::timeout(30)->post($url, [
            'creation_id' => $creationId,
            'access_token' => $pageAccessToken,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'id' => $response->json()['id'] ?? null,
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $this->parseMetaError($response),
        ];
    }

    /**
     * Publish Instagram Story (Single Image or Video)
     */
    public function publishInstagramStory(string $igUserId, string $pageAccessToken, string $mediaUrl, bool $isVideo = false): array
    {
        $params = [
            'media_type' => 'STORIES',
        ];

        if ($isVideo) {
            $params['video_url'] = $mediaUrl;
        } else {
            $params['image_url'] = $mediaUrl;
        }

        // 1. Buat Container
        $containerRes = $this->createInstagramContainer($igUserId, $pageAccessToken, $params);
        if (!$containerRes['success']) {
            return $containerRes;
        }

        $containerId = $containerRes['id'];

        // 2. Jika Video, polling status sampai FINISHED (maks 5x polling per 10-30 detik)
        if ($isVideo) {
            $maxPolls = 6;
            $finished = false;

            for ($i = 0; $i < $maxPolls; $i++) {
                sleep(5);
                $statusRes = $this->checkContainerStatus($containerId, $pageAccessToken);
                $statusCode = $statusRes['status_code'] ?? '';

                if ($statusCode === 'FINISHED') {
                    $finished = true;
                    break;
                } elseif (in_array($statusCode, ['ERROR', 'EXPIRED'])) {
                    return [
                        'success' => false,
                        'container_id' => $containerId,
                        'error' => [
                            'message' => "Container status returned: {$statusCode}",
                            'code' => 500,
                        ],
                    ];
                }
            }

            if (!$finished) {
                return [
                    'success' => false,
                    'container_id' => $containerId,
                    'error' => [
                        'message' => "Video container processing timeout (not FINISHED yet)",
                        'code' => 408,
                    ],
                ];
            }
        }

        // 3. Publish Container
        $publishRes = $this->publishInstagramContainer($igUserId, $pageAccessToken, $containerId);
        $publishRes['container_id'] = $containerId;
        return $publishRes;
    }

    /**
     * Publish Instagram Feed Post (Single Image, Single Video/Reels, or Carousel)
     */
    public function publishInstagramFeedPost(string $igUserId, string $pageAccessToken, array $mediaUrls, string $caption = '', bool $isVideo = false): array
    {
        if (count($mediaUrls) === 1) {
            $mediaUrl = $mediaUrls[0];
            $params = [
                'caption' => $caption,
            ];

            if ($isVideo) {
                $params['video_url'] = $mediaUrl;
                $params['media_type'] = 'REELS';
            } else {
                $params['image_url'] = $mediaUrl;
            }

            $containerRes = $this->createInstagramContainer($igUserId, $pageAccessToken, $params);
            if (!$containerRes['success']) {
                return $containerRes;
            }

            $containerId = $containerRes['id'];

            if ($isVideo) {
                for ($i = 0; $i < 6; $i++) {
                    sleep(5);
                    $statusRes = $this->checkContainerStatus($containerId, $pageAccessToken);
                    if (($statusRes['status_code'] ?? '') === 'FINISHED') break;
                }
            }

            $publishRes = $this->publishInstagramContainer($igUserId, $pageAccessToken, $containerId);
            $publishRes['container_id'] = $containerId;
            return $publishRes;
        }

        // Carousel (Multi-Item)
        $childContainerIds = [];
        foreach ($mediaUrls as $url) {
            $itemParams = [
                'is_carousel_item' => 'true',
            ];
            if (preg_match('/\.(mp4|mov)$/i', $url)) {
                $itemParams['video_url'] = $url;
                $itemParams['media_type'] = 'VIDEO';
            } else {
                $itemParams['image_url'] = $url;
            }

            $childRes = $this->createInstagramContainer($igUserId, $pageAccessToken, $itemParams);
            if ($childRes['success']) {
                $childContainerIds[] = $childRes['id'];
            }
        }

        if (empty($childContainerIds)) {
            return [
                'success' => false,
                'error' => ['message' => 'Gagal membuat container item carousel', 'code' => 500],
            ];
        }

        // Parent Carousel Container
        $parentRes = $this->createInstagramContainer($igUserId, $pageAccessToken, [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childContainerIds),
            'caption' => $caption,
        ]);

        if (!$parentRes['success']) {
            return $parentRes;
        }

        $parentContainerId = $parentRes['id'];
        $publishRes = $this->publishInstagramContainer($igUserId, $pageAccessToken, $parentContainerId);
        $publishRes['container_id'] = $parentContainerId;
        return $publishRes;
    }

    /**
     * Publish Facebook Page Post (Photos, Videos, or Feed)
     */
    public function publishFacebookPage(string $pageId, string $pageAccessToken, array $mediaUrls, string $caption = '', string $contentType = 'story'): array
    {
        $primaryUrl = $mediaUrls[0] ?? null;
        $isVideo = $primaryUrl && preg_match('/\.(mp4|mov)$/i', $primaryUrl);

        // Jika Video: POST /{page_id}/videos
        if ($isVideo) {
            $url = "{$this->getGraphBaseUrl()}/{$pageId}/videos";
            $response = Http::timeout(40)->post($url, [
                'file_url' => $primaryUrl,
                'description' => $caption,
                'access_token' => $pageAccessToken,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'id' => $response->json()['id'] ?? null,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $this->parseMetaError($response),
            ];
        }

        // Jika Foto: POST /{page_id}/photos
        if ($primaryUrl) {
            $url = "{$this->getGraphBaseUrl()}/{$pageId}/photos";
            $response = Http::timeout(30)->post($url, [
                'url' => $primaryUrl,
                'caption' => $caption,
                'access_token' => $pageAccessToken,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                return [
                    'success' => true,
                    'id' => $json['post_id'] ?? $json['id'] ?? null,
                    'data' => $json,
                ];
            }

            return [
                'success' => false,
                'error' => $this->parseMetaError($response),
            ];
        }

        // Jika Text Feed biasa: POST /{page_id}/feed
        $url = "{$this->getGraphBaseUrl()}/{$pageId}/feed";
        $response = Http::timeout(20)->post($url, [
            'message' => $caption,
            'access_token' => $pageAccessToken,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'id' => $response->json()['id'] ?? null,
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $this->parseMetaError($response),
        ];
    }

    /**
     * Test koneksi ringan ke Graph API (GET /{targetId}?fields=name)
     */
    public function testConnection(string $targetId, string $accessToken): array
    {
        $url = "{$this->getGraphBaseUrl()}/{$targetId}";
        $response = Http::timeout(15)->get($url, [
            'fields' => 'id,name',
            'access_token' => $accessToken,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $this->parseMetaError($response),
        ];
    }

    /**
     * Parse error response dari Meta Graph API secara detail
     */
    public function parseMetaError(Response $response): array
    {
        $json = $response->json();
        $error = $json['error'] ?? [];

        return [
            'message' => $error['message'] ?? $response->body() ?: 'Unknown Meta API error',
            'type' => $error['type'] ?? 'GraphException',
            'code' => $error['code'] ?? $response->status(),
            'error_subcode' => $error['error_subcode'] ?? null,
            'fbtrace_id' => $error['fbtrace_id'] ?? null,
            'user_title' => $error['error_user_title'] ?? null,
            'user_msg' => $error['error_user_msg'] ?? null,
        ];
    }
}