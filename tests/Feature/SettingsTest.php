<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->operator = User::factory()->create([
            'role' => 'operator',
        ]);
    }

    public function test_admin_can_access_settings_page(): void
    {
        MediaFile::create([
            'original_name' => 'test.jpg',
            'file_path' => 'uploads/test.jpg',
            'file_hash' => 'hash_settings_1',
            'mime_type' => 'image/jpeg',
            'file_size' => 5000,
            'media_type' => 'image',
        ]);

        $response = $this->actingAs($this->admin)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Sistem');
        $response->assertSee('Penyimpanan (Storage R2)');
        $response->assertSee('Integrasi Meta API');
        $response->assertSee('Cloudflare R2 Storage');
        $response->assertSee('Tes Koneksi R2');
    }

    public function test_admin_can_access_settings_meta_tab(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index', ['tab' => 'meta']));

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Sistem');
        $response->assertSee('Integrasi Meta API');
        $response->assertSee('Otorisasi Akun (Metode Utama)');
        $response->assertSee('Kredensial Meta App');
    }

    public function test_operator_cannot_access_settings_page(): void
    {
        $response = $this->actingAs($this->operator)->get(route('settings.index'));
        $response->assertStatus(403);
    }

    public function test_test_r2_returns_422_if_credentials_missing(): void
    {
        Config::set('filesystems.disks.r2.bucket', '');
        Config::set('filesystems.disks.r2.key', '');
        Config::set('filesystems.disks.r2.secret', '');
        Config::set('filesystems.disks.r2.endpoint', '');

        $response = $this->actingAs($this->admin)->postJson(route('settings.testR2'));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Konfigurasi Cloudflare R2 belum lengkap', $response->json('message'));
    }

    public function test_test_r2_succeeds_with_fake_storage(): void
    {
        Storage::fake('r2');
        Config::set('filesystems.disks.r2.bucket', 'my-test-bucket');
        Config::set('filesystems.disks.r2.key', 'fake-key');
        Config::set('filesystems.disks.r2.secret', 'fake-secret');
        Config::set('filesystems.disks.r2.endpoint', 'https://account.r2.cloudflarestorage.com');
        Config::set('filesystems.disks.r2.url', 'https://media.damaijaya.my.id');

        $response = $this->actingAs($this->admin)->postJson(route('settings.testR2'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'bucket' => 'my-test-bucket',
        ]);
        $this->assertGreaterThanOrEqual(1, $response->json('latency_ms'));
    }
}
