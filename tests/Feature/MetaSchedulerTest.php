<?php

namespace Tests\Feature;

use App\Models\CampaignTarget;
use App\Models\ConnectedAccount;
use App\Models\MediaFile;
use App\Models\MetaCredential;
use App\Models\ProjectCampaign;
use App\Models\PublishLog;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MetaSchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create([
            'name' => 'Admin Scheduler',
            'email' => 'admin@scheduler.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin);
    }

    public function test_meta_integration_page_is_accessible(): void
    {
        $response = $this->get(route('meta.index'));
        $response->assertRedirect(route('settings.index', ['tab' => 'meta']));

        $page = $this->get(route('settings.index', ['tab' => 'meta']));
        $page->assertStatus(200);
        $page->assertSee('Integrasi Meta API');
        $page->assertSee('Koneksi');
    }

    public function test_left_sidebar_navigation_rendered_consistently(): void
    {
        $response = $this->get(route('projects.index'));
        $response->assertStatus(200);
        $response->assertSee('Meta Scheduler');
        $response->assertSee('Menu Utama');
        $response->assertSee('Campaigns');
        $response->assertSee('Antrean Posting');
        $response->assertSee('Manajemen User');
        $response->assertSee('Pengaturan');
        $response->assertDontSee('Integrasi Meta API');
    }

    public function test_create_project_page_is_accessible_and_rendered_properly(): void
    {
        $response = $this->get(route('projects.create'));
        $response->assertStatus(200);
        $response->assertSee('Buat Campaign Baru');
        $response->assertSee('Informasi Campaign');
        $response->assertSee('Jadwal dan Waktu Tayang');
        $response->assertSee('Target Akun Meta');
        $response->assertSee('Materi Media Pool');
    }

    public function test_meta_api_connected_banner_contrast(): void
    {
        $cred = MetaCredential::getActive();
        $cred->update([
            'user_access_token' => 'test_token',
            'token_status' => 'valid',
            'token_expires_at' => Carbon::now()->addDays(30),
        ]);

        $response = $this->get(route('settings.index', ['tab' => 'meta']));
        $response->assertStatus(200);
        $response->assertSee('Meta API Terhubung & Siap Digunakan', false);
        $response->assertSee('text-emerald-950');
    }

    public function test_saving_meta_credentials_encrypts_secret(): void
    {
        $response = $this->post(route('meta.updateCredentials'), [
            'app_id' => '1234567890',
            'app_secret' => 'super_secret_app_key_123',
            'graph_version' => 'v22.0',
            'webhook_verify_token' => 'my_verify_token',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'meta']));

        $cred = MetaCredential::first();
        $this->assertNotNull($cred);
        $this->assertEquals('1234567890', $cred->app_id);
        $this->assertEquals('super_secret_app_key_123', $cred->app_secret);
        $this->assertEquals('v22.0', $cred->graph_version);

        // Pastikan di database tersimpan dalam bentuk encrypted (bukan raw plaintext)
        $rawSecret = \DB::table('meta_credentials')->where('id', $cred->id)->value('app_secret');
        $this->assertNotEquals('super_secret_app_key_123', $rawSecret);
    }

    public function test_project_campaign_creation_with_multi_target_and_platform_control(): void
    {
        // 1. Setup 2 Connected Accounts
        $acc1 = ConnectedAccount::create([
            'page_id' => 'page_111',
            'page_name' => 'Sevencols Apparel',
            'ig_user_id' => 'ig_111',
            'ig_username' => 'sevencols',
            'page_access_token' => 'token_111',
            'is_active' => true,
        ]);

        $acc2 = ConnectedAccount::create([
            'page_id' => 'page_222',
            'page_name' => 'Arema Style',
            'ig_user_id' => 'ig_222',
            'ig_username' => 'aremastyle',
            'page_access_token' => 'token_222',
            'is_active' => true,
        ]);

        Storage::fake('public');

        // Buat 2 dummy file gambar dengan dimensi berbeda agar hash berbeda
        $file1 = UploadedFile::fake()->image('post1.jpg', 100, 100);
        $file2 = UploadedFile::fake()->image('post2.jpg', 200, 200);

        $payload = [
            'name' => 'Promo Akhir Pekan',
            'content_type' => 'story',
            'caption' => 'Promo Spesial Weekend!',
            'target_time' => '08:00',
            'repeat_type' => 'continuous',
            'exclude_days' => [0],
            'media_files' => [$file1, $file2],
            'targets' => [
                [
                    'account_id' => $acc1->id,
                    'platform_target' => 'both', // Target 1 ke Both
                ],
                [
                    'account_id' => $acc2->id,
                    'platform_target' => 'instagram_only', // Target 2 ke Instagram saja
                ],
            ],
        ];

        $response = $this->post(route('projects.store'), $payload);
        $response->assertStatus(302);

        $project = ProjectCampaign::first();
        $this->assertNotNull($project);
        $this->assertEquals('Promo Akhir Pekan', $project->name);
        $this->assertEquals('story', $project->content_type);
        $this->assertEquals('continuous', $project->repeat_type);

        // Verifikasi Pivot Targets (Section 2.1)
        $targets = $project->targets;
        $this->assertCount(2, $targets);

        $target1 = $targets->where('connected_account_id', $acc1->id)->first();
        $this->assertNotNull($target1);
        $this->assertEquals('both', $target1->platform_target);
        $this->assertTrue($target1->targetsFacebook());
        $this->assertTrue($target1->targetsInstagram());

        $target2 = $targets->where('connected_account_id', $acc2->id)->first();
        $this->assertNotNull($target2);
        $this->assertEquals('instagram_only', $target2->platform_target);
        $this->assertFalse($target2->targetsFacebook());
        $this->assertTrue($target2->targetsInstagram());

        // Verifikasi Media Pool
        $this->assertCount(2, $project->mediaFiles);

        // Verifikasi Rolling Buffer 29 hari diinisialisasi
        $schedulesCount = Schedule::where('project_campaign_id', $project->id)->count();
        $this->assertGreaterThan(20, $schedulesCount);
    }

    public function test_sha256_media_deduplication(): void
    {
        $acc = ConnectedAccount::create([
            'page_id' => 'page_dedup',
            'page_name' => 'Dedup Test Page',
            'page_access_token' => 'token_dedup',
            'is_active' => true,
        ]);

        Storage::fake('public');

        // Buat 2 file dengan dimensi identik (konten & hash sama persis)
        $file1 = UploadedFile::fake()->image('duplicate1.jpg', 150, 150);
        $file2 = UploadedFile::fake()->image('duplicate2.jpg', 150, 150);

        $payload = [
            'name' => 'Campaign Deduplikasi',
            'content_type' => 'post',
            'target_time' => '12:00',
            'repeat_type' => 'continuous',
            'media_files' => [$file1, $file2],
            'targets' => [
                ['account_id' => $acc->id, 'platform_target' => 'both'],
            ],
        ];

        $this->post(route('projects.store'), $payload);

        // Di tabel media_files hanya tersimpan 1 baris karena hash sama
        $this->assertEquals(1, MediaFile::count());
    }

    public function test_once_repeat_mode_rejects_time_less_than_30_minutes(): void
    {
        $acc = ConnectedAccount::create([
            'page_id' => 'page_333',
            'page_name' => 'Toko Tes',
            'page_access_token' => 'token_333',
            'is_active' => true,
        ]);

        Storage::fake('public');
        $file = UploadedFile::fake()->image('once.jpg');

        // Waktu hanya +5 menit dari sekarang (harus ditolak)
        $tooSoon = Carbon::now()->addMinutes(5)->format('H:i');

        $payload = [
            'name' => 'Flash Sale Kilat',
            'content_type' => 'post',
            'target_time' => $tooSoon,
            'repeat_type' => 'once',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'media_files' => [$file],
            'targets' => [
                ['account_id' => $acc->id, 'platform_target' => 'both'],
            ],
        ];

        $response = $this->post(route('projects.store'), $payload);
        $response->assertSessionHas('error');
        $this->assertEquals(0, ProjectCampaign::count());
    }

    public function test_soft_deactivation_badge_detection(): void
    {
        $accActive = ConnectedAccount::create([
            'page_id' => 'page_active',
            'page_name' => 'Akun Masih Ada',
            'page_access_token' => 'token_act',
            'is_active' => true,
        ]);

        $accInactive = ConnectedAccount::create([
            'page_id' => 'page_deleted',
            'page_name' => 'Akun Terhapus di Meta',
            'page_access_token' => 'token_inact',
            'is_active' => false, // Soft-deactivated
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Campaign Testing Warning',
            'content_type' => 'story',
            'target_time' => '09:00',
            'repeat_type' => 'continuous',
            'status' => 'active',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $project->id,
            'connected_account_id' => $accActive->id,
            'platform_target' => 'both',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $project->id,
            'connected_account_id' => $accInactive->id,
            'platform_target' => 'both',
        ]);

        // Cek bahwa project mendeteksi adanya aset nonaktif
        $this->assertTrue($project->hasInactiveAccount());
    }

    public function test_publish_logs_recorded_per_target_per_platform(): void
    {
        $acc = ConnectedAccount::create([
            'page_id' => 'page_555',
            'page_name' => 'Test Page',
            'ig_user_id' => 'ig_555',
            'ig_username' => 'testig',
            'page_access_token' => 'token_555',
            'is_active' => true,
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Test Log Campaign',
            'content_type' => 'story',
            'target_time' => '10:00',
            'repeat_type' => 'continuous',
            'status' => 'active',
        ]);

        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'test_item_123',
            'media_path' => '/storage/uploads/test.jpg',
            'target_date' => Carbon::today(),
            'target_time' => '10:00',
            'status' => 'completed',
        ]);

        // Catat log Instagram
        PublishLog::create([
            'schedule_id' => $schedule->id,
            'project_campaign_id' => $project->id,
            'connected_account_id' => $acc->id,
            'platform' => 'instagram',
            'content_type' => 'story',
            'action_status' => 'success',
            'media_id' => '17999888777',
            'executed_at' => Carbon::now(),
        ]);

        // Catat log Facebook
        PublishLog::create([
            'schedule_id' => $schedule->id,
            'project_campaign_id' => $project->id,
            'connected_account_id' => $acc->id,
            'platform' => 'facebook',
            'content_type' => 'story',
            'action_status' => 'success',
            'media_id' => '999888777666',
            'executed_at' => Carbon::now(),
        ]);

        $this->assertEquals(2, $schedule->publishLogs()->count());
        $this->assertEquals(1, $schedule->publishLogs()->where('platform', 'instagram')->count());
        $this->assertEquals(1, $schedule->publishLogs()->where('platform', 'facebook')->count());
    }

    public function test_can_add_schedule_manually_to_project(): void
    {
        $project = ProjectCampaign::create([
            'name' => 'Project Manual Schedule',
            'content_type' => 'post',
            'target_time' => '14:00',
            'repeat_type' => 'continuous',
            'status' => 'active',
        ]);

        $media = MediaFile::create([
            'original_name' => 'manual.jpg',
            'file_path' => '/storage/uploads/manual.jpg',
            'file_hash' => 'dummyhash123',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'media_type' => 'image',
        ]);
        $project->mediaFiles()->attach($media->id);

        $response = $this->postJson(route('projects.addSchedule', $project->id), [
            'target_date' => Carbon::tomorrow()->format('Y-m-d'),
            'target_time' => '15:30',
            'media_file_id' => $media->id,
            'notes' => 'Catatan jadwal manual',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $schedule = Schedule::where('project_campaign_id', $project->id)->first();
        $this->assertNotNull($schedule);
        $this->assertEquals(Carbon::tomorrow()->format('Y-m-d'), $schedule->target_date->format('Y-m-d'));
        $this->assertEquals('15:30', $schedule->target_time);
        $this->assertEquals('pending', $schedule->status);
        $this->assertEquals('Catatan jadwal manual', $schedule->notes);
    }

    public function test_can_delete_schedule_from_project(): void
    {
        $project = ProjectCampaign::create([
            'name' => 'Project Delete Schedule',
            'content_type' => 'post',
            'target_time' => '10:00',
            'repeat_type' => 'continuous',
            'status' => 'active',
        ]);

        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'sch_to_delete',
            'media_path' => '/storage/uploads/test.jpg',
            'target_date' => Carbon::tomorrow()->format('Y-m-d'),
            'target_time' => '10:00',
            'status' => 'pending',
        ]);

        $response = $this->deleteJson(route('schedules.destroy', $schedule->id));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('schedules', [
            'id' => $schedule->id,
        ]);
    }

    public function test_updating_project_adjusts_schedules_on_end_date_and_exclude_days(): void
    {
        $acc = ConnectedAccount::create([
            'page_id' => '123456',
            'page_name' => 'Page Test Sync',
            'is_active' => true,
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Project Date Sync Test',
            'content_type' => 'post',
            'target_time' => '09:00',
            'repeat_type' => 'until_date',
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(10),
            'status' => 'active',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $project->id,
            'connected_account_id' => $acc->id,
            'platform_target' => 'both',
        ]);

        // Buat jadwal pending di luar batas baru (hari ke-15)
        $outsideSchedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'outside_sch',
            'media_path' => '/storage/uploads/test.jpg',
            'target_date' => Carbon::today()->addDays(15)->format('Y-m-d'),
            'target_time' => '09:00',
            'status' => 'pending',
        ]);

        // Buat jadwal pending di dalam batas baru (hari ke-3)
        $insideSchedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'inside_sch',
            'media_path' => '/storage/uploads/test.jpg',
            'target_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'target_time' => '09:00',
            'status' => 'pending',
        ]);

        // Update project: perpendek end_date ke hari ke-5, dan ubah jam ke 11:30
        $newEndDate = Carbon::today()->addDays(5)->format('Y-m-d');
        $response = $this->put(route('projects.update', $project->id), [
            'name' => 'Project Date Sync Test Updated',
            'content_type' => 'post',
            'target_time' => '11:30',
            'repeat_type' => 'until_date',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => $newEndDate,
            'targets' => [
                ['account_id' => $acc->id, 'platform_target' => 'both'],
            ],
        ]);

        $response->assertSessionHas('success');

        // Jadwal hari ke-15 harus terhapus karena melebihi end_date baru
        $this->assertDatabaseMissing('schedules', ['id' => $outsideSchedule->id]);

        // Jadwal hari ke-3 tetap ada dan jamnya terupdate ke 11:30
        $this->assertDatabaseHas('schedules', [
            'id' => $insideSchedule->id,
            'target_time' => '11:30',
        ]);
    }

    public function test_can_sync_project_schedule_buffer(): void
    {
        $project = ProjectCampaign::create([
            'name' => 'Project Buffer Test',
            'content_type' => 'post',
            'target_time' => '10:00',
            'repeat_type' => 'continuous',
            'status' => 'active',
        ]);

        $media = MediaFile::create([
            'original_name' => 'buffer_test.jpg',
            'file_path' => '/storage/uploads/buffer_test.jpg',
            'file_hash' => 'bufferdummyhash',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'media_type' => 'image',
        ]);
        $project->mediaFiles()->attach($media->id);

        $response = $this->postJson(route('projects.syncBuffer', $project->id));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Buffer harus terisi
        $this->assertGreaterThan(0, $project->schedules()->where('status', 'pending')->count());
    }

    public function test_show_project_self_heals_and_prunes_orphaned_schedules_exceeding_end_date(): void
    {
        $project = ProjectCampaign::create([
            'name' => 'Self Healing Test Project',
            'content_type' => 'post',
            'target_time' => '19:30',
            'repeat_type' => 'until_date',
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
            'status' => 'active',
        ]);

        $media = MediaFile::create([
            'original_name' => 'heal.jpg',
            'file_path' => '/storage/uploads/heal.jpg',
            'file_hash' => 'healhash123',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'media_type' => 'image',
        ]);
        $project->mediaFiles()->attach($media->id);

        $validSchedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'valid_sch',
            'media_path' => '/storage/uploads/heal.jpg',
            'target_date' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'target_time' => '19:30',
            'status' => 'pending',
        ]);

        $orphanedSchedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'orphaned_sch',
            'media_path' => '/storage/uploads/heal.jpg',
            'target_date' => Carbon::today()->addDays(15)->format('Y-m-d'),
            'target_time' => '19:30',
            'status' => 'pending',
        ]);

        $response = $this->get(route('projects.show', $project->id));
        $response->assertStatus(200);

        // Jadwal orphaned harus langsung terhapus oleh self-healing
        $this->assertDatabaseMissing('schedules', ['id' => $orphanedSchedule->id]);
        $this->assertDatabaseHas('schedules', ['id' => $validSchedule->id]);

        // Tanggal terjauh pada response tidak boleh menyebut hari ke-15
        $orphanedDateFormatted = Carbon::today()->addDays(15)->translatedFormat('d F Y');
        $response->assertDontSee($orphanedDateFormatted);
    }

    public function test_maintain_schedule_buffer_command_prunes_orphaned_schedules_globally(): void
    {
        $project = ProjectCampaign::create([
            'name' => 'Command Prune Test',
            'content_type' => 'post',
            'target_time' => '19:30',
            'repeat_type' => 'until_date',
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(3),
            'status' => 'active',
        ]);

        $orphanedSchedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'cmd_orphaned',
            'media_path' => '/storage/uploads/test.jpg',
            'target_date' => Carbon::today()->addDays(10)->format('Y-m-d'),
            'target_time' => '19:30',
            'status' => 'pending',
        ]);

        \Illuminate\Support\Facades\Artisan::call('meta:maintain-buffer');

        $this->assertDatabaseMissing('schedules', ['id' => $orphanedSchedule->id]);
    }

    public function test_can_duplicate_project_with_targets_and_media(): void
    {
        $acc = ConnectedAccount::create([
            'meta_credential_id' => MetaCredential::getActive()->id,
            'page_id' => 'page_dup_123',
            'page_name' => 'Duplication Test Page',
            'is_active' => true,
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Original Campaign',
            'content_type' => 'post',
            'caption' => 'Original Caption',
            'target_time' => '14:00',
            'images_per_post' => 1,
            'repeat_type' => 'continuous',
            'status' => 'active',
        ]);

        CampaignTarget::create([
            'project_campaign_id' => $project->id,
            'connected_account_id' => $acc->id,
            'platform_target' => 'both',
        ]);

        $media = MediaFile::create([
            'original_name' => 'dup.jpg',
            'file_path' => '/storage/uploads/dup.jpg',
            'file_hash' => 'duphash123',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'media_type' => 'image',
        ]);
        $project->mediaFiles()->attach($media->id, ['sort_order' => 1]);

        // 1. Endpoint duplicate meredirect ke create dengan query duplicate_from
        $responseRedirect = $this->get(route('projects.duplicate', $project->id));
        $responseRedirect->assertRedirect(route('projects.create', ['duplicate_from' => $project->id]));

        // 2. Buka halaman create dengan parameter duplicate_from
        $responsePage = $this->get(route('projects.create', ['duplicate_from' => $project->id]));
        $responsePage->assertStatus(200);
        $responsePage->assertSee('Original Campaign (Salinan)');
        $responsePage->assertSee('Original Caption');
        $responsePage->assertSee('Mode Duplikasi Campaign (Belum Disimpan)');

        // Pastikan belum ada project baru tersimpan di database sebelum user klik simpan
        $this->assertDatabaseCount('project_campaigns', 1);

        // 3. Simpan project baru melalui POST /projects dengan existing_media_ids
        $storeResponse = $this->postJson(route('projects.store'), [
            'name' => 'Original Campaign (Salinan)',
            'content_type' => 'post',
            'caption' => 'Original Caption',
            'target_time' => '14:00',
            'repeat_type' => 'continuous',
            'start_date' => now()->format('Y-m-d'),
            'targets' => [
                [
                    'account_id' => $acc->id,
                    'platform_target' => 'both',
                ],
            ],
            'existing_media_ids' => [$media->id],
        ]);

        $storeResponse->assertStatus(200);
        $storeResponse->assertJson(['success' => true]);

        // Pastikan project baru sekarang tersimpan
        $this->assertDatabaseCount('project_campaigns', 2);
        $duplicated = ProjectCampaign::where('name', 'Original Campaign (Salinan)')->first();
        $this->assertNotNull($duplicated);
        $this->assertEquals('14:00', $duplicated->target_time);
        $this->assertEquals('Original Caption', $duplicated->caption);

        // Pastikan target akun tersimpan
        $this->assertDatabaseHas('campaign_targets', [
            'project_campaign_id' => $duplicated->id,
            'connected_account_id' => $acc->id,
            'platform_target' => 'both',
        ]);

        // Pastikan media pool tersimpan
        $this->assertEquals(1, $duplicated->mediaFiles()->count());

        // Pastikan initial schedule buffer terbuat
        $this->assertGreaterThan(0, $duplicated->schedules()->count());
    }

    public function test_schedules_index_renders_with_client_side_filter_attributes(): void
    {
        $cred = MetaCredential::create([
            'app_id' => 'app_sch_123',
            'app_secret' => 'sec_sch_123',
            'access_token' => 'EAAB_token',
            'token_type' => 'user',
            'is_active' => true,
        ]);

        $account = ConnectedAccount::create([
            'meta_credential_id' => $cred->id,
            'page_id' => 'page_sch_123',
            'page_name' => 'Schedule Test Page',
            'is_active' => true,
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Filter Test Campaign',
            'content_type' => 'story',
            'caption' => 'Caption test filter',
            'target_time' => '10:00',
            'repeat_type' => 'continuous',
            'start_date' => now()->format('Y-m-d'),
        ]);

        $project->targets()->create([
            'connected_account_id' => $account->id,
            'platform_target' => 'both',
        ]);

        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'TEST-001',
            'media_path' => 'uploads/test.jpg',
            'target_date' => now()->format('Y-m-d'),
            'target_time' => '10:00',
            'status' => 'pending',
            'notes' => 'Test schedule note',
        ]);

        $response = $this->get(route('schedules.index'));
        $response->assertStatus(200);
        $response->assertSee('Antrean Posting &amp; Monitoring', false);
        $response->assertSee('data-status="pending"', false);
        $response->assertSee('data-content-type="story"', false);
        $response->assertSee('data-platforms="both"', false);
        $response->assertSee('x-show="isRowVisible($el)"', false);
        $response->assertSee('isRowVisible(el)', false);
        $response->assertSee('filterVersion', false);
    }
}
