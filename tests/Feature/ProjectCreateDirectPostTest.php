<?php

namespace Tests\Feature;

use App\Models\ConnectedAccount;
use App\Models\MediaFile;
use App\Models\ProjectCampaign;
use App\Models\Schedule;
use App\Models\User;
use App\Services\MetaGraphService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProjectCreateDirectPostTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ConnectedAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Admin Direct Create',
            'email' => 'admin_create@directpost.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        $this->account = ConnectedAccount::create([
            'page_id' => 'fb_page_create_123',
            'page_name' => 'Damai Jaya Create Page',
            'ig_user_id' => 'ig_user_create_456',
            'ig_username' => 'damaijaya_create',
            'page_access_token' => 'token_create_abc',
            'is_active' => true,
        ]);
    }

    public function test_create_project_with_instant_repeat_type_publishes_immediately(): void
    {
        Storage::fake('public');

        $mockMeta = Mockery::mock(MetaGraphService::class);

        // Check IG quota
        $mockMeta->shouldReceive('getContentPublishingLimit')
            ->with('ig_user_create_456', 'token_create_abc')
            ->once()
            ->andReturn(['success' => true, 'quota_usage' => 1, 'config' => ['quota_total' => 100]]);

        // Publish to IG Story
        $mockMeta->shouldReceive('publishInstagramStory')
            ->with('ig_user_create_456', 'token_create_abc', Mockery::any(), false)
            ->once()
            ->andReturn(['success' => true, 'media_id' => 'ig_story_created_999']);

        // Publish to FB Page
        $mockMeta->shouldReceive('publishFacebookPage')
            ->with('fb_page_create_123', 'token_create_abc', Mockery::any(), 'Promo hari ini!', 'story')
            ->once()
            ->andReturn(['success' => true, 'id' => 'fb_story_created_999']);

        $this->app->instance(MetaGraphService::class, $mockMeta);

        $fakeImage = UploadedFile::fake()->image('story_instant.jpg', 600, 600);

        $payload = [
            'name' => 'Promo Instant Flash Sale',
            'content_type' => 'story',
            'caption' => 'Promo hari ini!',
            'repeat_type' => 'instant',
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'both',
                ],
            ],
            'media_files' => [$fakeImage],
        ];

        $response = $this->postJson(route('projects.store'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'direct_published' => true,
                'schedule_status' => 'completed',
            ]);

        // Verifikasi database ProjectCampaign
        $project = ProjectCampaign::where('name', 'Promo Instant Flash Sale')->first();
        $this->assertNotNull($project);
        $this->assertEquals('completed', $project->status);
        $this->assertEquals('once', $project->repeat_type);

        // Verifikasi Schedule dan PublishLog
        $schedule = Schedule::where('project_campaign_id', $project->id)->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('completed', $schedule->status);
        $this->assertCount(2, $schedule->publishLogs);
    }

    public function test_create_project_with_continuous_and_direct_publish(): void
    {
        Storage::fake('public');

        $mockMeta = Mockery::mock(MetaGraphService::class);

        // Check IG quota
        $mockMeta->shouldReceive('getContentPublishingLimit')
            ->with('ig_user_create_456', 'token_create_abc')
            ->once()
            ->andReturn(['success' => true, 'quota_usage' => 1, 'config' => ['quota_total' => 100]]);

        // Publish to IG Feed Post
        $mockMeta->shouldReceive('publishInstagramFeedPost')
            ->with('ig_user_create_456', 'token_create_abc', Mockery::any(), 'Katalog harian pertama', false)
            ->once()
            ->andReturn(['success' => true, 'media_id' => 'ig_feed_post_111']);

        $this->app->instance(MetaGraphService::class, $mockMeta);

        $fakeImage = UploadedFile::fake()->image('katalog.jpg', 800, 800);

        $payload = [
            'name' => 'Katalog Harian Rolling',
            'content_type' => 'post',
            'caption' => 'Katalog harian pertama',
            'repeat_type' => 'continuous',
            'target_time' => '10:00',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'direct_publish' => '1',
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'instagram_only',
                ],
            ],
            'media_files' => [$fakeImage],
        ];

        $response = $this->postJson(route('projects.store'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'direct_published' => true,
                'schedule_status' => 'completed',
            ]);

        $project = ProjectCampaign::where('name', 'Katalog Harian Rolling')->first();
        $this->assertNotNull($project);
        $this->assertEquals('active', $project->status);
        $this->assertEquals('continuous', $project->repeat_type);

        // Harus ada direct schedule yang completed
        $directSchedule = Schedule::where('project_campaign_id', $project->id)
            ->where('status', 'completed')
            ->first();
        $this->assertNotNull($directSchedule);

        // Dan harus ada jadwal antrean masa depan (rolling buffer) yang berstatus pending
        $futureSchedules = Schedule::where('project_campaign_id', $project->id)
            ->where('status', 'pending')
            ->count();
        $this->assertGreaterThan(0, $futureSchedules);
    }

    public function test_create_project_with_existing_media_from_library(): void
    {
        Storage::fake('public');

        $media = MediaFile::create([
            'original_name' => 'existing_shirt.jpg',
            'file_path' => '/storage/uploads/existing_shirt.jpg',
            'file_hash' => 'hash_shirt_abc_123',
            'mime_type' => 'image/jpeg',
            'file_size' => 15000,
            'media_type' => 'image',
        ]);

        $mockMeta = Mockery::mock(MetaGraphService::class);
        $mockMeta->shouldReceive('getContentPublishingLimit')->andReturn(['success' => true]);
        $mockMeta->shouldReceive('publishFacebookPage')->andReturn(['success' => true, 'id' => 'fb_123']);
        $this->app->instance(MetaGraphService::class, $mockMeta);

        $payload = [
            'name' => 'Project from Existing Media',
            'content_type' => 'story',
            'repeat_type' => 'instant',
            'existing_media_ids' => [$media->id],
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'facebook_only',
                ],
            ],
        ];

        $response = $this->postJson(route('projects.store'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'direct_published' => true,
            ]);

        $project = ProjectCampaign::where('name', 'Project from Existing Media')->first();
        $this->assertNotNull($project);
        $this->assertTrue($project->mediaFiles->contains('id', $media->id));
    }
}
