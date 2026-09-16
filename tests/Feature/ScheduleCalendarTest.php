<?php

namespace Tests\Feature;

use App\Models\CampaignTarget;
use App\Models\ConnectedAccount;
use App\Models\MediaFile;
use App\Models\ProjectCampaign;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ConnectedAccount $account;
    protected ProjectCampaign $projectA;
    protected ProjectCampaign $projectB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Admin Calendar Test',
            'email' => 'admin@calendar.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        $this->account = ConnectedAccount::create([
            'page_id' => 'fb_cal_123',
            'page_name' => 'Damai Jaya Calendar Page',
            'ig_user_id' => 'ig_cal_456',
            'ig_username' => 'damaijaya_cal',
            'page_access_token' => 'token_cal_xyz',
            'is_active' => true,
        ]);

        $this->projectA = ProjectCampaign::create([
            'name' => 'Campaign Kemeja Pria',
            'content_type' => 'post',
            'caption' => 'Katalog kemeja pria terbaru',
            'target_time' => '10:00',
            'repeat_type' => 'continuous',
            'status' => 'active',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $this->projectA->id,
            'connected_account_id' => $this->account->id,
            'platform_target' => 'both',
        ]);

        $this->projectB = ProjectCampaign::create([
            'name' => 'Story Promo Flash Sale',
            'content_type' => 'story',
            'caption' => 'Promo flash sale 50%',
            'target_time' => '14:00',
            'repeat_type' => 'once',
            'status' => 'active',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $this->projectB->id,
            'connected_account_id' => $this->account->id,
            'platform_target' => 'instagram_only',
        ]);
    }

    public function test_calendar_events_validates_required_dates(): void
    {
        $response = $this->getJson(route('schedules.calendarEvents'));
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start', 'end']);
    }

    public function test_calendar_events_returns_schedules_in_date_range(): void
    {
        $today = Carbon::today();

        // Event 1: Today (Project A)
        Schedule::create([
            'project_campaign_id' => $this->projectA->id,
            'item_code' => 'sch_today_01',
            'media_path' => '/storage/uploads/test.jpg',
            'media_paths' => ['/storage/uploads/test.jpg'],
            'target_date' => $today->format('Y-m-d'),
            'target_time' => '10:00',
            'status' => 'pending',
            'notes' => 'Jadwal hari ini',
        ]);

        // Event 2: Tomorrow (Project B)
        Schedule::create([
            'project_campaign_id' => $this->projectB->id,
            'item_code' => 'sch_tomorrow_02',
            'media_path' => '/storage/uploads/test.jpg',
            'media_paths' => ['/storage/uploads/test.jpg'],
            'target_date' => $today->copy()->addDay()->format('Y-m-d'),
            'target_time' => '14:00',
            'status' => 'completed',
            'notes' => 'Jadwal besok',
        ]);

        // Event 3: Next Month (Out of range)
        Schedule::create([
            'project_campaign_id' => $this->projectA->id,
            'item_code' => 'sch_next_month_03',
            'media_path' => '/storage/uploads/test.jpg',
            'media_paths' => ['/storage/uploads/test.jpg'],
            'target_date' => $today->copy()->addMonths(2)->format('Y-m-d'),
            'target_time' => '10:00',
            'status' => 'pending',
        ]);

        $start = $today->format('Y-m-d');
        $end = $today->copy()->addDays(7)->format('Y-m-d');

        $response = $this->getJson(route('schedules.calendarEvents', [
            'start' => $start,
            'end' => $end,
        ]));

        $response->assertOk()
            ->assertJson(['success' => true, 'count' => 2])
            ->assertJsonFragment(['item_code' => 'sch_today_01'])
            ->assertJsonFragment(['item_code' => 'sch_tomorrow_02'])
            ->assertJsonMissing(['item_code' => 'sch_next_month_03']);
    }

    public function test_calendar_events_filters_by_status_and_project_and_search(): void
    {
        $today = Carbon::today();

        Schedule::create([
            'project_campaign_id' => $this->projectA->id,
            'item_code' => 'item_pending_a',
            'media_path' => '/storage/uploads/test.jpg',
            'media_paths' => ['/storage/uploads/test.jpg'],
            'target_date' => $today->format('Y-m-d'),
            'target_time' => '10:00',
            'status' => 'pending',
        ]);

        Schedule::create([
            'project_campaign_id' => $this->projectA->id,
            'item_code' => 'item_completed_a',
            'media_path' => '/storage/uploads/test.jpg',
            'media_paths' => ['/storage/uploads/test.jpg'],
            'target_date' => $today->format('Y-m-d'),
            'target_time' => '11:00',
            'status' => 'completed',
        ]);

        Schedule::create([
            'project_campaign_id' => $this->projectB->id,
            'item_code' => 'item_failed_b',
            'media_path' => '/storage/uploads/test.jpg',
            'media_paths' => ['/storage/uploads/test.jpg'],
            'target_date' => $today->format('Y-m-d'),
            'target_time' => '14:00',
            'status' => 'failed',
        ]);

        $range = ['start' => $today->format('Y-m-d'), 'end' => $today->format('Y-m-d')];

        // Filter by status: pending
        $resPending = $this->getJson(route('schedules.calendarEvents', array_merge($range, ['status' => 'pending'])));
        $resPending->assertOk()->assertJson(['count' => 1])
            ->assertJsonFragment(['item_code' => 'item_pending_a']);

        // Filter by project_id: projectB
        $resProjectB = $this->getJson(route('schedules.calendarEvents', array_merge($range, ['project_id' => $this->projectB->id])));
        $resProjectB->assertOk()->assertJson(['count' => 1])
            ->assertJsonFragment(['item_code' => 'item_failed_b']);

        // Filter by search: "Kemeja"
        $resSearch = $this->getJson(route('schedules.calendarEvents', array_merge($range, ['search' => 'Kemeja'])));
        $resSearch->assertOk()->assertJson(['count' => 2]);
    }

    public function test_recent_media_endpoint_returns_json_list(): void
    {
        MediaFile::create([
            'original_name' => 'sample_shirt.jpg',
            'file_path' => '/storage/uploads/sample_shirt.jpg',
            'file_hash' => 'hash_sample_shirt_123',
            'mime_type' => 'image/jpeg',
            'file_size' => 20480,
            'media_type' => 'image',
        ]);

        $response = $this->getJson(route('schedules.recentMedia'));
        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonFragment(['original_name' => 'sample_shirt.jpg']);
    }
}
