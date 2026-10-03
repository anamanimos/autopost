<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Models\TikTokCredential;
use App\Services\TikTokService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TikTokIntegrationController extends Controller
{
    /**
     * Update Pengaturan Kredensial TikTok App (Client Key & Secret)
     */
    public function updateCredentials(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Hanya Administrator yang dapat mengubah kredensial TikTok.');
        }

        $validated = $request->validate([
            'client_key' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $credential = TikTokCredential::getActive();

        $updateData = [
            'client_key' => $validated['client_key'] ?? $credential->client_key,
            'notes' => $validated['notes'] ?? $credential->notes,
            'status' => !empty($validated['client_key']) ? 'valid' : 'unconfigured',
        ];

        if (!empty($validated['client_secret'])) {
            $updateData['client_secret'] = $validated['client_secret'];
        }

        $credential->update($updateData);

        return redirect()->route('settings.index', ['tab' => 'tiktok'])
            ->with('success', 'Kredensial TikTok API berhasil disimpan.');
    }

    /**
     * Redirect ke halaman otorisasi OAuth TikTok Login
     */
    public function redirectToOAuth(Request $request, TikTokService $tikTokService)
    {
        $credential = TikTokCredential::getActive();
        $clientKey = $credential->getClientKey();

        if (empty($clientKey)) {
            return redirect()->route('settings.index', ['tab' => 'tiktok'])
                ->with('error', 'TikTok Client Key belum diatur. Masukkan Client Key & Client Secret dari TikTok for Developers terlebih dahulu.');
        }

        if ($request->has('account_id')) {
            session(['tiktok_target_account_id' => (int) $request->get('account_id')]);
        }

        $redirectUri = route('tiktok.callback');
        $authUrl = $tikTokService->getAuthorizationUrl($redirectUri);

        return redirect()->away($authUrl);
    }

    /**
     * Menangani callback OAuth TikTok
     */
    public function handleOAuthCallback(Request $request, TikTokService $tikTokService)
    {
        if ($request->has('error')) {
            $errDesc = $request->get('error_description') ?: $request->get('error');
            return redirect()->route('settings.index', ['tab' => 'tiktok'])
                ->with('error', 'Otorisasi TikTok dibatalkan atau ditolak: ' . $errDesc);
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('settings.index', ['tab' => 'tiktok'])
                ->with('error', 'Kode otorisasi dari TikTok tidak ditemukan.');
        }

        $redirectUri = route('tiktok.callback');

        // 1. Tukar authorization code dengan token
        $tokenRes = $tikTokService->exchangeCodeForToken($code, $redirectUri);
        if (!$tokenRes['success']) {
            $msg = $tokenRes['error']['message'] ?? 'Gagal menukar kode otorisasi TikTok.';
            return redirect()->route('settings.index', ['tab' => 'tiktok'])->with('error', $msg);
        }

        $accessToken = $tokenRes['access_token'];
        $refreshToken = $tokenRes['refresh_token'];
        $openId = $tokenRes['open_id'];
        $expiresIn = $tokenRes['expires_in'] ?? 86400;
        $refreshExpiresIn = $tokenRes['refresh_expires_in'] ?? 31536000;

        $tokenExpiresAt = Carbon::now()->addSeconds($expiresIn);
        $refreshTokenExpiresAt = Carbon::now()->addSeconds($refreshExpiresIn);

        // 2. Ambil informasi profil pengguna
        $profileRes = $tikTokService->getUserInfo($accessToken);
        $username = $profileRes['username'] ?? '';
        $displayName = $profileRes['display_name'] ?? $username;
        $avatarUrl = $profileRes['avatar_url'] ?? null;

        // 3. Tautkan ke ConnectedAccount
        $targetAccountId = session()->pull('tiktok_target_account_id');
        $account = null;

        if ($targetAccountId) {
            $account = ConnectedAccount::find($targetAccountId);
        }

        if (!$account && !empty($openId)) {
            $account = ConnectedAccount::where('tiktok_open_id', $openId)->first();
        }

        if (!$account && !empty($username)) {
            // Cocokkan dengan akun yang memiliki username sama
            $account = ConnectedAccount::where('ig_username', $username)
                ->orWhere('threads_username', $username)
                ->first();
        }

        if (!$account) {
            // Buat entitas akun terhubung baru khusus TikTok
            $account = ConnectedAccount::create([
                'page_id' => 'tiktok_' . ($openId ?: time()),
                'page_name' => $displayName ?: ($username ? "@{$username}" : 'Akun TikTok'),
                'is_active' => true,
            ]);
        }

        $account->update([
            'tiktok_open_id' => $openId ?: $account->tiktok_open_id,
            'tiktok_username' => $username ?: $account->tiktok_username,
            'tiktok_display_name' => $displayName ?: $account->tiktok_display_name,
            'tiktok_avatar_url' => $avatarUrl ?: $account->tiktok_avatar_url,
            'tiktok_access_token' => $accessToken,
            'tiktok_refresh_token' => $refreshToken,
            'tiktok_token_expires_at' => $tokenExpiresAt,
            'tiktok_refresh_token_expires_at' => $refreshTokenExpiresAt,
            'last_synced_at' => Carbon::now(),
            'last_verified_at' => Carbon::now(),
        ]);

        return redirect()->route('settings.index', ['tab' => 'tiktok'])
            ->with('success', "Akun TikTok @{$account->tiktok_username} berhasil dihubungkan ke {$account->page_name}.");
    }

    /**
     * Tautkan Akun TikTok Secara Manual (Token & Open ID)
     */
    public function connectManual(Request $request, TikTokService $tikTokService)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Hanya Administrator yang dapat menautkan akun.');
        }

        $validated = $request->validate([
            'account_id' => 'nullable|exists:connected_accounts,id',
            'access_token' => 'required|string',
            'refresh_token' => 'nullable|string',
            'open_id' => 'nullable|string|max:100',
            'username' => 'nullable|string|max:100',
        ]);

        $account = null;
        if (!empty($validated['account_id'])) {
            $account = ConnectedAccount::find($validated['account_id']);
        }

        // Cek profil via token jika belum ada data profil
        $openId = $validated['open_id'] ?? null;
        $username = $validated['username'] ?? null;
        $displayName = $username;
        $avatarUrl = null;

        $profileRes = $tikTokService->getUserInfo($validated['access_token']);
        if ($profileRes['success']) {
            $openId = $profileRes['open_id'] ?: $openId;
            $username = $profileRes['username'] ?: $username;
            $displayName = $profileRes['display_name'] ?: $displayName;
            $avatarUrl = $profileRes['avatar_url'] ?: $avatarUrl;
        }

        if (!$account) {
            $account = ConnectedAccount::create([
                'page_id' => 'tiktok_' . ($openId ?: time()),
                'page_name' => $displayName ?: ($username ? "@{$username}" : 'Akun TikTok Manual'),
                'is_active' => true,
            ]);
        }

        $account->update([
            'tiktok_open_id' => $openId ?: ($account->tiktok_open_id ?: 'tiktok_' . $account->id),
            'tiktok_username' => $username ?: $account->tiktok_username,
            'tiktok_display_name' => $displayName ?: $account->tiktok_display_name,
            'tiktok_avatar_url' => $avatarUrl ?: $account->tiktok_avatar_url,
            'tiktok_access_token' => $validated['access_token'],
            'tiktok_refresh_token' => $validated['refresh_token'] ?? $account->tiktok_refresh_token,
            'tiktok_token_expires_at' => Carbon::now()->addHours(24),
            'last_synced_at' => Carbon::now(),
            'last_verified_at' => Carbon::now(),
        ]);

        return redirect()->route('settings.index', ['tab' => 'tiktok'])
            ->with('success', "Token TikTok berhasil ditautkan ke akun {$account->page_name}.");
    }

    /**
     * Putuskan Koneksi TikTok dari Akun
     */
    public function disconnect(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Hanya Administrator yang dapat memutuskan koneksi.');
        }

        $account = ConnectedAccount::findOrFail($id);
        $account->update([
            'tiktok_open_id' => null,
            'tiktok_username' => null,
            'tiktok_display_name' => null,
            'tiktok_avatar_url' => null,
            'tiktok_access_token' => null,
            'tiktok_refresh_token' => null,
            'tiktok_token_expires_at' => null,
            'tiktok_refresh_token_expires_at' => null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Koneksi TikTok untuk {$account->page_name} berhasil diputuskan.",
            ]);
        }

        return redirect()->route('settings.index', ['tab' => 'tiktok'])
            ->with('success', "Koneksi TikTok untuk {$account->page_name} berhasil diputuskan.");
    }
}
