<?php

namespace Tests\Feature;

use App\Jobs\PublishScheduleJob;
use App\Models\CampaignTarget;
use App\Models\ConnectedAccount;
use App\Models\ProjectCampaign;
use App\Models\Schedule;
use App\Models\User;
use App\Services\MetaGraphService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PublishEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Operator Engine',
            'email' => 'engine@test.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'status' => 'active',
        ]);

        $this->actingAs($user);
    }

    public function test_publish_schedule_job_respects_platform_targets_and_records_logs(): void
    {
        // 1. Account 1: Sevencols (Target: both)
        $acc1 = ConnectedAccount::create([
            'page_id' => 'fb_sevencols',
            'page_name' => 'Sevencols Apparel',
            'ig_user_id' => 'ig_sevencols',
            'ig_username' => 'sevencols',
            'page_access_token' => 'token_sevencols',
            'is_active' => true,
        ]);

        // 2. Account 2: Arema Style (Target: instagram_only)
        $acc2 = ConnectedAccount::create([
            'page_id' => 'fb_aremastyle',
            'page_name' => 'Arema Style',
            'ig_user_id' => 'ig_aremastyle',
            'ig_username' => 'aremastyle',
            'page_access_token' => 'token_aremastyle',
            'is_active' => true,
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Multi-Account Campaign',
            'content_type' => 'story',
            'target_time' => '07:30',
            'repeat_type' => 'continuous',
            'status' => 'active',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $project->id,
            'connected_account_id' => $acc1->id,
            'platform_target' => 'both',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $project->id,
            'connected_account_id' => $acc2->id,
            'platform_target' => 'instagram_only',
        ]);

        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'test_item_456',
            'media_path' => '/storage/uploads/story.jpg',
            'media_paths' => ['/storage/uploads/story.jpg'],
            'target_date' => Carbon::today(),
            'target_time' => '07:30',
            'status' => 'pending',
        ]);

        // Mock MetaGraphService
        $mockService = Mockery::mock(MetaGraphService::class);

        // Account 1: Check Quota -> OK
        $mockService->shouldReceive('getContentPublishingLimit')
            ->with('ig_sevencols', 'token_sevencols')
            ->once()
            ->andReturn(['success' => true, 'quota_usage' => 5, 'config' => ['quota_total' => 100]]);

        // Account 1: Publish Story to IG -> Success
        $mockService->shouldReceive('publishInstagramStory')
            ->with('ig_sevencols', 'token_sevencols', Mockery::any(), false)
            ->once()
            ->andReturn(['success' => true, 'id' => 'ig_story_id_111', 'container_id' => 'cnt_111']);

        // Account 1: Publish Story to FB -> Success
        $mockService->shouldReceive('publishFacebookPage')
            ->with('fb_sevencols', 'token_sevencols', Mockery::any(), Mockery::any(), 'story')
            ->once()
            ->andReturn(['success' => true, 'id' => 'fb_post_id_111', 'data' => []]);

        // Account 2: Check Quota -> OK
        $mockService->shouldReceive('getContentPublishingLimit')
            ->with('ig_aremastyle', 'token_aremastyle')
            ->once()
            ->andReturn(['success' => true, 'quota_usage' => 10, 'config' => ['quota_total' => 100]]);

        // Account 2: Publish Story to IG -> Success
        $mockService->shouldReceive('publishInstagramStory')
            ->with('ig_aremastyle', 'token_aremastyle', Mockery::any(), false)
            ->once()
            ->andReturn(['success' => true, 'id' => 'ig_story_id_222', 'container_id' => 'cnt_222']);

        // Account 2: Should NOT call publishFacebookPage because platform_target is instagram_only!
        $mockService->shouldNotReceive('publishFacebookPage')
            ->with('fb_aremastyle', Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any());

        // Execute Job
        $job = new PublishScheduleJob($schedule);
        $job->handle($mockService);

        // Verifikasi hasil
        $schedule->refresh();
        $this->assertEquals('completed', $schedule->status);

        $logs = $schedule->publishLogs;
        // Total logs = 4 (Acc 1 IG, Acc 1 FB, Acc 2 IG, Acc 2 FB skipped)
        $this->assertCount(4, $logs);

        // Cek log Acc 1 (Sevencols)
        $acc1IgLog = $logs->where('connected_account_id', $acc1->id)->where('platform', 'instagram')->first();
        $this->assertEquals('success', $acc1IgLog->action_status);
        $this->assertEquals('ig_story_id_111', $acc1IgLog->media_id);

        $acc1FbLog = $logs->where('connected_account_id', $acc1->id)->where('platform', 'facebook')->first();
        $this->assertEquals('success', $acc1FbLog->action_status);
        $this->assertEquals('fb_post_id_111', $acc1FbLog->media_id);

        // Cek log Acc 2 (Arema Style)
        $acc2IgLog = $logs->where('connected_account_id', $acc2->id)->where('platform', 'instagram')->first();
        $this->assertEquals('success', $acc2IgLog->action_status);
        $this->assertEquals('ig_story_id_222', $acc2IgLog->media_id);

        $acc2FbLog = $logs->where('connected_account_id', $acc2->id)->where('platform', 'facebook')->first();
        $this->assertEquals('skipped', $acc2FbLog->action_status);
        $this->assertStringContainsString('instagram_only', $acc2FbLog->error_message);
    }

    public function test_run_single_supports_force_republish(): void
    {
        $acc = ConnectedAccount::create([
            'page_id' => 'fb_page_999',
            'page_name' => 'Test Account',
            'page_access_token' => 'test_token',
            'is_active' => true,
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Single Test Campaign',
            'content_type' => 'post',
            'target_time' => '10:00',
            'repeat_type' => 'once',
            'status' => 'active',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $project->id,
            'connected_account_id' => $acc->id,
            'platform_target' => 'facebook_only',
        ]);

        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'test_item_republish',
            'media_path' => '/storage/uploads/test.jpg',
            'media_paths' => ['/storage/uploads/test.jpg'],
            'target_date' => Carbon::today(),
            'target_time' => '10:00',
            'status' => 'completed',
        ]);

        $mockService = Mockery::mock(MetaGraphService::class);
        $mockService->shouldReceive('publishFacebookPage')
            ->once()
            ->andReturn(['success' => true, 'id' => 'fb_post_republish_123', 'data' => []]);

        $this->app->instance(MetaGraphService::class, $mockService);

        $response = $this->postJson(route('schedules.runSingle', $schedule->id), [
            'force_republish' => true,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'completed',
        ]);

        $schedule->refresh();
        $this->assertEquals('completed', $schedule->status);
    }
}
