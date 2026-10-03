<?php

namespace Tests\Feature;

use App\Jobs\PublishScheduleJob;
use App\Models\CampaignTarget;
use App\Models\ConnectedAccount;
use App\Models\MediaFile;
use App\Models\MetaCredential;
use App\Models\ProjectCampaign;
use App\Models\PublishLog;
use App\Models\Schedule;
use App\Models\TikTokCredential;
use App\Models\User;
use App\Services\MetaGraphService;
use App\Services\ThreadsService;
use App\Services\TikTokService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class TikTokIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected TikTokCredential $credential;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin TikTok',
            'email' => 'admin_tiktok@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->credential = TikTokCredential::create([
            'client_key' => 'tiktok_client_key_123',
            'client_secret' => 'tiktok_client_secret_456',
            'status' => 'valid',
            'notes' => 'TikTok Test App',
        ]);
    }

    public function test_admin_can_update_tiktok_credentials(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->post(route('tiktok.updateCredentials'), [
            'client_key' => 'new_client_key_999',
            'client_secret' => 'new_client_secret_888',
            'notes' => 'Updated App',
        ]);

        $res->assertRedirect(route('settings.index', ['tab' => 'tiktok']));
        $res->assertSessionHas('success');

        $this->credential->refresh();
        $this->assertEquals('new_client_key_999', $this->credential->client_key);
        $this->assertEquals('new_client_secret_888', $this->credential->getClientSecret());
        $this->assertEquals('Updated App', $this->credential->notes);
    }

    public function test_tiktok_oauth_redirect_and_callback(): void
    {
        $this->actingAs($this->adminUser);

        $account = ConnectedAccount::create([
            'page_id' => 'fb_acc_200',
            'page_name' => 'Toko Busana',
            'is_active' => true,
        ]);

        // 1. Test Redirect
        $res = $this->get(route('tiktok.oauth', ['account_id' => $account->id]));
        $res->assertRedirect();
        $this->assertStringContainsString('tiktok.com/v2/auth/authorize', $res->headers->get('Location'));
        $this->assertEquals($account->id, session('tiktok_target_account_id'));

        // 2. Mock HTTP responses for token exchange and user info
        Http::fake([
            'https://open.tiktokapis.com/v2/oauth/token/' => Http::response([
                'data' => [
                    'access_token' => 'act.tiktok_access_token_mock',
                    'refresh_token' => 'rft.tiktok_refresh_token_mock',
                    'open_id' => 'open_id_tiktok_12345',
                    'expires_in' => 86400,
                    'refresh_expires_in' => 31536000,
                    'token_type' => 'Bearer',
                ],
                'message' => 'success',
            ], 200),
            'https://open.tiktokapis.com/v2/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id' => 'open_id_tiktok_12345',
                        'union_id' => 'union_id_12345',
                        'avatar_url' => 'https://p16-va.tiktokcdn.com/avatar.jpeg',
                        'display_name' => 'Toko Busana Official',
                        'username' => 'tokobusana_id',
                    ],
                ],
                'message' => 'success',
            ], 200),
        ]);

        // 3. Callback execution
        $callbackRes = $this->get(route('tiktok.callback', ['code' => 'mock_auth_code_from_tiktok']));
        $callbackRes->assertRedirect(route('settings.index', ['tab' => 'tiktok']));
        $callbackRes->assertSessionHas('success');

        $account->refresh();
        $this->assertTrue($account->hasTikTok());
        $this->assertEquals('open_id_tiktok_12345', $account->tiktok_open_id);
        $this->assertEquals('tokobusana_id', $account->tiktok_username);
        $this->assertEquals('Toko Busana Official', $account->tiktok_display_name);
        $this->assertEquals('act.tiktok_access_token_mock', $account->tiktok_access_token);
        $this->assertEquals('rft.tiktok_refresh_token_mock', $account->tiktok_refresh_token);
    }

    public function test_tiktok_oauth_callback_with_root_level_token_response(): void
    {
        $this->actingAs($this->adminUser);

        $account = ConnectedAccount::create([
            'page_id' => 'fb_acc_root_level',
            'page_name' => 'Arema Style',
            'is_active' => true,
        ]);

        session(['tiktok_target_account_id' => $account->id]);

        // Mock TikTok returning tokens at ROOT level of JSON (RFC 6749 standard)
        Http::fake([
            'https://open.tiktokapis.com/v2/oauth/token/' => Http::response([
                'access_token' => 'act.root_level_token_arema',
                'refresh_token' => 'rft.root_level_refresh_arema',
                'open_id' => 'open_id_arema_999',
                'expires_in' => 86400,
                'refresh_expires_in' => 31536000,
                'token_type' => 'Bearer',
            ], 200),
            'https://open.tiktokapis.com/v2/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id' => 'open_id_arema_999',
                        'display_name' => 'Arema Style Official',
                        'username' => 'arema_style',
                    ],
                ],
            ], 200),
        ]);

        $res = $this->get(route('tiktok.callback', ['code' => 'mock_root_code']));
        $res->assertRedirect(route('settings.index', ['tab' => 'tiktok']));
        $res->assertSessionHas('success');

        $account->refresh();
        $this->assertTrue($account->hasTikTok());
        $this->assertEquals('open_id_arema_999', $account->tiktok_open_id);
        $this->assertEquals('arema_style', $account->tiktok_username);
        $this->assertEquals('act.root_level_token_arema', $account->tiktok_access_token);
    }

    public function test_tiktok_manual_connect_and_disconnect(): void
    {
        $this->actingAs($this->adminUser);

        $account = ConnectedAccount::create([
            'page_id' => 'fb_acc_300',
            'page_name' => 'Kopi Senja',
            'is_active' => true,
        ]);

        Http::fake([
            'https://open.tiktokapis.com/v2/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id' => 'open_id_kopi_777',
                        'display_name' => 'Kopi Senja Cafe',
                        'username' => 'kopisenja_cafe',
                        'avatar_url' => 'https://example.com/avatar.png',
                    ],
                ],
                'message' => 'success',
            ], 200),
        ]);

        $connectRes = $this->post(route('tiktok.connectManual'), [
            'account_id' => $account->id,
            'access_token' => 'act.manual_token_abc',
            'refresh_token' => 'rft.manual_refresh_xyz',
        ]);

        $connectRes->assertRedirect(route('settings.index', ['tab' => 'tiktok']));
        $connectRes->assertSessionHas('success');

        $account->refresh();
        $this->assertTrue($account->hasTikTok());
        $this->assertEquals('kopisenja_cafe', $account->tiktok_username);
        $this->assertEquals('open_id_kopi_777', $account->tiktok_open_id);

        // Test Disconnect via JSON / AJAX
        $disconnectRes = $this->postJson(route('tiktok.disconnect', $account->id));
        $disconnectRes->assertOk();
        $disconnectRes->assertJson(['success' => true]);

        $account->refresh();
        $this->assertFalse($account->hasTikTok());
        $this->assertNull($account->tiktok_access_token);
        $this->assertNull($account->tiktok_open_id);
    }

    public function test_publish_schedule_job_targets_tiktok_successfully(): void
    {
        $account = ConnectedAccount::create([
            'page_id' => 'tiktok_account_555',
            'page_name' => 'Studio Kreatif',
            'tiktok_open_id' => 'open_id_studio_555',
            'tiktok_username' => 'studiokreatif',
            'tiktok_access_token' => 'act.valid_studio_token',
            'tiktok_refresh_token' => 'rft.valid_studio_refresh',
            'tiktok_token_expires_at' => Carbon::now()->addHours(20),
            'is_active' => true,
        ]);

        $media = MediaFile::create([
            'original_name' => 'promo_video.mp4',
            'file_path' => 'uploads/promo_video.mp4',
            'file_hash' => md5('promo_video.mp4'),
            'file_size' => 1024000,
            'mime_type' => 'video/mp4',
            'media_type' => 'video',
            'is_video' => true,
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Campaign TikTok Viral',
            'content_type' => 'post',
            'caption' => 'Video promo viral #fyp #trending',
            'target_time' => '10:00',
            'repeat_type' => 'once',
            'start_date' => Carbon::today(),
            'status' => 'active',
        ]);

        $target = CampaignTarget::create([
            'project_campaign_id' => $project->id,
            'connected_account_id' => $account->id,
            'platform_target' => 'tiktok_only',
        ]);

        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'sch_tk_001',
            'media_file_id' => $media->id,
            'media_path' => $media->file_path,
            'media_paths' => [$media->file_path],
            'target_date' => Carbon::today(),
            'target_time' => '10:00',
            'status' => 'pending',
        ]);

        $mockTikTokService = Mockery::mock(TikTokService::class);
        $mockTikTokService->shouldReceive('publishTikTokPost')
            ->once()
            ->andReturn([
                'success' => true,
                'publish_id' => 'publish_job_id_99999',
                'id' => 'v_pub_id_99999',
                'data' => ['status' => 'PROCESSING_DOWNLOAD'],
            ]);

        $metaService = Mockery::mock(MetaGraphService::class);
        $threadsService = Mockery::mock(ThreadsService::class);

        (new PublishScheduleJob($schedule))->handle($metaService, $threadsService, $mockTikTokService);

        $schedule->refresh();
        $this->assertEquals('completed', $schedule->status);

        $log = PublishLog::where('schedule_id', $schedule->id)
            ->where('platform', 'tiktok')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('success', $log->action_status);
        $this->assertEquals('v_pub_id_99999', $log->media_id);
    }

    public function test_publish_schedule_job_selective_retry_tiktok_only(): void
    {
        $account = ConnectedAccount::create([
            'page_id' => 'fb_page_888',
            'page_name' => 'Brand Multiplatform',
            'facebook_page_id' => 'fb_page_888',
            'access_token' => 'fb_token_valid',
            'tiktok_open_id' => 'open_id_tk_888',
            'tiktok_username' => 'brand_tk',
            'tiktok_access_token' => 'act.brand_tk_token',
            'tiktok_token_expires_at' => Carbon::now()->addHours(12),
            'is_active' => true,
        ]);

        $media = MediaFile::create([
            'original_name' => 'photo.jpg',
            'file_path' => 'uploads/photo.jpg',
            'file_hash' => md5('photo.jpg'),
            'file_size' => 500000,
            'mime_type' => 'image/jpeg',
            'media_type' => 'image',
            'is_video' => false,
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Campaign Multiplatform',
            'content_type' => 'post',
            'caption' => 'Koleksi terbaru sekarang hadir!',
            'target_time' => '11:00',
            'repeat_type' => 'once',
            'start_date' => Carbon::today(),
            'status' => 'active',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $project->id,
            'connected_account_id' => $account->id,
            'platform_target' => 'fb_tiktok',
        ]);

        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'sch_retry_001',
            'media_file_id' => $media->id,
            'media_path' => $media->file_path,
            'media_paths' => [$media->file_path],
            'target_date' => Carbon::today(),
            'target_time' => '11:00',
            'status' => 'partially_failed',
        ]);

        // Catat log sukses lama untuk Facebook
        PublishLog::create([
            'schedule_id' => $schedule->id,
            'project_campaign_id' => $project->id,
            'connected_account_id' => $account->id,
            'platform' => 'facebook',
            'content_type' => 'post',
            'action_status' => 'success',
            'media_id' => 'fb_post_existing_123',
            'executed_at' => Carbon::now()->subMinutes(10),
        ]);

        // Catat log gagal lama untuk TikTok
        $failedTikTokLog = PublishLog::create([
            'schedule_id' => $schedule->id,
            'project_campaign_id' => $project->id,
            'connected_account_id' => $account->id,
            'platform' => 'tiktok',
            'content_type' => 'post',
            'action_status' => 'failed',
            'error_message' => 'Rate limit sementara dari TikTok',
            'executed_at' => Carbon::now()->subMinutes(10),
        ]);

        $metaService = Mockery::mock(MetaGraphService::class);
        // Meta tidak boleh dipanggil publishFeedPhoto/Video karena sudah sukses sebelumnya!
        $metaService->shouldNotReceive('publishFeedPhoto');
        $metaService->shouldNotReceive('publishFeedVideo');

        $threadsService = Mockery::mock(ThreadsService::class);

        $mockTikTokService = Mockery::mock(TikTokService::class);
        $mockTikTokService->shouldReceive('publishTikTokPost')
            ->once()
            ->andReturn([
                'success' => true,
                'publish_id' => 'v_pub_retry_success_456',
                'id' => 'v_pub_retry_success_456',
                'data' => ['status' => 'PROCESSING_DOWNLOAD'],
            ]);

        // Jalankan retry (forceAll = false)
        (new PublishScheduleJob($schedule, false))->handle($metaService, $threadsService, $mockTikTokService);

        $schedule->refresh();
        $this->assertEquals('completed', $schedule->status);

        // Pastikan Facebook tetap memiliki 1 log sukses
        $fbLogsCount = PublishLog::where('schedule_id', $schedule->id)
            ->where('platform', 'facebook')
            ->count();
        $this->assertEquals(1, $fbLogsCount);

        // Pastikan log TikTok lama di-update in-place menjadi success
        $failedTikTokLog->refresh();
        $this->assertEquals('success', $failedTikTokLog->action_status);
        $this->assertEquals('v_pub_retry_success_456', $failedTikTokLog->media_id);
        $this->assertNull($failedTikTokLog->error_message);
    }

    public function test_tiktok_requires_media_validation(): void
    {
        $account = ConnectedAccount::create([
            'page_id' => 'tiktok_text_only',
            'page_name' => 'Akun TikTok Teks',
            'tiktok_open_id' => 'open_id_no_media',
            'tiktok_username' => 'akunteks',
            'tiktok_access_token' => 'act.token_valid',
            'tiktok_token_expires_at' => Carbon::now()->addHours(5),
            'is_active' => true,
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Campaign Teks Saja',
            'content_type' => 'post',
            'caption' => 'Postingan ini hanya berisi teks tanpa gambar ataupun video.',
            'target_time' => '12:00',
            'repeat_type' => 'once',
            'start_date' => Carbon::today(),
            'status' => 'active',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $project->id,
            'connected_account_id' => $account->id,
            'platform_target' => 'tiktok_only',
        ]);

        // Jadwal tanpa media file
        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'sch_no_media_001',
            'media_file_id' => null,
            'media_path' => null,
            'media_paths' => [],
            'target_date' => Carbon::today(),
            'target_time' => '12:00',
            'status' => 'pending',
        ]);

        $metaService = Mockery::mock(MetaGraphService::class);
        $threadsService = Mockery::mock(ThreadsService::class);
        $mockTikTokService = Mockery::mock(TikTokService::class);

        (new PublishScheduleJob($schedule))->handle($metaService, $threadsService, $mockTikTokService);

        $schedule->refresh();
        $this->assertEquals('failed', $schedule->status);

        $log = PublishLog::where('schedule_id', $schedule->id)
            ->where('platform', 'tiktok')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('failed', $log->action_status);
        $this->assertStringContainsString('TikTok mewajibkan aset video atau foto', $log->error_message);
    }
}
