<?php

namespace Tests\Feature;

use App\Models\CampaignTarget;
use App\Models\ConnectedAccount;
use App\Models\ProjectCampaign;
use App\Models\Schedule;
use App\Models\User;
use App\Services\MetaGraphService;
use App\Services\ThreadsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TextOnlyPostTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ConnectedAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Admin Text Post',
            'email' => 'admin_text@autopost.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        $this->account = ConnectedAccount::create([
            'page_id' => 'fb_page_text_123',
            'page_name' => 'Damai Jaya Text Page',
            'page_access_token' => 'token_fb_text_abc',
            'ig_user_id' => 'ig_user_text_456',
            'ig_username' => 'damaijaya_ig_text',
            'threads_user_id' => 'th_user_text_789',
            'threads_username' => 'damaijaya_threads',
            'threads_access_token' => 'token_th_text_xyz',
            'threads_token_expires_at' => Carbon::now()->addDays(60),
            'is_active' => true,
        ]);
    }

    public function test_can_create_project_campaign_threads_only_without_media(): void
    {
        $response = $this->postJson(route('projects.store'), [
            'name' => 'Threads Text Only Campaign',
            'content_type' => 'post',
            'caption' => 'Halo Warga Threads dari Campaign Teks!',
            'target_time' => '14:30',
            'repeat_type' => 'continuous',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'threads_only',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('project_campaigns', [
            'name' => 'Threads Text Only Campaign',
            'caption' => 'Halo Warga Threads dari Campaign Teks!',
        ]);

        $project = ProjectCampaign::where('name', 'Threads Text Only Campaign')->first();
        $this->assertCount(0, $project->mediaFiles);
        $this->assertGreaterThan(0, $project->schedules()->count());
    }

    public function test_can_create_project_campaign_facebook_only_without_media(): void
    {
        $response = $this->postJson(route('projects.store'), [
            'name' => 'Facebook Text Only Campaign',
            'content_type' => 'post',
            'caption' => 'Status Facebook tanpa media!',
            'target_time' => '10:00',
            'repeat_type' => 'continuous',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'facebook_only',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $project = ProjectCampaign::where('name', 'Facebook Text Only Campaign')->first();
        $this->assertCount(0, $project->mediaFiles);
        $this->assertGreaterThan(0, $project->schedules()->count());
    }

    public function test_cannot_create_project_campaign_targeting_instagram_without_media(): void
    {
        $response = $this->postJson(route('projects.store'), [
            'name' => 'Instagram Without Media Campaign',
            'content_type' => 'post',
            'caption' => 'Caption untuk Instagram tapi tanpa media',
            'target_time' => '10:00',
            'repeat_type' => 'continuous',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'both', // Both includes Instagram
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Target akun mencakup Instagram yang mewajibkan file media (gambar/video). Silakan pilih atau unggah minimal 1 media.',
        ]);
    }

    public function test_cannot_create_project_campaign_without_media_and_without_caption(): void
    {
        $response = $this->postJson(route('projects.store'), [
            'name' => 'Threads Without Media And Without Caption',
            'content_type' => 'post',
            'caption' => '',
            'target_time' => '10:00',
            'repeat_type' => 'continuous',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'threads_only',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Untuk postingan tanpa media (Threads / FB Page), silakan isi teks caption postingan.',
        ]);
    }

    public function test_direct_publish_threads_only_without_media(): void
    {
        $mockThreads = Mockery::mock(ThreadsService::class);
        $this->app->instance(ThreadsService::class, $mockThreads);

        $mockThreads->shouldReceive('getPublishingLimit')
            ->once()
            ->with('th_user_text_789', 'token_th_text_xyz')
            ->andReturn([
                'success' => true,
                'quota_usage' => 5,
                'config' => ['quota_total' => 250],
            ]);

        $mockThreads->shouldReceive('publishThreadsPost')
            ->once()
            ->with('th_user_text_789', 'token_th_text_xyz', [], 'Direct post Threads tanpa media', false)
            ->andReturn([
                'success' => true,
                'id' => 'th_post_instant_123',
                'container_id' => 'th_con_instant_456',
                'data' => ['id' => 'th_post_instant_123'],
            ]);

        $response = $this->postJson(route('projects.store'), [
            'name' => 'Instant Threads Campaign',
            'content_type' => 'post',
            'caption' => 'Direct post Threads tanpa media',
            'repeat_type' => 'instant',
            'direct_publish' => '1',
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'threads_only',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'direct_published' => true,
            'schedule_status' => 'completed',
        ]);

        $this->assertDatabaseHas('publish_logs', [
            'platform' => 'threads',
            'action_status' => 'success',
            'media_id' => 'th_post_instant_123',
        ]);
    }

    public function test_direct_publish_facebook_only_without_media(): void
    {
        $mockMeta = Mockery::mock(MetaGraphService::class);
        $this->app->instance(MetaGraphService::class, $mockMeta);

        $mockMeta->shouldReceive('publishFacebookPage')
            ->once()
            ->with('fb_page_text_123', 'token_fb_text_abc', [], 'Direct post Facebook tanpa media', 'post')
            ->andReturn([
                'success' => true,
                'id' => 'fb_post_instant_789',
                'data' => ['id' => 'fb_post_instant_789'],
            ]);

        $response = $this->postJson(route('projects.store'), [
            'name' => 'Instant Facebook Campaign',
            'content_type' => 'post',
            'caption' => 'Direct post Facebook tanpa media',
            'repeat_type' => 'instant',
            'direct_publish' => '1',
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'facebook_only',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'direct_published' => true,
            'schedule_status' => 'completed',
        ]);

        $this->assertDatabaseHas('publish_logs', [
            'platform' => 'facebook',
            'action_status' => 'success',
            'media_id' => 'fb_post_instant_789',
        ]);
    }

    public function test_schedule_controller_direct_post_modal_without_media_succeeds_for_threads(): void
    {
        $mockThreads = Mockery::mock(ThreadsService::class);
        $this->app->instance(ThreadsService::class, $mockThreads);

        $mockThreads->shouldReceive('getPublishingLimit')
            ->once()
            ->andReturn(['success' => true, 'quota_usage' => 0, 'config' => ['quota_total' => 250]]);

        $mockThreads->shouldReceive('publishThreadsPost')
            ->once()
            ->with('th_user_text_789', 'token_th_text_xyz', [], 'Modal direct text post', false)
            ->andReturn([
                'success' => true,
                'id' => 'th_modal_111',
                'container_id' => 'th_con_222',
                'data' => ['id' => 'th_modal_111'],
            ]);

        $response = $this->postJson(route('schedules.directPost'), [
            'name' => 'Modal Direct Post Threads',
            'content_type' => 'post',
            'caption' => 'Modal direct text post',
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'threads_only',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'completed',
        ]);
    }
}
