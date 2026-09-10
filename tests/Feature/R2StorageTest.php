<?php

namespace Tests\Feature;

use App\Models\ConnectedAccount;
use App\Models\MediaFile;
use App\Models\MetaCredential;
use App\Models\ProjectCampaign;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class R2StorageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($this->user);

        MetaCredential::create([
            'app_id' => 'test_app_123',
            'app_secret' => 'test_secret_123',
            'access_token' => 'EAAB_test_token',
            'token_type' => 'user',
            'is_active' => true,
        ]);
    }

    public function test_r2_disk_and_media_disk_configuration_registered(): void
    {
        $this->assertArrayHasKey('r2', config('filesystems.disks'));
        $this->assertEquals('s3', config('filesystems.disks.r2.driver'));
        $this->assertNotNull(config('filesystems.media_disk'));
    }

    public function test_media_file_url_accessor_with_local_and_r2_disk(): void
    {
        // 1. Mode Local
        Config::set('filesystems.media_disk', 'local');
        $media = MediaFile::create([
            'original_name' => 'sample.jpg',
            'file_path' => '/storage/uploads/sample.jpg',
            'file_hash' => 'hash_sample_123',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'media_type' => 'image',
        ]);

        $this->assertStringContainsString('/storage/uploads/sample.jpg', $media->url);

        // 2. Mode R2 dengan custom domain / public URL
        Config::set('filesystems.media_disk', 'r2');
        Config::set('filesystems.disks.r2.url', 'https://media.damaijaya.my.id');

        $this->assertEquals('https://media.damaijaya.my.id/uploads/sample.jpg', $media->url);

        // 3. Jika file_path sudah berupa URL penuh
        $mediaRemote = MediaFile::create([
            'original_name' => 'remote.jpg',
            'file_path' => 'https://cdn.example.com/assets/remote.jpg',
            'file_hash' => 'hash_remote_456',
            'mime_type' => 'image/jpeg',
            'file_size' => 2048,
            'media_type' => 'image',
        ]);

        $this->assertEquals('https://cdn.example.com/assets/remote.jpg', $mediaRemote->url);
    }

    public function test_schedule_url_accessor_with_r2_disk(): void
    {
        Config::set('filesystems.media_disk', 'r2');
        Config::set('filesystems.disks.r2.url', 'https://media.damaijaya.my.id');

        $project = ProjectCampaign::create([
            'name' => 'Test Campaign R2',
            'content_type' => 'post',
            'target_time' => '10:00',
            'repeat_type' => 'continuous',
            'status' => 'active',
        ]);

        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'r2_sched_1',
            'media_path' => '/storage/uploads/banner.jpg',
            'media_paths' => ['/storage/uploads/banner.jpg', '/storage/uploads/banner2.jpg'],
            'target_date' => now()->format('Y-m-d'),
            'target_time' => '10:00',
            'status' => 'pending',
        ]);

        $this->assertEquals('https://media.damaijaya.my.id/uploads/banner.jpg', $schedule->media_url);
        $this->assertEquals([
            'https://media.damaijaya.my.id/uploads/banner.jpg',
            'https://media.damaijaya.my.id/uploads/banner2.jpg',
        ], $schedule->media_urls);
    }

    public function test_uploading_media_to_r2_with_deduplication(): void
    {
        Storage::fake('r2');
        Config::set('filesystems.media_disk', 'r2');

        $acc = ConnectedAccount::create([
            'meta_credential_id' => MetaCredential::getActive()->id,
            'page_id' => 'page_r2_123',
            'page_name' => 'R2 Page',
            'is_active' => true,
        ]);

        $file1 = UploadedFile::fake()->image('photo_r2.jpg', 200, 200);

        // Upload pertama
        $response = $this->postJson(route('projects.store'), [
            'name' => 'Project Upload R2',
            'content_type' => 'post',
            'caption' => 'R2 Upload Caption',
            'target_time' => '12:00',
            'repeat_type' => 'continuous',
            'start_date' => now()->format('Y-m-d'),
            'targets' => [
                ['account_id' => $acc->id, 'platform_target' => 'both'],
            ],
            'media_files' => [$file1],
        ]);

        $response->assertStatus(200);

        $media = MediaFile::where('original_name', 'photo_r2.jpg')->first();
        $this->assertNotNull($media);
        $this->assertStringStartsWith('uploads/', $media->file_path);

        // Pastikan file tersimpan di disk fake r2
        Storage::disk('r2')->assertExists($media->file_path);

        // Upload kedua dengan file konten yang sama (Deduplikasi SHA-256)
        $fileDuplicate = UploadedFile::fake()->image('photo_r2.jpg', 200, 200);
        $response2 = $this->postJson(route('projects.store'), [
            'name' => 'Project Upload R2 Salinan',
            'content_type' => 'post',
            'caption' => 'Salinan',
            'target_time' => '12:00',
            'repeat_type' => 'continuous',
            'start_date' => now()->format('Y-m-d'),
            'targets' => [
                ['account_id' => $acc->id, 'platform_target' => 'both'],
            ],
            'media_files' => [$fileDuplicate],
        ]);

        $response2->assertStatus(200);

        // Jumlah MediaFile harus tetap 1 (tidak diduplikasi di storage/DB)
        $this->assertEquals(1, MediaFile::count());
    }

    public function test_migrate_storage_to_r2_command(): void
    {
        Storage::fake('r2');
        Config::set('filesystems.disks.r2.bucket', 'test-bucket');
        Config::set('filesystems.disks.r2.endpoint', 'https://account.r2.cloudflarestorage.com');
        Config::set('filesystems.disks.r2.key', 'fake-key');
        Config::set('filesystems.disks.r2.secret', 'fake-secret');
        Config::set('filesystems.disks.r2.url', 'https://media.damaijaya.my.id');

        // Buat file lokal dummy di public/storage/uploads
        $testFileName = 'migrate_test_' . time() . '.jpg';
        $uploadDir = public_path('storage/uploads');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $localFilePath = $uploadDir . '/' . $testFileName;
        file_put_contents($localFilePath, 'dummy image content');

        $media = MediaFile::create([
            'original_name' => 'migrate_test.jpg',
            'file_path' => '/storage/uploads/' . $testFileName,
            'file_hash' => hash_file('sha256', $localFilePath),
            'mime_type' => 'image/jpeg',
            'file_size' => filesize($localFilePath),
            'media_type' => 'image',
        ]);

        $project = ProjectCampaign::create([
            'name' => 'Project For Migration',
            'content_type' => 'post',
            'target_time' => '09:00',
            'repeat_type' => 'continuous',
            'status' => 'active',
        ]);

        $schedule = Schedule::create([
            'project_campaign_id' => $project->id,
            'item_code' => 'mig_item_1',
            'media_file_id' => $media->id,
            'media_path' => $media->file_path,
            'media_paths' => [$media->file_path],
            'target_date' => now()->format('Y-m-d'),
            'target_time' => '09:00',
            'status' => 'pending',
        ]);

        // 1. Test Dry Run (tidak mengubah database)
        $this->artisan('storage:migrate-to-r2 --dry-run')
            ->assertExitCode(0);

        $media->refresh();
        $this->assertEquals('/storage/uploads/' . $testFileName, $media->file_path);

        // 2. Test Real Migration dengan --force
        $this->artisan('storage:migrate-to-r2 --force')
            ->assertExitCode(0);

        $media->refresh();
        $schedule->refresh();

        $this->assertEquals('uploads/' . $testFileName, $media->file_path);
        $this->assertEquals('uploads/' . $testFileName, $schedule->media_path);
        $this->assertEquals(['uploads/' . $testFileName], $schedule->media_paths);

        // Pastikan file berhasil diunggah ke fake r2
        Storage::disk('r2')->assertExists('uploads/' . $testFileName);

        // Bersihkan file lokal uji coba
        if (file_exists($localFilePath)) {
            @unlink($localFilePath);
        }
    }
}
