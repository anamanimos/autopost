<?php

namespace Tests\Feature;

use App\Models\ConnectedAccount;
use App\Models\MediaFile;
use App\Models\ProjectCampaign;
use App\Models\Schedule;
use App\Models\User;
use App\Services\MetaGraphService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DirectPostTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ConnectedAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Operator Direct Post',
            'email' => 'operator@directpost.test',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        $this->account = ConnectedAccount::create([
            'page_id' => 'fb_page_direct_123',
            'page_name' => 'Damai Jaya Direct Post',
            'ig_user_id' => 'ig_user_direct_456',
            'ig_username' => 'damaijaya_direct',
            'page_access_token' => 'token_direct_abc',
            'is_active' => true,
        ]);
    }

    public function test_direct_post_validates_required_fields(): void
    {
        // 1. Empty payload
        $response = $this->postJson(route('schedules.directPost'), []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content_type', 'targets']);

        // 2. Missing media (both media_file and existing_media_id empty)
        $response = $this->postJson(route('schedules.directPost'), [
            'content_type' => 'post',
            'targets' => [
                ['account_id' => $this->account->id, 'platform_target' => 'both'],
            ],
        ]);
        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_direct_post_publishes_immediately_with_uploaded_file(): void
    {
        Storage::fake('public');

        $mockMeta = Mockery::mock(MetaGraphService::class);

        // Check IG quota
        $mockMeta->shouldReceive('getContentPublishingLimit')
            ->with('ig_user_direct_456', 'token_direct_abc')
            ->once()
            ->andReturn(['success' => true, 'quota_usage' => 1, 'config' => ['quota_total' => 100]]);

        // Publish to IG Feed Post
        $mockMeta->shouldReceive('publishInstagramFeedPost')
            ->with('ig_user_direct_456', 'token_direct_abc', Mockery::any(), 'Caption Post Langsung Test', false)
            ->once()
            ->andReturn(['success' => true, 'id' => 'ig_post_id_999']);

        // Publish to FB Page
        $mockMeta->shouldReceive('publishFacebookPage')
            ->with('fb_page_direct_123', 'token_direct_abc', Mockery::any(), 'Caption Post Langsung Test', 'post')
            ->once()
            ->andReturn(['success' => true, 'id' => 'fb_post_id_888']);

        $this->app->instance(MetaGraphService::class, $mockMeta);

        $file = UploadedFile::fake()->image('direct_promo.jpg', 800, 800);

        $payload = [
            'name' => 'Promo Kilat Langsung',
            'content_type' => 'post',
            'caption' => 'Caption Post Langsung Test',
            'media_file' => $file,
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'both',
                ],
            ],
        ];

        $response = $this->postJson(route('schedules.directPost'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'completed',
            ]);

        // Verify Campaign was created
        $campaign = ProjectCampaign::where('name', 'Promo Kilat Langsung')->first();
        $this->assertNotNull($campaign);
        $this->assertEquals('once', $campaign->repeat_type);
        $this->assertEquals('completed', $campaign->status);
        $this->assertEquals('post', $campaign->content_type);
        $this->assertEquals('Caption Post Langsung Test', $campaign->caption);

        // Verify Schedule was created and completed
        $schedule = Schedule::where('project_campaign_id', $campaign->id)->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('completed', $schedule->status);

        // Verify MediaFile was created
        $this->assertNotNull($schedule->mediaFile);
    }

    public function test_direct_post_publishes_story_with_existing_media(): void
    {
        $existingMedia = MediaFile::create([
            'original_name' => 'story_banner.png',
            'file_path' => '/storage/uploads/story_banner.png',
            'file_hash' => 'fakehash1234567890',
            'mime_type' => 'image/png',
            'file_size' => 10240,
            'media_type' => 'image',
        ]);

        $mockMeta = Mockery::mock(MetaGraphService::class);

        // Check IG quota
        $mockMeta->shouldReceive('getContentPublishingLimit')
            ->with('ig_user_direct_456', 'token_direct_abc')
            ->once()
            ->andReturn(['success' => true, 'quota_usage' => 2, 'config' => ['quota_total' => 100]]);

        // Publish to IG Story
        $mockMeta->shouldReceive('publishInstagramStory')
            ->with('ig_user_direct_456', 'token_direct_abc', Mockery::any(), false)
            ->once()
            ->andReturn(['success' => true, 'id' => 'ig_story_id_777']);

        $this->app->instance(MetaGraphService::class, $mockMeta);

        $payload = [
            'content_type' => 'story',
            'existing_media_id' => $existingMedia->id,
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'instagram_only',
                ],
            ],
        ];

        $response = $this->postJson(route('schedules.directPost'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'completed',
            ]);

        $schedule = Schedule::latest('id')->first();
        $this->assertEquals($existingMedia->id, $schedule->media_file_id);
        $this->assertEquals('completed', $schedule->status);
    }
}
