<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use App\Models\Schedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateStorageToR2Command extends Command
{
    protected $signature = 'storage:migrate-to-r2 
                            {--dry-run : Simulasi migrasi tanpa mengunggah file atau memperbarui database}
                            {--delete-local : Hapus file lokal setelah berhasil diunggah ke Cloudflare R2}
                            {--force : Jalankan langsung tanpa prompt konfirmasi}';

    protected $description = 'Migrasi seluruh file media lokal ke Cloudflare R2 object storage dan sinkronkan database';

    public function handle(): int
    {
        $this->newLine();
        $this->info('===============================================================');
        $this->info('   MIGRASI PENYIMPANAN KE CLOUDFLARE R2 OBJECT STORAGE         ');
        $this->info('===============================================================');
        $this->newLine();

        $isDryRun = (bool) $this->option('dry-run');
        $deleteLocal = (bool) $this->option('delete-local');
        $force = (bool) $this->option('force');

        if ($isDryRun) {
            $this->warn('>> MODE SIMULASI (DRY-RUN): Tidak ada file yang diunggah atau data yang diubah.');
            $this->newLine();
        }

        // 1. Validasi Konfigurasi R2
        $bucket = config('filesystems.disks.r2.bucket');
        $endpoint = config('filesystems.disks.r2.endpoint');
        $key = config('filesystems.disks.r2.key');
        $secret = config('filesystems.disks.r2.secret');

        if (empty($bucket) || empty($key) || empty($secret) || empty($endpoint)) {
            $this->error('ERROR: Konfigurasi Cloudflare R2 belum lengkap di .env!');
            $this->line('Pastikan parameter berikut sudah diatur di file .env server Anda:');
            $this->line('  - R2_ACCESS_KEY_ID');
            $this->line('  - R2_SECRET_ACCESS_KEY');
            $this->line('  - R2_BUCKET');
            $this->line('  - R2_ENDPOINT (contoh: https://<ACCOUNT_ID>.r2.cloudflarestorage.com)');
            $this->line('  - R2_URL (contoh: https://media.damaijaya.my.id atau https://pub-xxx.r2.dev)');
            return Command::FAILURE;
        }

        $this->info("Bucket R2 Target : {$bucket}");
        $this->info("Endpoint R2      : {$endpoint}");
        $this->info("Public URL Prefix: " . (config('filesystems.disks.r2.url') ?: '(Belum diatur)'));
        $this->newLine();

        // 2. Uji Coba Konektivitas ke R2 (jika bukan dry-run)
        if (!$isDryRun) {
            $this->line('Menguji konektivitas tulis & baca ke Cloudflare R2...');
            try {
                $testKey = '.test_r2_connectivity_' . time() . '.txt';
                Storage::disk('r2')->put($testKey, 'ok', ['visibility' => 'public']);
                Storage::disk('r2')->delete($testKey);
                $this->info('✓ Koneksi ke Cloudflare R2 berhasil dan siap digunakan!');
            } catch (\Exception $e) {
                $this->error('Gagal terhubung ke Cloudflare R2: ' . $e->getMessage());
                $this->line('Silakan periksa kembali R2_ENDPOINT, R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, dan R2_BUCKET.');
                return Command::FAILURE;
            }
            $this->newLine();
        }

        // 3. Kumpulkan Data MediaFile
        $mediaFiles = MediaFile::all();
        $totalCount = $mediaFiles->count();

        if ($totalCount === 0) {
            $this->info('Tidak ada data MediaFile yang ditemukan di database.');
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$totalCount} item media di database.");

        if (!$force && !$isDryRun) {
            if (!$this->confirm('Apakah Anda ingin melanjutkan proses migrasi ke Cloudflare R2 sekarang?', true)) {
                $this->warn('Migrasi dibatalkan oleh pengguna.');
                return Command::SUCCESS;
            }
        }

        $this->newLine();
        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $uploadedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $missingLocalCount = 0;

        foreach ($mediaFiles as $media) {
            $fileName = basename($media->file_path);
            $targetR2Path = 'uploads/' . $fileName;

            // Cari lokasi fisik file di disk lokal VPS
            $localPath = $this->findLocalFile($media->file_path, $fileName);

            // Cek apakah file sudah berada di R2
            $alreadyOnR2 = false;
            if (!$isDryRun) {
                try {
                    $alreadyOnR2 = Storage::disk('r2')->exists($targetR2Path);
                } catch (\Exception $e) {
                    $alreadyOnR2 = false;
                }
            }

            if ($alreadyOnR2 && !$localPath) {
                // File sudah ada di R2 dan lokal sudah tidak ada
                $this->updateDatabaseRecords($media, $targetR2Path, $isDryRun);
                $skippedCount++;
                $bar->advance();
                continue;
            }

            if (!$localPath) {
                $missingLocalCount++;
                $bar->advance();
                continue;
            }

            if ($isDryRun) {
                $uploadedCount++;
                $bar->advance();
                continue;
            }

            // Eksekusi Unggah ke R2
            try {
                $stream = fopen($localPath, 'r');
                if (!$stream) {
                    throw new \Exception("Gagal membuka stream file lokal: {$localPath}");
                }

                $mimeType = $media->mime_type ?: mime_content_type($localPath);
                $uploaded = Storage::disk('r2')->put($targetR2Path, $stream, [
                    'visibility' => 'public',
                    'mimetype' => $mimeType,
                ]);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                if (!$uploaded) {
                    throw new \Exception("Storage::put mengembalikan false saat mengunggah ke R2.");
                }

                // Perbarui Database
                $this->updateDatabaseRecords($media, $targetR2Path, false);

                // Hapus lokal jika diminta
                if ($deleteLocal && file_exists($localPath)) {
                    @unlink($localPath);
                }

                $uploadedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("\nGagal mengunggah file #{$media->id} ({$fileName}): " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Ringkasan Hasil
        $this->table(
            ['Kategori', 'Jumlah'],
            [
                ['Total Media Terdaftar', $totalCount],
                ['Berhasil Dimigrasi / Siap Diunggah', $uploadedCount],
                ['Sudah Berada di R2 (Dilewati)', $skippedCount],
                ['File Lokal Fisik Hilang', $missingLocalCount],
                ['Gagal Diunggah', $failedCount],
            ]
        );

        $this->newLine();
        if ($isDryRun) {
            $this->info('>> Simulasi selesai. Untuk mengeksekusi migrasi nyata, jalankan:');
            $this->line('   php artisan storage:migrate-to-r2');
        } else {
            $this->info('✓ Proses migrasi selesai!');
            $this->warn('PENTING: Pastikan Anda telah mengatur MEDIA_DISK=r2 di file .env agar upload baru otomatis masuk ke R2.');
        }

        return $failedCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Cari file lokal di berbagai direktori standar storage Laravel.
     */
    protected function findLocalFile(string $filePath, string $fileName): ?string
    {
        $candidates = [
            public_path('storage/uploads/' . $fileName),
            storage_path('app/public/uploads/' . $fileName),
            public_path(ltrim($filePath, '/')),
            storage_path('app/public/' . ltrim(str_replace('/storage/', '', $filePath), '/')),
            public_path('uploads/' . $fileName),
        ];

        foreach ($candidates as $cand) {
            if (file_exists($cand) && is_file($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Perbarui path pada tabel media_files dan tabel schedules terkait.
     */
    protected function updateDatabaseRecords(MediaFile $media, string $newPath, bool $isDryRun): void
    {
        if ($isDryRun) return;

        $oldPath = $media->file_path;
        $fileName = basename($oldPath);

        // Update MediaFile
        $media->file_path = $newPath;
        $media->save();

        // Update Schedules yang merujuk pada media ini
        $schedules = Schedule::where('media_file_id', $media->id)
            ->orWhere('media_path', $oldPath)
            ->orWhere('media_path', 'LIKE', '%' . $fileName)
            ->get();

        foreach ($schedules as $schedule) {
            $schedule->media_path = $newPath;

            // Perbarui array media_paths jika ada
            if (!empty($schedule->media_paths) && is_array($schedule->media_paths)) {
                $updatedPaths = [];
                foreach ($schedule->media_paths as $p) {
                    if (basename($p) === $fileName || $p === $oldPath) {
                        $updatedPaths[] = $newPath;
                    } else {
                        $updatedPaths[] = $p;
                    }
                }
                $schedule->media_paths = $updatedPaths;
            }

            $schedule->save();
        }
    }
}
