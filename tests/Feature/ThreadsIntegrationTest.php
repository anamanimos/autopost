<?php

namespace Tests\Feature;

use App\Jobs\PublishScheduleJob;
use App\Models\CampaignTarget;
use App\Models\ConnectedAccount;
use App\Models\MediaFile;
use App\Models\MetaCredential;
use App\Models\ProjectCampaign;
use App\Models\Schedule;
use App\Models\User;
use App\Services\MetaGraphService;
use App\Services\ThreadsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ThreadsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin Threads',
            'email' => 'admin_threads@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        MetaCredential::create([
            'app_id' => 'meta_app_123',
            'app_secret' => 'meta_secret_456',
            'threads_app_id' => 'threads_app_789',
            'threads_app_secret' => 'threads_secret_abc',
            'graph_version' => 'v22.0',
            'token_status' => 'valid',
        ]);
    }

    public function test_threads_oauth_redirect_and_callback(): void
    {
        $this->actingAs($this->adminUser);

        $acc = ConnectedAccount::create([
            'page_id' => 'fb_acc_100',
            'page_name' => 'Brand Sevencols',
            'ig_username' => 'sevencols',
            'is_active' => true,
        ]);

        // 1. Test redirect
        $res = $this->get(route('threads.oauth', ['account_id' => $acc->id]));
        $res->assertRedirect();
        $this->assertStringContainsString('threads.net/oauth/authorize', $res->headers->get('Location'));
        $this->assertEquals($acc->id, session('threads_target_account_id'));

        // 2. Mock callback responses
        Http::fake([
            'https://graph.threads.net/oauth/access_token' => Http::response([
                'access_token' => 'short_th_token',
                'user_id' => 'th_uid_999',
            ], 200),
            'https://graph.threads.net/access_token*' => Http::response([
                'access_token' => 'long_th_token_60d',
                'token_type' => 'bearer',
                'expires_in' => 5184000,
            ], 200),
            'https://graph.threads.net/v1.0/me*' => Http::response([
                'id' => 'th_uid_999',
                'username' => 'sevencols',
                'threads_profile_picture_url' => 'https://example.com/pic.jpg',
            ], 200),
            'https://graph.threads.net/v1.0/th_uid_999/threads_publishing_limit*' => Http::response([
                'data' => [
                    [
                        'quota_usage' => 2,
                        'config' => ['quota_total' => 250, 'quota_duration' => 86400],
                    ],
                ],
            ], 200),
        ]);

        $cbRes = $this->get(route('threads.callback', ['code' => 'valid_auth_code']));
        $cbRes->assertRedirect(route('settings.index', ['tab' => 'meta']));
        $cbRes->assertSessionHas('success');

        $acc->refresh();
        $this->assertTrue($acc->hasThreads());
        $this->assertEquals('th_uid_999', $acc->threads_user_id);
        $this->assertEquals('sevencols', $acc->threads_username);
        $this->assertEquals('long_th_token_60d', $acc->threads_access_token);
        $this->assertEquals(2, $acc->threads_publishing_quota_usage);
    }

    public function test_threads_manual_token_and_disconnect(): void
    {
        $this->actingAs($this->adminUser);

        $acc = ConnectedAccount::create([
            'page_id' => 'fb_acc_200',
            'page_name' => 'Brand Arema',
            'ig_username' => 'arema_official',
            'is_active' => true,
        ]);

        Http::fake([
            'https://graph.threads.net/v1.0/me*' => Http::response([
                'id' => 'th_arema_uid',
                'username' => 'arema_official',
                'threads_profile_picture_url' => 'https://example.com/arema.jpg',
            ], 200),
            'https://graph.threads.net/access_token*' => Http::response([
                'access_token' => 'long_arema_token',
                'expires_in' => 5184000,
            ], 200),
            'https://graph.threads.net/v1.0/th_arema_uid/threads_publishing_limit*' => Http::response([
                'data' => [['quota_usage' => 0, 'config' => ['quota_total' => 250]]],
            ], 200),
        ]);

        // Simpan token manual
        $postRes = $this->postJson(route('threads.saveManualToken'), [
            'account_id' => $acc->id,
            'threads_access_token' => 'manual_token_xyz',
        ]);

        $postRes->assertOk();
        $postRes->assertJson(['success' => true]);

        $acc->refresh();
        $this->assertTrue($acc->hasThreads());
        $this->assertEquals('th_arema_uid', $acc->threads_user_id);

        // Putuskan koneksi (disconnect)
        $discRes = $this->postJson(route('threads.disconnect', $acc->id));
        $discRes->assertOk();

        $acc->refresh();
        $this->assertFalse($acc->hasThreads());
        $this->assertNull($acc->threads_user_id);
        $this->assertNull($acc->threads_access_token);
    }

    public function test_publish_schedule_job_executes_threads_for_all_and_threads_only(): void
    {
        $acc = ConnectedAccount::create([
            'page_id' => 'fb_sevencols',
            'page_name' => 'Sevencols',
            'ig_user_id' => 'ig_sevencols',
            'threads_user_id' => 'th_sevencols',
            'threads_username' => 'sevencols',
            'threads_access_token' => 'token_th_active',
            'page_access_token' => 'token_fb_active',
            'is_active' => true,
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Threads Omnichannel Post',
            'content_type' => 'post',
            'caption' => 'Rilisan jersey terbaru!',
            'target_time' => '12:00',
            'repeat_type' => 'once',
            'status' => 'active',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $project->id,
            'connected_account_id' => $acc->id,
            'platform_target' => 'all', // Menargetkan FB, IG, dan Threads
        ]);

        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'sched_threads_001',
            'media_path' => '/storage/uploads/jersey.jpg',
            'media_paths' => ['/storage/uploads/jersey.jpg'],
            'target_date' => Carbon::today(),
            'target_time' => '12:00',
            'status' => 'pending',
        ]);

        // Mock MetaGraphService (untuk IG & FB)
        $metaMock = Mockery::mock(MetaGraphService::class);
        $metaMock->shouldReceive('getContentPublishingLimit')->andReturn(['success' => true, 'quota_usage' => 1, 'config' => ['quota_total' => 100]]);
        $metaMock->shouldReceive('publishInstagramFeedPost')->andReturn(['success' => true, 'id' => 'ig_post_001', 'container_id' => 'cnt_ig']);
        $metaMock->shouldReceive('publishFacebookPage')->andReturn(['success' => true, 'id' => 'fb_post_001', 'data' => []]);

        // Mock ThreadsService
        $threadsMock = Mockery::mock(ThreadsService::class);
        $threadsMock->shouldReceive('getPublishingLimit')->with('th_sevencols', 'token_th_active')->andReturn(['success' => true, 'quota_usage' => 5, 'config' => ['quota_total' => 250]]);
        $threadsMock->shouldReceive('publishThreadsPost')
            ->with('th_sevencols', 'token_th_active', Mockery::any(), Mockery::any(), false)
            ->once()
            ->andReturn(['success' => true, 'id' => 'threads_post_777', 'container_id' => 'th_cnt_777']);

        $job = new PublishScheduleJob($schedule);
        $job->handle($metaMock, $threadsMock);

        $schedule->refresh();
        $this->assertEquals('completed', $schedule->status);

        $logs = $schedule->publishLogs;
        $this->assertCount(3, $logs); // Instagram, Facebook, Threads

        $threadsLog = $logs->where('platform', 'threads')->first();
        $this->assertNotNull($threadsLog);
        $this->assertEquals('success', $threadsLog->action_status);
        $this->assertEquals('threads_post_777', $threadsLog->media_id);
    }

    public function test_direct_post_supports_threads_platform(): void
    {
        $this->actingAs($this->adminUser);

        $acc = ConnectedAccount::create([
            'page_id' => 'fb_acc_direct',
            'page_name' => 'Direct Page',
            'threads_user_id' => 'th_direct_id',
            'threads_username' => 'direct_th',
            'threads_access_token' => 'direct_token',
            'is_active' => true,
        ]);

        $media = MediaFile::create([
            'original_name' => 'direct.jpg',
            'file_path' => '/storage/uploads/direct.jpg',
            'file_hash' => 'hash_direct_001',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'media_type' => 'image',
        ]);

        Http::fake([
            'https://graph.threads.net/v1.0/th_direct_id/threads_publishing_limit*' => Http::response([
                'data' => [['quota_usage' => 0, 'config' => ['quota_total' => 250]]],
            ], 200),
            'https://graph.threads.net/v1.0/th_direct_id/threads' => Http::response(['id' => 'cnt_direct_th'], 200),
            'https://graph.threads.net/v1.0/th_direct_id/threads_publish' => Http::response(['id' => 'pub_direct_th'], 200),
        ]);

        $res = $this->postJson(route('schedules.directPost'), [
            'name' => 'Post Langsung ke Threads',
            'content_type' => 'post',
            'caption' => 'Halo Threads dari Direct Post!',
            'existing_media_id' => $media->id,
            'targets' => [
                [
                    'account_id' => $acc->id,
                    'platform_target' => 'threads_only',
                ],
            ],
        ]);

        $res->assertOk();
        $res->assertJson(['success' => true]);

        $schedule = Schedule::latest()->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('completed', $schedule->status);

        $threadsLog = $schedule->publishLogs()->where('platform', 'threads')->first();
        $this->assertNotNull($threadsLog);
        $this->assertEquals('success', $threadsLog->action_status);
        $this->assertEquals('pub_direct_th', $threadsLog->media_id);
    }

    public function test_threads_oauth_redirect_blocks_and_warns_when_threads_app_id_not_set(): void
    {
        $this->actingAs($this->adminUser);

        // Kosongkan threads_app_id
        MetaCredential::first()->update([
            'threads_app_id' => null,
            'app_id' => 'general_fb_app_id', // app_id umum tidak boleh dipakai untuk Threads
        ]);

        $res = $this->get(route('threads.oauth'));
        $res->assertRedirect(route('settings.index', ['tab' => 'meta']));
        $res->assertSessionHas('error');
        $this->assertStringContainsString('Threads App ID belum diatur', session('error'));
    }
}
