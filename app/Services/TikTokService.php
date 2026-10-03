<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use App\Models\TikTokCredential;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokService
{
    protected ?string $clientKey;
    protected ?string $clientSecret;

    public const REQUIRED_SCOPES = [
        'user.info.basic',
        'video.publish',
        'video.upload',
    ];

    public function __construct(?TikTokCredential $credential = null)
    {
        $cred = $credential ?? TikTokCredential::getActive();
        $this->clientKey = $cred->getClientKey();
        $this->clientSecret = $cred->getClientSecret();
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientKey) && !empty($this->clientSecret);
    }

    /**
     * Generate URL Otorisasi OAuth TikTok Login
     */
    public function getAuthorizationUrl(string $redirectUri, string $state = ''): string
    {
        $params = [
            'client_key' => $this->clientKey,
            'scope' => implode(',', self::REQUIRED_SCOPES),
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state ?: csrf_token(),
        ];

        return 'https://www.tiktok.com/v2/auth/authorize/?' . http_build_query($params);
    }

    /**
     * Tukar kode otorisasi dengan access_token dan refresh_token
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        $url = 'https://open.tiktokapis.com/v2/oauth/token/';

        try {
            $response = Http::asForm()->timeout(30)->post($url, [
                'client_key' => $this->clientKey,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]);

            $json = $response->json();
            $tokenData = !empty($json['data']['access_token']) 
                ? $json['data'] 
                : (!empty($json['access_token']) ? $json : null);

            if ($response->successful() && !empty($tokenData['access_token'])) {
                return [
                    'success' => true,
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'open_id' => $tokenData['open_id'] ?? '',
                    'expires_in' => $tokenData['expires_in'] ?? 86400,
                    'refresh_expires_in' => $tokenData['refresh_expires_in'] ?? 31536000,
                    'scope' => $tokenData['scope'] ?? '',
                    'data' => $tokenData,
                ];
            }

            $rawBody = $response->body();
            $status = $response->status();
            $data = is_array($json) ? $json : [];

            $errCode = is_string($data['error'] ?? null) 
                ? $data['error'] 
                : ($data['error']['code'] ?? $data['error_code'] ?? $data['code'] ?? null);

            $errDesc = $data['error_description'] 
                ?? $data['error']['message'] 
                ?? $data['description']
                ?? $data['message'] 
                ?? null;

            if ($errDesc && $errCode && $errCode !== $errDesc) {
                $errMsg = "Gagal otorisasi TikTok [{$errCode}]: {$errDesc}";
            } elseif ($errDesc) {
                $errMsg = "Gagal otorisasi TikTok: {$errDesc}";
            } elseif ($errCode) {
                $errMsg = "Gagal otorisasi TikTok [{$errCode}].";
            } elseif ($rawBody) {
                $errMsg = "Gagal menukar kode otorisasi TikTok (HTTP {$status}): " . \Illuminate\Support\Str::limit($rawBody, 200);
            } else {
                $errMsg = "Gagal menukar kode otorisasi TikTok (HTTP {$status}): Respons kosong dari TikTok.";
            }

            Log::warning('TikTok token exchange failed', [
                'status' => $status,
                'response_body' => $rawBody,
                'json' => $json,
                'client_key' => $this->clientKey,
                'redirect_uri' => $redirectUri,
            ]);

            return [
                'success' => false,
                'error' => [
                    'message' => $errMsg,
                    'code' => $errCode,
                    'raw' => $json,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('TikTok exchangeCodeForToken Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Perbarui Access Token menggunakan Refresh Token
     */
    public function refreshToken(string $refreshToken): array
    {
        $url = 'https://open.tiktokapis.com/v2/oauth/token/';

        try {
            $response = Http::asForm()->timeout(30)->post($url, [
                'client_key' => $this->clientKey,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

            $json = $response->json();

            $tokenData = !empty($json['data']['access_token']) 
                ? $json['data'] 
                : (!empty($json['access_token']) ? $json : null);

            if ($response->successful() && !empty($tokenData['access_token'])) {
                return [
                    'success' => true,
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? $refreshToken,
                    'open_id' => $tokenData['open_id'] ?? '',
                    'expires_in' => $tokenData['expires_in'] ?? 86400,
                    'refresh_expires_in' => $tokenData['refresh_expires_in'] ?? 31536000,
                    'data' => $tokenData,
                ];
            }

            $errCode = is_string($json['error'] ?? null) ? $json['error'] : ($json['error']['code'] ?? null);
            $errDesc = $json['error_description'] 
                ?? $json['error']['message'] 
                ?? $json['message'] 
                ?? null;

            if ($errDesc && $errCode && $errCode !== $errDesc) {
                $errMsg = "Gagal memperbarui token TikTok [{$errCode}]: {$errDesc}";
            } elseif ($errDesc) {
                $errMsg = "Gagal memperbarui token TikTok: {$errDesc}";
            } else {
                $errMsg = 'Gagal memperbarui token TikTok.';
            }

            Log::warning('TikTok token refresh failed', ['response' => $json, 'status' => $response->status()]);
            return [
                'success' => false,
                'error' => ['message' => $errMsg, 'code' => $errCode],
            ];
        } catch (\Throwable $e) {
            Log::error('TikTok refreshToken Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Ambil Informasi Profil Akun TikTok Pengguna
     */
    public function getUserInfo(string $accessToken): array
    {
        $url = 'https://open.tiktokapis.com/v2/user/info/';

        try {
            $response = Http::withToken($accessToken)
                ->timeout(20)
                ->get($url, [
                    'fields' => 'open_id,union_id,avatar_url,display_name,username',
                ]);

            $json = $response->json();

            $user = $json['data']['user'] ?? $json['user'] ?? null;
            if ($response->successful() && !empty($user)) {
                return [
                    'success' => true,
                    'open_id' => $user['open_id'] ?? '',
                    'union_id' => $user['union_id'] ?? null,
                    'avatar_url' => $user['avatar_url'] ?? null,
                    'display_name' => $user['display_name'] ?? '',
                    'username' => $user['username'] ?? '',
                ];
            }

            $errMsg = $json['error']['message'] ?? 'Gagal mengambil data profil TikTok.';
            return ['success' => false, 'error' => ['message' => $errMsg]];
        } catch (\Throwable $e) {
            Log::error('TikTok getUserInfo Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Memastikan Token Masih Valid (Auto-refresh jika kadaluarsa)
     */
    public function ensureValidAccessToken(ConnectedAccount $account): ?string
    {
        if (empty($account->tiktok_access_token)) {
            return null;
        }

        $now = Carbon::now();
        $isExpiringSoon = $account->tiktok_token_expires_at && $account->tiktok_token_expires_at->subMinutes(10)->lessThanOrEqualTo($now);

        if ($isExpiringSoon && !empty($account->tiktok_refresh_token)) {
            Log::info("TikTok access_token untuk akun #{$account->id} ({$account->tiktok_username}) mendekati kadaluarsa. Memperbarui token...");
            $refreshed = $this->refreshToken($account->tiktok_refresh_token);

            if ($refreshed['success']) {
                $account->update([
                    'tiktok_access_token' => $refreshed['access_token'],
                    'tiktok_refresh_token' => $refreshed['refresh_token'],
                    'tiktok_token_expires_at' => Carbon::now()->addSeconds($refreshed['expires_in']),
                    'tiktok_refresh_token_expires_at' => Carbon::now()->addSeconds($refreshed['refresh_expires_in']),
                    'last_synced_at' => Carbon::now(),
                ]);
                return $refreshed['access_token'];
            } else {
                Log::error("Gagal auto-refresh token TikTok untuk akun #{$account->id}: " . ($refreshed['error']['message'] ?? 'Unknown error'));
            }
        }

        return $account->tiktok_access_token;
    }

    /**
     * Publikasikan Video via PULL_FROM_URL
     */
    public function publishVideoPost(string $accessToken, string $videoUrl, string $caption = '', array $options = []): array
    {
        $url = 'https://open.tiktokapis.com/v2/post/publish/video/init/';
        $privacyLevel = $options['privacy_level'] ?? 'PUBLIC_TO_EVERYONE';

        $payload = [
            'post_info' => [
                'title' => mb_substr($caption, 0, 2200),
                'privacy_level' => $privacyLevel,
                'disable_duet' => false,
                'disable_stitch' => false,
                'disable_comment' => false,
                'video_cover_timestamp_ms' => 1000,
            ],
            'source_info' => [
                'source' => 'PULL_FROM_URL',
                'video_url' => $videoUrl,
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
                ->timeout(45)
                ->post($url, $payload);

            $json = $response->json();

            if ($response->successful() && !empty($json['data']['publish_id'])) {
                $publishId = (string) $json['data']['publish_id'];

                // Verifikasi status inisialisasi awal
                return [
                    'success' => true,
                    'id' => $publishId,
                    'publish_id' => $publishId,
                    'data' => $json['data'],
                ];
            }

            $errCode = is_string($json['error'] ?? null)
                ? $json['error']
                : ($json['error']['code'] ?? $json['code'] ?? null);

            // Auto-fallback: Jika aplikasi TikTok developer masih berstatus unaudited / sandbox,
            // TikTok hanya mengizinkan posting ke akun privat dengan privacy_level = SELF_ONLY.
            if ($errCode === 'unaudited_client_can_only_post_to_private_accounts' && $privacyLevel !== 'SELF_ONLY') {
                Log::info('TikTok unaudited client restriction terdeteksi. Mencoba inisialisasi video dengan privacy_level=SELF_ONLY...');
                $retryOptions = array_merge($options, ['privacy_level' => 'SELF_ONLY']);
                $retryResult = $this->publishVideoPost($accessToken, $videoUrl, $caption, $retryOptions);
                if ($retryResult['success']) {
                    return $retryResult;
                }
                $json = $retryResult['error']['response'] ?? $json;
                $errCode = is_string($json['error'] ?? null)
                    ? $json['error']
                    : ($json['error']['code'] ?? $json['code'] ?? $errCode);
            }

            $errMsg = $json['error']['message'] ?? $json['message'] ?? 'Gagal menginisialisasi postingan video TikTok.';
            $errMsg = $this->formatTikTokErrorMessage($errCode, $errMsg, $json);

            Log::error('TikTok publishVideoPost Error', ['response' => $json, 'url' => $videoUrl]);
            return [
                'success' => false,
                'error' => [
                    'message' => $errMsg,
                    'code' => $errCode,
                    'response' => $json,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('TikTok publishVideoPost Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Publikasikan Konten Foto (TikTok Photo Mode) via PULL_FROM_URL
     */
    public function publishPhotoPost(string $accessToken, array $imageUrls, string $caption = '', array $options = []): array
    {
        $url = 'https://open.tiktokapis.com/v2/post/publish/content/init/';
        $privacyLevel = $options['privacy_level'] ?? 'PUBLIC_TO_EVERYONE';

        $payload = [
            'post_info' => [
                'title' => mb_substr($caption, 0, 150),
                'description' => mb_substr($caption, 0, 2200),
                'privacy_level' => $privacyLevel,
            ],
            'source_info' => [
                'source' => 'PULL_FROM_URL',
                'photo_cover_index' => 1,
                'photo_images' => array_values($imageUrls),
            ],
            'post_mode' => 'DIRECT_POST',
            'media_type' => 'PHOTO',
        ];

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
                ->timeout(45)
                ->post($url, $payload);

            $json = $response->json();

            if ($response->successful() && !empty($json['data']['publish_id'])) {
                $publishId = (string) $json['data']['publish_id'];
                return [
                    'success' => true,
                    'id' => $publishId,
                    'publish_id' => $publishId,
                    'data' => $json['data'],
                ];
            }

            $errCode = is_string($json['error'] ?? null)
                ? $json['error']
                : ($json['error']['code'] ?? $json['code'] ?? null);

            // Auto-fallback: Jika aplikasi TikTok developer masih berstatus unaudited / sandbox,
            // TikTok hanya mengizinkan posting ke akun privat dengan privacy_level = SELF_ONLY.
            if ($errCode === 'unaudited_client_can_only_post_to_private_accounts' && $privacyLevel !== 'SELF_ONLY') {
                Log::info('TikTok unaudited client restriction terdeteksi. Mencoba inisialisasi foto dengan privacy_level=SELF_ONLY...');
                $retryOptions = array_merge($options, ['privacy_level' => 'SELF_ONLY']);
                $retryResult = $this->publishPhotoPost($accessToken, $imageUrls, $caption, $retryOptions);
                if ($retryResult['success']) {
                    return $retryResult;
                }
                $json = $retryResult['error']['response'] ?? $json;
                $errCode = is_string($json['error'] ?? null)
                    ? $json['error']
                    : ($json['error']['code'] ?? $json['code'] ?? $errCode);
            }

            $errMsg = $json['error']['message'] ?? $json['message'] ?? 'Gagal menginisialisasi postingan foto TikTok.';
            $errMsg = $this->formatTikTokErrorMessage($errCode, $errMsg, $json);

            Log::error('TikTok publishPhotoPost Error', ['response' => $json]);
            return [
                'success' => false,
                'error' => [
                    'message' => $errMsg,
                    'code' => $errCode,
                    'response' => $json,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('TikTok publishPhotoPost Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Ambil Informasi Creator (Limit durasi, izin privasi, opsi komentar)
     */
    public function getCreatorInfo(string $accessToken): array
    {
        $url = 'https://open.tiktokapis.com/v2/post/publish/creator_info/query/';

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
                ->timeout(20)
                ->post($url, new \stdClass());

            $json = $response->json();

            if ($response->successful() && isset($json['data'])) {
                return [
                    'success' => true,
                    'data' => $json['data'],
                    'privacy_level_options' => $json['data']['privacy_level_options'] ?? [],
                    'max_video_post_duration_sec' => $json['data']['max_video_post_duration_sec'] ?? 0,
                    'comment_disabled' => $json['data']['comment_disabled'] ?? false,
                ];
            }

            Log::warning('TikTok getCreatorInfo Warning', ['response' => $json]);
            return [
                'success' => false,
                'error' => $json['error'] ?? ['message' => 'Gagal mengambil informasi creator TikTok.'],
            ];
        } catch (\Throwable $e) {
            Log::error('TikTok getCreatorInfo Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Terjemahkan dan berikan panduan solutif untuk kode error TikTok
     */
    protected function formatTikTokErrorMessage(?string $errCode, string $defaultMsg, array $json = []): string
    {
        if (empty($errCode)) {
            return $defaultMsg;
        }

        return match ($errCode) {
            'unaudited_client_can_only_post_to_private_accounts' => "Aplikasi TikTok masih tahap pengujian (Sandbox / Belum Diaudit). TikTok mewajibkan: 1) Akun TikTok target disetel sebagai 'Akun Privat' di aplikasi ponsel (Pengaturan & Privasi > Privasi > Akun Privat = Aktif). 2) Visibilitas postingan dibatasi ke 'Hanya Anda' (SELF_ONLY) hingga aplikasi lolos audit TikTok.",
            'url_ownership_unverified' => "Domain media belum diverifikasi di TikTok Developer Portal. Buka https://developers.tiktok.com > Aplikasi Anda > URL properties, lalu tambahkan dan verifikasi domain web Anda.",
            'privacy_level_option_mismatch' => "Tingkat privasi yang dipilih tidak diizinkan untuk akun TikTok ini. Pastikan menggunakan opsi privasi yang diizinkan (misal: SELF_ONLY untuk sandbox).",
            'scope_not_authorized', 'scope_permission_missed' => "Aplikasi TikTok belum memiliki izin (scope) yang diperlukan. Pastikan scope 'video.publish' dan 'video.upload' telah diaktifkan di TikTok Developer Portal.",
            'invalid_file_upload' => "Format file media ditolak oleh TikTok. Pastikan foto berformat JPG/JPEG atau WebP (PNG tidak didukung TikTok), dan video berformat MP4/MOV.",
            'access_token_invalid' => "Token akses TikTok kadaluarsa atau tidak valid. Silakan hubungkan ulang akun TikTok di Pengaturan Akun.",
            'rate_limit_exceeded' => "Batas frekuensi posting TikTok tercapai. Silakan coba lagi beberapa saat lagi.",
            default => !empty($json['error']['message']) ? "Gagal dari TikTok [{$errCode}]: " . $json['error']['message'] : $defaultMsg,
        };
    }

    /**
     * Cek Status Penerbitan Konten TikTok
     */
    public function checkPublishStatus(string $accessToken, string $publishId): array
    {
        $url = 'https://open.tiktokapis.com/v2/post/publish/status/fetch/';

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
                ->timeout(25)
                ->post($url, [
                    'publish_id' => $publishId,
                ]);

            $json = $response->json();

            if ($response->successful() && isset($json['data']['status'])) {
                return [
                    'success' => true,
                    'status' => $json['data']['status'], // PROCESSING_DOWNLOAD, PROCESSING_UPLOAD, SUCCESS, FAILED
                    'fail_reason' => $json['data']['fail_reason'] ?? null,
                    'data' => $json['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $json['error'] ?? ['message' => 'Gagal memeriksa status publikasi TikTok.'],
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => ['message' => $e->getMessage()]];
        }
    }

    /**
     * Koordinator Utama Publikasi Postingan TikTok
     */
    public function publishTikTokPost(ConnectedAccount $account, array $mediaUrls, string $caption, bool $isVideo = false, array $options = []): array
    {
        if (!$account->hasTikTok()) {
            return [
                'success' => false,
                'error' => ['message' => 'Akun belum terhubung ke TikTok API.'],
            ];
        }

        $accessToken = $this->ensureValidAccessToken($account);
        if (empty($accessToken)) {
            return [
                'success' => false,
                'error' => ['message' => 'Access token TikTok tidak ditemukan atau gagal diperbarui.'],
            ];
        }

        if (empty($mediaUrls)) {
            return [
                'success' => false,
                'error' => ['message' => 'TikTok mewajibkan aset media (video atau foto). Postingan teks murni tidak didukung.'],
            ];
        }

        // Pastikan seluruh URL media menggunakan protokol HTTPS (TikTok mewajibkan HTTPS langsung tanpa redirect)
        $mediaUrls = array_map(function ($url) {
            return preg_replace('/^http:\/\//i', 'https://', trim($url));
        }, $mediaUrls);

        // Pastikan URL publik dapat dijangkau
        $primaryUrl = $mediaUrls[0];
        $isActualVideo = $isVideo;
        if (!$isActualVideo) {
            $ext = strtolower(pathinfo(parse_url($primaryUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (in_array($ext, ['mp4', 'mov', 'webm', 'mkv'])) {
                $isActualVideo = true;
            }
        }

        // Cek izin privasi dari creator_info agar privacy_level sesuai dengan akun pengguna
        $creatorInfo = $this->getCreatorInfo($accessToken);
        if ($creatorInfo['success'] && !empty($creatorInfo['privacy_level_options'])) {
            $allowedPrivacy = $creatorInfo['privacy_level_options'];
            $currentPrivacy = $options['privacy_level'] ?? 'PUBLIC_TO_EVERYONE';

            if (!in_array($currentPrivacy, $allowedPrivacy)) {
                if (in_array('SELF_ONLY', $allowedPrivacy)) {
                    $options['privacy_level'] = 'SELF_ONLY';
                } elseif (in_array('MUTUAL_FOLLOW_FRIENDS', $allowedPrivacy)) {
                    $options['privacy_level'] = 'MUTUAL_FOLLOW_FRIENDS';
                } elseif (in_array('FOLLOWER_OF_CREATOR', $allowedPrivacy)) {
                    $options['privacy_level'] = 'FOLLOWER_OF_CREATOR';
                } else {
                    $options['privacy_level'] = $allowedPrivacy[0];
                }
                Log::info("Penyesuaian privacy_level TikTok ke '{$options['privacy_level']}' berdasarkan izin creator_info.");
            }
        }

        if ($isActualVideo) {
            return $this->publishVideoPost($accessToken, $primaryUrl, $caption, $options);
        }

        // Foto / Carousel Mode
        $res = $this->publishPhotoPost($accessToken, $mediaUrls, $caption, $options);
        if (!$res['success']) {
            // Jika direct photo mode belum aktif pada app TikTok, berikan pesan panduan yang jelas
            $msg = $res['error']['message'] ?? '';
            if (stripos($msg, 'scope') !== false || stripos($msg, 'permission') !== false) {
                $res['error']['message'] = 'Format foto TikTok memerlukan izin akses Photo Mode pada TikTok Developer App, atau unggah dalam format video.';
            }
        }

        return $res;
    }
}
