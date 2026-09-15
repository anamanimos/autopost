<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\ProjectCampaign;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GlobalSortingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@damaijaya.my.id',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    protected function createMedia(string $name = 'test.jpg'): MediaFile
    {
        return MediaFile::create([
            'original_name' => $name,
            'file_path' => 'media/' . $name,
            'file_hash' => md5(uniqid($name, true)),
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'media_type' => 'image',
        ]);
    }

    protected function createSchedule(array $attributes): Schedule
    {
        return Schedule::create(array_merge([
            'item_code' => 'ITEM_' . uniqid(),
            'media_path' => '/storage/test.jpg',
            'status' => 'pending',
        ], $attributes));
    }

    public function test_schedules_sorting_by_date_globally(): void
    {
        $campaign = ProjectCampaign::create([
            'name' => 'Campaign Utama',
            'content_type' => 'story',
            'target_time' => '10:00',
        ]);

        $media = $this->createMedia('test.jpg');

        $sch1 = $this->createSchedule([
            'project_campaign_id' => $campaign->id,
            'media_file_id' => $media->id,
            'target_date' => Carbon::parse('2026-01-01'),
            'target_time' => '10:00',
            'status' => 'pending',
        ]);

        $sch2 = $this->createSchedule([
            'project_campaign_id' => $campaign->id,
            'media_file_id' => $media->id,
            'target_date' => Carbon::parse('2026-06-01'),
            'target_time' => '10:00',
            'status' => 'pending',
        ]);

        $sch3 = $this->createSchedule([
            'project_campaign_id' => $campaign->id,
            'media_file_id' => $media->id,
            'target_date' => Carbon::parse('2026-12-01'),
            'target_time' => '10:00',
            'status' => 'pending',
        ]);

        // Ascending sort (earliest first: 2026-01-01)
        $responseAsc = $this->actingAs($this->admin)->get(route('schedules.index', [
            'sort' => 'date',
            'direction' => 'asc',
        ]));
        $responseAsc->assertStatus(200);
        $schedulesAsc = $responseAsc->viewData('schedules');
        $this->assertEquals($sch1->id, $schedulesAsc->first()->id);
        $this->assertEquals($sch3->id, $schedulesAsc->last()->id);

        // Descending sort (latest first: 2026-12-01)
        $responseDesc = $this->actingAs($this->admin)->get(route('schedules.index', [
            'sort' => 'date',
            'direction' => 'desc',
        ]));
        $responseDesc->assertStatus(200);
        $schedulesDesc = $responseDesc->viewData('schedules');
        $this->assertEquals($sch3->id, $schedulesDesc->first()->id);
        $this->assertEquals($sch1->id, $schedulesDesc->last()->id);
    }

    public function test_schedules_sorting_by_campaign_name_globally(): void
    {
        $campaignA = ProjectCampaign::create(['name' => 'Alpha Campaign', 'content_type' => 'story', 'target_time' => '09:00']);
        $campaignZ = ProjectCampaign::create(['name' => 'Zulu Campaign', 'content_type' => 'post', 'target_time' => '15:00']);

        $mediaA = $this->createMedia('a.jpg');
        $mediaZ = $this->createMedia('z.jpg');

        $schA = $this->createSchedule([
            'project_campaign_id' => $campaignA->id,
            'media_file_id' => $mediaA->id,
            'target_date' => Carbon::parse('2026-03-01'),
            'target_time' => '09:00',
            'status' => 'pending',
        ]);
        $schZ = $this->createSchedule([
            'project_campaign_id' => $campaignZ->id,
            'media_file_id' => $mediaZ->id,
            'target_date' => Carbon::parse('2026-03-01'),
            'target_time' => '15:00',
            'status' => 'pending',
        ]);

        // Ascending: Alpha first
        $responseAsc = $this->actingAs($this->admin)->get(route('schedules.index', [
            'sort' => 'campaign',
            'direction' => 'asc',
        ]));
        $responseAsc->assertStatus(200);
        $schedulesAsc = $responseAsc->viewData('schedules');
        $this->assertEquals($schA->id, $schedulesAsc->first()->id);

        // Descending: Zulu first
        $responseDesc = $this->actingAs($this->admin)->get(route('schedules.index', [
            'sort' => 'campaign',
            'direction' => 'desc',
        ]));
        $responseDesc->assertStatus(200);
        $schedulesDesc = $responseDesc->viewData('schedules');
        $this->assertEquals($schZ->id, $schedulesDesc->first()->id);
    }

    public function test_schedules_pagination_preserves_sort_parameters(): void
    {
        $campaign = ProjectCampaign::create(['name' => 'Test Bulk', 'content_type' => 'story', 'target_time' => '08:00']);
        $media = $this->createMedia('bulk.jpg');

        // Create 35 schedules to trigger pagination (> 30 per page)
        for ($i = 1; $i <= 35; $i++) {
            $this->createSchedule([
                'project_campaign_id' => $campaign->id,
                'media_file_id' => $media->id,
                'target_date' => Carbon::parse("2026-01-01")->addDays($i),
                'target_time' => '08:00',
                'status' => 'pending',
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('schedules.index', [
            'sort' => 'date',
            'direction' => 'asc',
            'page' => 1,
        ]));

        $response->assertStatus(200);
        // Pagination link must include sort=date and direction=asc
        $response->assertSee('sort=date');
        $response->assertSee('direction=asc');
    }

    public function test_projects_sorting_by_name_and_created_at(): void
    {
        $projB = ProjectCampaign::create(['name' => 'Bravo Project', 'content_type' => 'story', 'target_time' => '08:00']);
        $projA = ProjectCampaign::create(['name' => 'Alpha Project', 'content_type' => 'post', 'target_time' => '12:00']);

        // Sort Name Asc
        $responseAsc = $this->actingAs($this->admin)->get(route('projects.index', [
            'sort' => 'name',
            'direction' => 'asc',
        ]));
        $responseAsc->assertStatus(200);
        $projectsAsc = $responseAsc->viewData('projects');
        $this->assertEquals($projA->id, $projectsAsc->first()->id);
        $this->assertEquals($projB->id, $projectsAsc->last()->id);

        // Sort Name Desc
        $responseDesc = $this->actingAs($this->admin)->get(route('projects.index', [
            'sort' => 'name',
            'direction' => 'desc',
        ]));
        $responseDesc->assertStatus(200);
        $projectsDesc = $responseDesc->viewData('projects');
        $this->assertEquals($projB->id, $projectsDesc->first()->id);
        $this->assertEquals($projA->id, $projectsDesc->last()->id);
    }

    public function test_users_sorting_by_name(): void
    {
        $userA = User::create([
            'name' => 'Aaron Smith',
            'email' => 'aaron@test.com',
            'password' => Hash::make('pass123'),
            'role' => 'operator',
            'status' => 'active',
        ]);

        $userZ = User::create([
            'name' => 'Zachary Taylor',
            'email' => 'zach@test.com',
            'password' => Hash::make('pass123'),
            'role' => 'operator',
            'status' => 'active',
        ]);

        // Ascending sort by name
        $responseAsc = $this->actingAs($this->admin)->get(route('users.index', [
            'sort' => 'name',
            'direction' => 'asc',
        ]));
        $responseAsc->assertStatus(200);
        $usersAsc = $responseAsc->viewData('users');
        $this->assertEquals('Aaron Smith', $usersAsc->first()->name);

        // Descending sort by name
        $responseDesc = $this->actingAs($this->admin)->get(route('users.index', [
            'sort' => 'name',
            'direction' => 'desc',
        ]));
        $responseDesc->assertStatus(200);
        $usersDesc = $responseDesc->viewData('users');
        $this->assertEquals('Zachary Taylor', $usersDesc->first()->name);
    }

    public function test_project_show_schedules_sorting(): void
    {
        $campaign = ProjectCampaign::create(['name' => 'Detail Project', 'content_type' => 'story', 'target_time' => '10:00']);
        $media = $this->createMedia('detail.jpg');

        $schEarly = $this->createSchedule([
            'project_campaign_id' => $campaign->id,
            'media_file_id' => $media->id,
            'target_date' => Carbon::parse('2026-02-01'),
            'target_time' => '10:00',
            'status' => 'pending',
        ]);

        $schLate = $this->createSchedule([
            'project_campaign_id' => $campaign->id,
            'media_file_id' => $media->id,
            'target_date' => Carbon::parse('2026-11-01'),
            'target_time' => '10:00',
            'status' => 'pending',
        ]);

        // Ascending
        $responseAsc = $this->actingAs($this->admin)->get(route('projects.show', [
            'project' => $campaign->id,
            'sort' => 'date',
            'direction' => 'asc',
        ]));
        $responseAsc->assertStatus(200);
        $projectAsc = $responseAsc->viewData('project');
        $this->assertEquals($schEarly->id, $projectAsc->schedules->first()->id);

        // Descending
        $responseDesc = $this->actingAs($this->admin)->get(route('projects.show', [
            'project' => $campaign->id,
            'sort' => 'date',
            'direction' => 'desc',
        ]));
        $responseDesc->assertStatus(200);
        $projectDesc = $responseDesc->viewData('project');
        $this->assertEquals($schLate->id, $projectDesc->schedules->first()->id);
    }
}
