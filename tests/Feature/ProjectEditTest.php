<?php

namespace Tests\Feature;

use App\Models\CampaignTarget;
use App\Models\ConnectedAccount;
use App\Models\MediaFile;
use App\Models\ProjectCampaign;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProjectEditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ConnectedAccount $account;
    protected ProjectCampaign $project;
    protected MediaFile $media;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Edit Test',
            'email' => 'admin_edit@test.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->account = ConnectedAccount::create([
            'page_id' => 'page_test_123',
            'page_name' => 'Test Page FB',
            'ig_user_id' => 'ig_test_123',
            'ig_username' => 'testpage_ig',
            'page_access_token' => 'token_123',
            'is_active' => true,
        ]);

        $this->project = ProjectCampaign::create([
            'name' => 'Campaign Edit Original',
            'content_type' => 'story',
            'caption' => 'Original caption',
            'target_time' => '08:00',
            'repeat_type' => 'continuous',
            'start_date' => Carbon::today(),
            'exclude_days' => [0],
            'status' => 'active',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $this->project->id,
            'connected_account_id' => $this->account->id,
            'platform_target' => 'both',
        ]);

        $this->media = MediaFile::create([
            'original_name' => 'photo1.jpg',
            'file_path' => 'uploads/photo1.jpg',
            'file_hash' => md5('photo1'),
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'media_type' => 'image',
        ]);

        $this->project->mediaFiles()->attach($this->media->id);
    }

    public function test_edit_page_renders_with_unified_form_structure(): void
    {
        $response = $this->actingAs($this->admin)->get(route('projects.edit', $this->project->id));

        $response->assertStatus(200);
        $response->assertSee('Edit Campaign: Campaign Edit Original');
        $response->assertSee('formEditProject');
        $response->assertSee('Informasi Campaign');
        $response->assertSee('Jadwal dan Waktu Tayang');
        $response->assertSee('Target Akun Meta');
        $response->assertSee('Materi Media Pool');
        $response->assertSee('dropzone');
        $response->assertSee('Simpan Perubahan Campaign');
    }

    public function test_update_project_with_existing_media_and_modified_fields(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('projects.update', $this->project->id), [
            '_method' => 'PUT',
            'name' => 'Campaign Edit Updated',
            'content_type' => 'post',
            'caption' => 'Updated caption',
            'repeat_type' => 'continuous',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'target_time' => '10:30',
            'exclude_days' => [0, 6],
            'targets' => [
                [
                    'account_id' => $this->account->id,
                    'platform_target' => 'instagram_only',
                ]
            ],
            'existing_media_ids' => [$this->media->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->project->refresh();
        $this->assertEquals('Campaign Edit Updated', $this->project->name);
        $this->assertEquals('post', $this->project->content_type);
        $this->assertEquals('Updated caption', $this->project->caption);
        $this->assertEquals('10:30', $this->project->target_time);
        $this->assertEquals([0, 6], $this->project->exclude_days);

        // Check targets updated
        $this->assertEquals(1, $this->project->targets()->count());
        $this->assertEquals('instagram_only', $this->project->targets()->first()->platform_target);

        // Check media pool preserved
        $this->assertEquals(1, $this->project->mediaFiles()->count());
        $this->assertEquals($this->media->id, $this->project->mediaFiles()->first()->id);
    }
}
