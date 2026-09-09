<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SSOTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.damaijaya.base_url', 'https://app.damaijaya.my.id');
        Config::set('services.damaijaya.client_id', 'test-client-id-uuid');
        Config::set('services.damaijaya.client_secret', 'test-client-secret-123');
        Config::set('services.damaijaya.redirect', 'https://sosmedauto.dj1.my.id/auth/sso/callback');
    }

    public function test_sso_redirect_fails_gracefully_when_client_id_empty(): void
    {
        Config::set('services.damaijaya.client_id', '');

        $response = $this->get(route('sso.redirect'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_sso_redirect_generates_valid_authorization_url_and_session_state(): void
    {
        $response = $this->get(route('sso.redirect'));

        $response->assertStatus(302);
        $redirectUrl = $response->headers->get('Location');

        $this->assertStringStartsWith('https://app.damaijaya.my.id/oauth/authorize', $redirectUrl);
        $this->assertStringContainsString('client_id=test-client-id-uuid', $redirectUrl);
        $this->assertStringContainsString('response_type=code', $redirectUrl);
        $response->assertSessionHas('sso_oauth_state');
    }

    public function test_sso_callback_rejects_error_from_provider(): void
    {
        $response = $this->get(route('sso.callback', [
            'error' => 'access_denied',
            'error_description' => 'Pengguna membatalkan login SSO',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_sso_callback_rejects_invalid_state(): void
    {
        session(['sso_oauth_state' => 'correct-state-token']);

        $response = $this->get(route('sso.callback', [
            'code' => 'sample-auth-code',
            'state' => 'wrong-state-token',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_sso_callback_registers_and_auto_activates_erp_superadmin(): void
    {
        session(['sso_oauth_state' => 'valid-state-123']);

        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss' => 'https://app.damaijaya.my.id',
            'sub' => 'erp-superadmin-001',
            'name' => 'Budi Superadmin',
            'email' => 'budi.super@damaijaya.my.id',
            'role' => 'superadmin',
        ]));
        $fakeIdToken = "{$header}.{$payload}.fakesignature";

        Http::fake([
            'https://app.damaijaya.my.id/oauth/token' => Http::response([
                'access_token' => 'access-token-superadmin',
                'id_token' => $fakeIdToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            'https://app.damaijaya.my.id/oauth/userinfo' => Http::response([
                'sub' => 'erp-superadmin-001',
                'name' => 'Budi Superadmin',
                'email' => 'budi.super@damaijaya.my.id',
                'role' => 'superadmin',
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'code' => 'valid-superadmin-code',
            'state' => 'valid-state-123',
        ]));

        $this->assertAuthenticated();
        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'budi.super@damaijaya.my.id')->first();
        $this->assertNotNull($user);
        $this->assertEquals('admin', $user->role);
        $this->assertEquals('active', $user->status);
        $this->assertEquals('erp-superadmin-001', $user->sso_id);
        $this->assertNotNull($user->approved_at);
    }

    public function test_sso_callback_registers_non_superadmin_as_pending_and_requires_approval(): void
    {
        session(['sso_oauth_state' => 'valid-state-456']);

        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss' => 'https://app.damaijaya.my.id',
            'sub' => 'erp-staff-002',
            'name' => 'Siti Staff',
            'email' => 'siti.staff@damaijaya.my.id',
            'role' => 'staff_gudang',
        ]));
        $fakeIdToken = "{$header}.{$payload}.fakesignature";

        Http::fake([
            'https://app.damaijaya.my.id/oauth/token' => Http::response([
                'access_token' => 'access-token-staff',
                'id_token' => $fakeIdToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            'https://app.damaijaya.my.id/oauth/userinfo' => Http::response([
                'sub' => 'erp-staff-002',
                'name' => 'Siti Staff',
                'email' => 'siti.staff@damaijaya.my.id',
                'role' => 'staff_gudang',
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'code' => 'valid-staff-code',
            'state' => 'valid-state-456',
        ]));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('info');

        $user = User::where('email', 'siti.staff@damaijaya.my.id')->first();
        $this->assertNotNull($user);
        $this->assertEquals('operator', $user->role);
        $this->assertEquals('pending', $user->status);
        $this->assertNull($user->approved_at);
    }

    public function test_sso_callback_allows_approved_active_user_to_login(): void
    {
        $existingUser = User::create([
            'name' => 'Staff Sudah di-ACC',
            'email' => 'staff.acc@damaijaya.my.id',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'status' => 'active',
            'sso_id' => 'erp-staff-003',
            'approved_at' => now(),
        ]);

        session(['sso_oauth_state' => 'valid-state-789']);

        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss' => 'https://app.damaijaya.my.id',
            'sub' => 'erp-staff-003',
            'name' => 'Staff Sudah di-ACC',
            'email' => 'staff.acc@damaijaya.my.id',
            'role' => 'operator',
        ]));
        $fakeIdToken = "{$header}.{$payload}.fakesignature";

        Http::fake([
            'https://app.damaijaya.my.id/oauth/token' => Http::response([
                'access_token' => 'access-token-staff-acc',
                'id_token' => $fakeIdToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'code' => 'valid-staff-acc-code',
            'state' => 'valid-state-789',
        ]));

        $this->assertAuthenticatedAs($existingUser);
        $response->assertRedirect(route('projects.index'));
    }

    public function test_sso_callback_blocks_pending_user_from_logging_in(): void
    {
        $pendingUser = User::create([
            'name' => 'Staff Menunggu ACC',
            'email' => 'staff.pending@damaijaya.my.id',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'status' => 'pending',
            'sso_id' => 'erp-staff-004',
        ]);

        session(['sso_oauth_state' => 'valid-state-999']);

        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss' => 'https://app.damaijaya.my.id',
            'sub' => 'erp-staff-004',
            'name' => 'Staff Menunggu ACC',
            'email' => 'staff.pending@damaijaya.my.id',
            'role' => 'operator',
        ]));
        $fakeIdToken = "{$header}.{$payload}.fakesignature";

        Http::fake([
            'https://app.damaijaya.my.id/oauth/token' => Http::response([
                'access_token' => 'access-token-staff-pending',
                'id_token' => $fakeIdToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'code' => 'valid-staff-pending-code',
            'state' => 'valid-state-999',
        ]));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_approve_pending_user(): void
    {
        $admin = User::create([
            'name' => 'Admin Approver',
            'email' => 'approver@admin.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $pendingUser = User::create([
            'name' => 'Staff Butuh ACC',
            'email' => 'needacc@damaijaya.my.id',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'status' => 'pending',
            'sso_id' => 'erp-staff-005',
        ]);

        $response = $this->actingAs($admin)->post(route('users.approve', $pendingUser->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pendingUser->refresh();
        $this->assertEquals('active', $pendingUser->status);
        $this->assertNotNull($pendingUser->approved_at);
        $this->assertEquals($admin->id, $pendingUser->approved_by);
    }

    public function test_operator_cannot_approve_users(): void
    {
        $operator = User::create([
            'name' => 'Operator Staff',
            'email' => 'operator@test.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'status' => 'active',
        ]);

        $pendingUser = User::create([
            'name' => 'Target User',
            'email' => 'target@test.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($operator)->post(route('users.approve', $pendingUser->id));

        $response->assertStatus(403);
    }

    public function test_sso_callback_recognizes_super_admin_with_spaces_and_auto_activates(): void
    {
        session(['sso_oauth_state' => 'valid-state-space']);

        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss' => 'https://app.damaijaya.my.id',
            'sub' => 999,
            'name' => 'Choirul Anam',
            'email' => 'cranam21@gmail.com',
            'role' => 'Super Admin',
        ]));
        $fakeIdToken = "{$header}.{$payload}.fakesignature";

        Http::fake([
            'https://app.damaijaya.my.id/oauth/token' => Http::response([
                'access_token' => 'access-token-super-admin',
                'id_token' => $fakeIdToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            'https://app.damaijaya.my.id/oauth/userinfo' => Http::response([
                'sub' => 999,
                'name' => 'Choirul Anam',
                'email' => 'cranam21@gmail.com',
                'role' => 'Super Admin',
            ], 200),
        ]);

        $response = $this->get(route('sso.callback', [
            'code' => 'valid-superadmin-space-code',
            'state' => 'valid-state-space',
        ]));

        $this->assertAuthenticated();
        $response->assertRedirect(route('projects.index'));

        $user = User::where('email', 'cranam21@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('admin', $user->role);
        $this->assertEquals('active', $user->status);
    }
}
