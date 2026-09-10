<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Models\MetaCredential;
use App\Models\TokenActivityLog;
use App\Services\MetaGraphService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class MetaIntegrationController extends Controller
{
    public function index()
    {
        return redirect()->route('settings.index', ['tab' => 'meta']);
    }

    public function updateCredentials(Request $request)
    {
        $request->validate([
            'app_id' => 'required|string',
            'app_secret' => 'nullable|string',
            'graph_version' => 'required|string',
            'webhook_verify_token' => 'nullable|string',
        ]);

        $credential = MetaCredential::getActive();
        $data = [
            'app_id' => trim($request->app_id),
            'graph_version' => trim($request->graph_version),
            'webhook_verify_token' => $request->webhook_verify_token ? trim($request->webhook_verify_token) : null,
        ];

        if ($request->filled('app_secret')) {
            $data['app_secret'] = trim($request->app_secret);
        }

        $credential->update($data);

        return redirect()->route('settings.index', ['tab' => 'meta'])->with('success', 'Kredensial Meta App berhasil disimpan!');
    }

    public function saveManualToken(Request $request, MetaGraphService $metaService)
    {
        $request->validate([
            'access_token' => 'required|string',
            'token_type' => 'required|in:oauth_user,system_user',
        ]);

        $credential = MetaCredential::getActive();
        $token = trim($request->access_token);
        $type = $request->token_type;

        // Cek validitas token via /debug_token
        $debugRes = $metaService->debugToken($token);

        $expiresAt = null;
        $status = 'valid';

        if ($debugRes['success']) {
            $debugData = $debugRes['data'];
            if (!empty($debugData['expires_at'])) {
                $expiresAt = Carbon::createFromTimestamp($debugData['expires_at']);
            }
            if (isset($debugData['is_valid']) && !$debugData['is_valid']) {
                $status = 'expired';
            }
        }

        if ($type === 'system_user') {
            $credential->update([
                'system_user_token' => $token,
                'token_type' => 'system_user',
                'token_expires_at' => $expiresAt,
                'token_status' => $status,
                'last_verified_at' => Carbon::now(),
            ]);
        } else {
            // Otomatis tukar ke Long-Lived Token (~60 hari) jika token berasal dari Graph API Explorer
            $exchangeRes = $metaService->exchangeForLongLivedToken($token);
            if ($exchangeRes['success'] && !empty($exchangeRes['data']['access_token'])) {
                $token = $exchangeRes['data']['access_token'];
                $expiresIn = $exchangeRes['data']['expires_in'] ?? null;
                if ($expiresIn) {
                    $expiresAt = Carbon::now()->addSeconds($expiresIn);
                }
            }

            $credential->update([
                'user_access_token' => $token,
                'token_type' => 'oauth_user',
                'token_expires_at' => $expiresAt,
                'token_status' => $status,
                'last_verified_at' => Carbon::now(),
            ]);
        }

        TokenActivityLog::create([
            'action' => 'manual_token_input',
            'status' => 'success',
            'details' => "Token ({$type}) berhasil disimpan dan diverifikasi. Status: {$status}.",
        ]);

        // Auto Sync Accounts
        Artisan::call('meta:sync-accounts');

        return redirect()->route('settings.index', ['tab' => 'meta'])->with('success', 'Token berhasil disimpan! Akun Meta sedang disinkronisasi.');
    }

    public function redirectToOAuth(MetaGraphService $metaService)
    {
        $credential = MetaCredential::getActive();
        if (empty($credential->app_id)) {
            return redirect()->route('settings.index', ['tab' => 'meta'])->with('error', 'Harap isi Meta App ID terlebih dahulu sebelum login.');
        }

        $callbackUrl = route('meta.callback');
        $authUrl = $metaService->getAuthorizationUrl($callbackUrl);

        return redirect()->away($authUrl);
    }

    public function handleOAuthCallback(Request $request, MetaGraphService $metaService)
    {
        if ($request->has('error')) {
            $errMsg = $request->get('error_description') ?: $request->get('error_message') ?: 'Autentikasi dibatalkan atau ditolak oleh pengguna.';
            TokenActivityLog::create([
                'action' => 'oauth_connect',
                'status' => 'failed',
                'details' => $errMsg,
            ]);
            return redirect()->route('settings.index', ['tab' => 'meta'])->with('error', "Gagal login dengan Facebook: {$errMsg}");
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('settings.index', ['tab' => 'meta'])->with('error', 'Tidak ada authorization code yang diterima dari Meta.');
        }

        $callbackUrl = route('meta.callback');

        // 1. Tukar Code dengan Short-Lived User Token
        $shortRes = $metaService->exchangeCodeForToken($code, $callbackUrl);
        if (!$shortRes['success']) {
            $err = $shortRes['error']['message'] ?? 'Gagal menukar code authorization';
            TokenActivityLog::create([
                'action' => 'oauth_connect',
                'status' => 'failed',
                'details' => $err,
            ]);
            return redirect()->route('settings.index', ['tab' => 'meta'])->with('error', "Gagal mendapatkan token: {$err}");
        }

        $shortLivedToken = $shortRes['data']['access_token'] ?? null;

        // 2. Tukar Short-Lived dengan Long-Lived Token (~60 hari)
        $longRes = $metaService->exchangeForLongLivedToken($shortLivedToken);
        $finalToken = $shortLivedToken;
        $expiresIn = $shortRes['data']['expires_in'] ?? null;

        if ($longRes['success'] && !empty($longRes['data']['access_token'])) {
            $finalToken = $longRes['data']['access_token'];
            $expiresIn = $longRes['data']['expires_in'] ?? (60 * 86400); // 60 hari
        }

        $expiresAt = $expiresIn ? Carbon::now()->addSeconds($expiresIn) : Carbon::now()->addDays(60);

        $credential = MetaCredential::getActive();
        $credential->update([
            'user_access_token' => $finalToken,
            'token_type' => 'oauth_user',
            'token_expires_at' => $expiresAt,
            'token_status' => 'valid',
            'last_verified_at' => Carbon::now(),
        ]);

        TokenActivityLog::create([
            'action' => 'oauth_connect',
            'status' => 'success',
            'details' => 'OAuth Facebook Login for Business berhasil. Long-Lived User Token (~60 hari) tersimpan.',
        ]);

        // 3. Jalankan Full Sync Otomatis untuk menarik Facebook Pages & Instagram Accounts
        Artisan::call('meta:sync-accounts');

        return redirect()->route('settings.index', ['tab' => 'meta'])->with('success', 'Berhasil terhubung dengan Facebook! Pages dan Instagram Accounts telah disinkronkan.');
    }

    public function refreshToken(MetaGraphService $metaService)
    {
        $credential = MetaCredential::getActive();
        $token = $credential->getActiveToken();

        if (empty($token)) {
            return response()->json(['success' => false, 'message' => 'Belum ada token untuk direfresh.'], 400);
        }

        $longRes = $metaService->exchangeForLongLivedToken($token);

        if ($longRes['success'] && !empty($longRes['data']['access_token'])) {
            $newToken = $longRes['data']['access_token'];
            $expiresIn = $longRes['data']['expires_in'] ?? (60 * 86400);
            $expiresAt = Carbon::now()->addSeconds($expiresIn);

            $credential->update([
                'user_access_token' => $newToken,
                'token_expires_at' => $expiresAt,
                'token_status' => 'valid',
                'last_verified_at' => Carbon::now(),
            ]);

            TokenActivityLog::create([
                'action' => 'manual_refresh',
                'status' => 'success',
                'details' => 'Token berhasil direfresh ke long-lived token baru.',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token berhasil diperbarui! Masa aktif diperpanjang hingga ' . $expiresAt->format('d M Y H:i'),
            ]);
        }

        $errMsg = $longRes['error']['message'] ?? 'Gagal merefresh token Meta API';
        TokenActivityLog::create([
            'action' => 'manual_refresh',
            'status' => 'failed',
            'details' => $errMsg,
        ]);

        return response()->json([
            'success' => false,
            'message' => "Gagal merefresh token: {$errMsg}",
        ], 500);
    }

    public function testConnection(Request $request, MetaGraphService $metaService)
    {
        $targetId = $request->get('target_id');
        $account = null;

        if ($targetId) {
            $account = ConnectedAccount::where('page_id', $targetId)->first();
        }

        $cred = MetaCredential::getActive();
        $token = $account ? $account->page_access_token : $cred->getActiveToken();
        $testId = $account ? $account->page_id : 'me';

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token belum tersedia untuk melakukan test.',
            ], 400);
        }

        $start = microtime(true);
        $res = $metaService->testConnection($testId, $token);
        $latencyMs = round((microtime(true) - $start) * 1000);

        if ($res['success']) {
            TokenActivityLog::create([
                'action' => 'test_connection',
                'status' => 'success',
                'details' => "Test koneksi ke {$testId} sukses dalam {$latencyMs}ms.",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Koneksi ke Meta Graph API BERHASIL!',
                'latency_ms' => $latencyMs,
                'data' => $res['data'],
            ]);
        }

        $errMsg = $res['error']['message'] ?? 'Koneksi gagal';
        TokenActivityLog::create([
            'action' => 'test_connection',
            'status' => 'failed',
            'details' => "Test koneksi ke {$testId} gagal: {$errMsg}",
        ]);

        return response()->json([
            'success' => false,
            'message' => "Test koneksi GAGAL: {$errMsg}",
            'error' => $res['error'],
        ], 500);
    }

    public function syncNow()
    {
        try {
            $exitCode = Artisan::call('meta:sync-accounts');
            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sinkronisasi akun Meta berhasil diselesaikan!',
                    'output' => $output,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Sinkronisasi akun gagal: ' . $output,
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteAccount($id)
    {
        $account = ConnectedAccount::findOrFail($id);
        $name = $account->page_name;
        $account->delete();

        return response()->json([
            'success' => true,
            'message' => "Akun '{$name}' berhasil dihapus dari sistem.",
        ]);
    }
}