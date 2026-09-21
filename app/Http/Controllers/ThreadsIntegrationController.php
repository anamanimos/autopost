<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Models\MetaCredential;
use App\Services\ThreadsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ThreadsIntegrationController extends Controller
{
    /**
     * Redirect ke halaman otorisasi OAuth Meta Threads
     */
    public function redirectToOAuth(Request $request, ThreadsService $threadsService)
    {
        $credential = MetaCredential::getActive();
        $appId = $credential->getThreadsAppId();

        if (empty($appId)) {
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'Threads App ID belum diatur. Masukkan Threads App ID dari Meta for Developers (Use Cases > Threads API Access > Settings) pada Pengaturan Kredensial, atau gunakan tombol Token Manual.');
        }

        if ($request->has('account_id')) {
            session(['threads_target_account_id' => (int) $request->get('account_id')]);
        }

        $redirectUri = route('threads.callback');
        $authUrl = $threadsService->getAuthorizationUrl($redirectUri);

        return redirect()->away($authUrl);
    }

    /**
     * Menangani callback OAuth Threads
     */
    public function handleOAuthCallback(Request $request, ThreadsService $threadsService)
    {
        if ($request->has('error')) {
            $errDesc = $request->get('error_description') ?: $request->get('error');
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'Otorisasi Threads dibatalkan atau ditolak: ' . $errDesc);
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'Kode otorisasi dari Threads tidak ditemukan.');
        }

        $redirectUri = route('threads.callback');

        // 1. Tukar code dengan Short-Lived Token
        $tokenRes = $threadsService->exchangeCodeForToken($code, $redirectUri);
        if (!$tokenRes['success']) {
            $msg = $tokenRes['error']['message'] ?? 'Gagal menukar kode otorisasi Threads.';
            return redirect()->route('settings.index', ['tab' => 'meta'])->with('error', $msg);
        }

        $shortLivedToken = $tokenRes['access_token'];

        // 2. Tukar Short-Lived Token dengan Long-Lived Token (~60 hari)
        $longLivedRes = $threadsService->exchangeForLongLivedToken($shortLivedToken);
        $accessToken = $longLivedRes['success'] ? $longLivedRes['access_token'] : $shortLivedToken;
        $expiresIn = $longLivedRes['expires_in'] ?? 5184000;
        $expiresAt = Carbon::now()->addSeconds($expiresIn);

        // 3. Ambil profil pengguna Threads
        $profileRes = $threadsService->getUserProfile($accessToken);
        if (!$profileRes['success']) {
            $msg = $profileRes['error']['message'] ?? 'Gagal mengambil profil akun Threads.';
            return redirect()->route('settings.index', ['tab' => 'meta'])->with('error', $msg);
        }

        $threadsUserId = (string) $profileRes['id'];
        $threadsUsername = $profileRes['username'] ?? '';
        $threadsProfilePic = $profileRes['threads_profile_picture_url'] ?? null;

        // 4. Ambil kuota publikasi Threads
        $quotaUsage = 0;
        $quotaTotal = 250;
        $limitRes = $threadsService->getPublishingLimit($threadsUserId, $accessToken);
        if ($limitRes['success']) {
            $quotaUsage = $limitRes['quota_usage'] ?? 0;
            $quotaTotal = $limitRes['config']['quota_total'] ?? 250;
        }

        // 5. Tautkan ke ConnectedAccount
        $targetAccountId = session()->pull('threads_target_account_id');
        $account = null;

        if ($targetAccountId) {
            $account = ConnectedAccount::find($targetAccountId);
        }

        if (!$account && !empty($threadsUsername)) {
            // Cocokkan dengan akun yang memiliki IG username sama
            $account = ConnectedAccount::where('ig_username', $threadsUsername)->first();
        }

        if (!$account) {
            // Ambil akun aktif pertama yang belum memiliki Threads
            $account = ConnectedAccount::where('is_active', true)->whereNull('threads_user_id')->first()
                ?: ConnectedAccount::where('is_active', true)->first();
        }

        if (!$account) {
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'Tidak ada akun Facebook / Instagram terdaftar untuk ditautkan dengan Threads (@' . $threadsUsername . ').');
        }

        $account->update([
            'threads_user_id' => $threadsUserId,
            'threads_username' => $threadsUsername,
            'threads_profile_picture_url' => $threadsProfilePic,
            'threads_access_token' => $accessToken,
            'threads_token_expires_at' => $expiresAt,
            'threads_publishing_quota_usage' => $quotaUsage,
            'threads_publishing_quota_total' => $quotaTotal,
            'last_verified_at' => Carbon::now(),
        ]);

        return redirect()->route('settings.index', ['tab' => 'meta'])
            ->with('success', "Akun Threads @{$threadsUsername} berhasil terhubung dengan akun {$account->page_name}!");
    }

    /**
     * Simpan Token Threads secara manual
     */
    public function saveManualToken(Request $request, ThreadsService $threadsService)
    {
        $request->validate([
            'account_id' => 'required|exists:connected_accounts,id',
            'threads_access_token' => 'required|string',
            'threads_user_id' => 'nullable|string',
        ]);

        $account = ConnectedAccount::findOrFail($request->account_id);
        $token = trim($request->threads_access_token);

        // Coba periksa profil Threads
        $profileRes = $threadsService->getUserProfile($token);
        $threadsUserId = $request->threads_user_id;
        $threadsUsername = null;
        $threadsProfilePic = null;

        if ($profileRes['success']) {
            $threadsUserId = (string) $profileRes['id'];
            $threadsUsername = $profileRes['username'] ?? null;
            $threadsProfilePic = $profileRes['threads_profile_picture_url'] ?? null;
        }

        if (empty($threadsUserId)) {
            // Fallback gunakan user ID dari request atau dari IG user ID jika sama
            $threadsUserId = $account->ig_user_id;
        }

        if (empty($threadsUserId)) {
            return response()->json([
                'success' => false,
                'message' => 'Token Threads tidak valid atau Threads User ID tidak dapat dideteksi.',
            ], 422);
        }

        // Coba perpanjang token jika merupakan short-lived token
        $expiresAt = Carbon::now()->addDays(60);
        $exchangeRes = $threadsService->exchangeForLongLivedToken($token);
        if ($exchangeRes['success'] && !empty($exchangeRes['access_token'])) {
            $token = $exchangeRes['access_token'];
            $expiresIn = $exchangeRes['expires_in'] ?? 5184000;
            $expiresAt = Carbon::now()->addSeconds($expiresIn);
        }

        // Ambil kuota limit
        $quotaUsage = 0;
        $quotaTotal = 250;
        $limitRes = $threadsService->getPublishingLimit($threadsUserId, $token);
        if ($limitRes['success']) {
            $quotaUsage = $limitRes['quota_usage'] ?? 0;
            $quotaTotal = $limitRes['config']['quota_total'] ?? 250;
        }

        $account->update([
            'threads_user_id' => $threadsUserId,
            'threads_username' => $threadsUsername ?: $account->ig_username,
            'threads_profile_picture_url' => $threadsProfilePic ?: $account->ig_profile_picture_url,
            'threads_access_token' => $token,
            'threads_token_expires_at' => $expiresAt,
            'threads_publishing_quota_usage' => $quotaUsage,
            'threads_publishing_quota_total' => $quotaTotal,
            'last_verified_at' => Carbon::now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Token Threads berhasil disimpan dan akun berhasil ditautkan.',
                'account' => $account->fresh(),
            ]);
        }

        return redirect()->route('settings.index', ['tab' => 'meta'])
            ->with('success', 'Token Threads berhasil disimpan dan diverifikasi.');
    }

    /**
     * Putuskan koneksi akun Threads dari ConnectedAccount
     */
    public function disconnect(Request $request, $id)
    {
        $account = ConnectedAccount::findOrFail($id);

        $account->update([
            'threads_user_id' => null,
            'threads_username' => null,
            'threads_profile_picture_url' => null,
            'threads_access_token' => null,
            'threads_token_expires_at' => null,
            'threads_publishing_quota_usage' => 0,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Koneksi Threads untuk {$account->page_name} berhasil diputuskan.",
            ]);
        }

        return redirect()->route('settings.index', ['tab' => 'meta'])
            ->with('success', "Koneksi Threads untuk {$account->page_name} berhasil diputuskan.");
    }

    /**
     * Uji koneksi Threads untuk akun tertentu
     */
    public function testConnection(Request $request, $id, ThreadsService $threadsService)
    {
        $account = ConnectedAccount::findOrFail($id);

        if (!$account->hasThreads()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini belum memiliki konfigurasi token Threads.',
            ], 404);
        }

        $profileRes = $threadsService->getUserProfile($account->threads_access_token);
        if (!$profileRes['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Token Threads kedaluwarsa atau tidak valid: ' . ($profileRes['error']['message'] ?? 'Unknown error'),
            ], 400);
        }

        $limitRes = $threadsService->getPublishingLimit($account->threads_user_id, $account->threads_access_token);
        if ($limitRes['success']) {
            $account->update([
                'threads_publishing_quota_usage' => $limitRes['quota_usage'] ?? $account->threads_publishing_quota_usage,
                'threads_publishing_quota_total' => $limitRes['config']['quota_total'] ?? $account->threads_publishing_quota_total,
                'last_verified_at' => Carbon::now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Koneksi Threads @{$profileRes['username']} aktif dan valid!",
            'profile' => $profileRes,
            'limit' => $limitRes,
        ]);
    }
}
