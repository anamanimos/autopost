<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SSOController extends Controller
{
    /**
     * Redirect user to ERP Damai Jaya OIDC Authorize endpoint.
     */
    public function redirect(Request $request)
    {
        $clientId = config('services.damaijaya.client_id');
        $baseUrl = config('services.damaijaya.base_url');

        if (empty($clientId)) {
            return redirect()->route('login')->with(
                'error',
                'Integrasi SSO Damai Jaya belum dikonfigurasi. Mohon atur SSO_CLIENT_ID di file .env terlebih dahulu.'
            );
        }

        $state = Str::random(40);
        session(['sso_oauth_state' => $state]);

        $redirectUri = config('services.damaijaya.redirect', route('sso.callback'));

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
        ]);

        return redirect()->away("{$baseUrl}/oauth/authorize?{$query}");
    }

    /**
     * Handle incoming callback from ERP Damai Jaya OIDC Provider.
     */
    public function callback(Request $request)
    {
        // 1. Check for error in callback query
        if ($request->has('error')) {
            $errorDesc = $request->input('error_description', $request->input('error'));
            return redirect()->route('login')->with('error', "Otorisasi SSO ditolak: {$errorDesc}");
        }

        $code = $request->input('code');
        if (empty($code)) {
            return redirect()->route('login')->with('error', 'Kode otorisasi SSO tidak ditemukan dari ERP Damai Jaya.');
        }

        // 2. Verify CSRF State
        $savedState = session('sso_oauth_state');
        session()->forget('sso_oauth_state');

        if (empty($savedState) || $savedState !== $request->input('state')) {
            return redirect()->route('login')->with('error', 'Validasi sesi SSO (state) tidak valid atau telah kadaluarsa. Silakan ulangi login.');
        }

        $baseUrl = config('services.damaijaya.base_url');
        $clientId = config('services.damaijaya.client_id');
        $clientSecret = config('services.damaijaya.client_secret');
        $redirectUri = config('services.damaijaya.redirect', route('sso.callback'));

        // 3. Exchange Authorization Code for Token
        try {
            $tokenResponse = Http::asForm()->post("{$baseUrl}/oauth/token", [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ]);
        } catch (\Exception $e) {
            Log::error('SSO Token Exchange Exception: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Gagal terhubung ke server SSO ERP Damai Jaya: ' . $e->getMessage());
        }

        if (! $tokenResponse->successful()) {
            $errorMessage = $tokenResponse->json('error_description') 
                ?? $tokenResponse->json('message') 
                ?? $tokenResponse->json('error') 
                ?? 'Respon tidak valid dari server SSO (' . $tokenResponse->status() . ')';

            Log::error('SSO Token Exchange Failed', [
                'status' => $tokenResponse->status(),
                'body' => $tokenResponse->body(),
            ]);

            return redirect()->route('login')->with('error', "Gagal menukar token SSO: {$errorMessage}");
        }

        $tokenData = $tokenResponse->json();
        $accessToken = $tokenData['access_token'] ?? null;
        $idToken = $tokenData['id_token'] ?? null;

        // 4. Extract User Data from ID Token and/or /oauth/userinfo
        $userData = [];

        // Decode ID Token (JWT Payload)
        if ($idToken) {
            $jwtParts = explode('.', $idToken);
            if (count($jwtParts) >= 2) {
                $payloadJson = base64_decode(strtr($jwtParts[1], '-_', '+/'));
                $claims = json_decode($payloadJson, true);
                if (is_array($claims)) {
                    $userData = array_merge($userData, $claims);
                }
            }
        }

        // Query /oauth/userinfo if access token is available
        if ($accessToken) {
            try {
                $userInfoResponse = Http::withToken($accessToken)->get("{$baseUrl}/oauth/userinfo");
                if ($userInfoResponse->successful() && is_array($userInfoResponse->json())) {
                    $userData = array_merge($userData, $userInfoResponse->json());
                }
            } catch (\Exception $e) {
                Log::warning('SSO /oauth/userinfo request warning: ' . $e->getMessage());
            }
        }

        $email = $userData['email'] ?? null;
        if (empty($email)) {
            return redirect()->route('login')->with('error', 'Identitas email tidak ditemukan pada respon akun SSO ERP Damai Jaya.');
        }

        $ssoId = $userData['sub'] ?? ($userData['id'] ?? null);
        $name = $userData['name'] ?? ($userData['username'] ?? explode('@', $email)[0]);

        // 5. Determine if the user has "superadmin" level in ERP Damai Jaya
        $isSuperadmin = $this->checkIfErpSuperadmin($userData);

        // 6. Look up existing user
        $user = User::where(function ($query) use ($ssoId, $email) {
            if (! empty($ssoId)) {
                $query->where('sso_id', $ssoId);
            }
            $query->orWhere('email', $email);
        })->first();

        // 7. Case A: User NOT registered yet
        if (! $user) {
            if ($isSuperadmin) {
                // Auto register and auto activate as Administrator
                $newUser = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'admin',
                    'status' => 'active',
                    'sso_id' => $ssoId,
                    'sso_provider' => 'damaijaya',
                    'sso_data' => $userData,
                    'approved_at' => now(),
                    'last_login_at' => now(),
                    'last_login_ip' => $request->ip(),
                ]);

                Auth::login($newUser, true);
                $request->session()->regenerate();

                return redirect()->intended(route('projects.index'))->with(
                    'success',
                    "Selamat datang, {$newUser->name}! Akun Superadmin Anda dari ERP Damai Jaya telah otomatis didaftarkan dan diaktifkan sebagai Administrator."
                );
            } else {
                // Register as pending approval by administrator
                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'operator',
                    'status' => 'pending',
                    'sso_id' => $ssoId,
                    'sso_provider' => 'damaijaya',
                    'sso_data' => $userData,
                    'approved_at' => null,
                    'last_login_ip' => $request->ip(),
                ]);

                return redirect()->route('login')->with(
                    'info',
                    "Akun Anda ({$email}) berhasil terdaftar via SSO ERP Damai Jaya. Namun, akun Anda berstatus 'Menunggu Persetujuan' dan harus disetujui (di-ACC) oleh Administrator sebelum dapat digunakan."
                );
            }
        }

        // 8. Case B: User ALREADY registered
        $user->sso_id = $ssoId ?: $user->sso_id;
        $user->sso_provider = 'damaijaya';
        $user->sso_data = $userData;

        // If user is superadmin in ERP, auto-elevate & auto-activate
        if ($isSuperadmin) {
            $user->role = 'admin';
            $user->status = 'active';
            $user->approved_at = $user->approved_at ?? now();
            $user->last_login_at = now();
            $user->last_login_ip = $request->ip();
            $user->save();

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended(route('projects.index'))->with(
                'success',
                "Selamat datang kembali, {$user->name}! (Masuk via SSO Superadmin ERP Damai Jaya)"
            );
        }

        // Non-superadmin existing user status checking
        if ($user->status === 'active') {
            $user->last_login_at = now();
            $user->last_login_ip = $request->ip();
            $user->save();

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended(route('projects.index'))->with(
                'success',
                "Selamat datang kembali, {$user->name}!"
            );
        }

        $user->save();

        if ($user->status === 'pending') {
            return redirect()->route('login')->with(
                'error',
                "Akun Anda ({$email}) sedang menunggu persetujuan (ACC) dari Administrator. Silakan hubungi Administrator untuk aktivasi akun."
            );
        }

        return redirect()->route('login')->with(
            'error',
            "Akun Anda ({$email}) sedang dinonaktifkan. Silakan hubungi Administrator."
        );
    }

    /**
     * Check whether ERP user claims contain superadmin role or level.
     */
    protected function checkIfErpSuperadmin(array $userData): bool
    {
        $fields = [
            $userData['role'] ?? '',
            $userData['level'] ?? '',
            $userData['user_role'] ?? '',
            $userData['role_name'] ?? '',
        ];

        foreach ($fields as $field) {
            if (is_string($field)) {
                $normalized = preg_replace('/[^a-z0-9]/', '', strtolower(trim($field)));
                if ($normalized === 'superadmin') {
                    return true;
                }
            }
        }

        if (! empty($userData['is_superadmin']) && (
            $userData['is_superadmin'] === true || 
            $userData['is_superadmin'] == 1 || 
            $userData['is_superadmin'] === 'true'
        )) {
            return true;
        }

        if (! empty($userData['roles']) && is_array($userData['roles'])) {
            foreach ($userData['roles'] as $r) {
                if (is_string($r)) {
                    $normalized = preg_replace('/[^a-z0-9]/', '', strtolower(trim($r)));
                    if ($normalized === 'superadmin') {
                        return true;
                    }
                }
                if (is_array($r) && isset($r['name'])) {
                    $normalized = preg_replace('/[^a-z0-9]/', '', strtolower(trim($r['name'])));
                    if ($normalized === 'superadmin') {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
