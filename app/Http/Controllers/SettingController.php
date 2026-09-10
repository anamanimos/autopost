<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Hanya Administrator yang dapat mengakses menu pengaturan.');
        }

        // Statistik Media Storage
        $totalFiles = MediaFile::count();
        $totalSizeBytes = (int) MediaFile::sum('file_size');
        $totalSizeFormatted = $this->formatBytes($totalSizeBytes);

        $imageFiles = MediaFile::where('media_type', 'image')->orWhere('mime_type', 'LIKE', 'image/%')->count();
        $videoFiles = MediaFile::where('media_type', 'video')->orWhere('mime_type', 'LIKE', 'video/%')->count();

        // Hitung distribusi R2 vs Local
        $r2Count = MediaFile::where(function ($q) {
            $q->where('file_path', 'LIKE', 'uploads/%')
              ->orWhere('file_path', 'LIKE', 'http%');
        })->count();

        $localCount = MediaFile::where('file_path', 'LIKE', '/storage/uploads/%')
            ->orWhere('file_path', 'LIKE', 'public/%')
            ->count();

        $r2Percentage = $totalFiles > 0 ? round(($r2Count / $totalFiles) * 100) : 0;
        $localPercentage = $totalFiles > 0 ? (100 - $r2Percentage) : 0;

        // Konfigurasi R2
        $r2Config = [
            'media_disk' => config('filesystems.media_disk', env('MEDIA_DISK', 'local')),
            'bucket' => config('filesystems.disks.r2.bucket') ?: '(Belum diatur)',
            'endpoint' => config('filesystems.disks.r2.endpoint') ?: '(Belum diatur)',
            'url' => config('filesystems.disks.r2.url') ?: '(Belum diatur)',
            'region' => config('filesystems.disks.r2.region', 'auto'),
            'is_configured' => !empty(config('filesystems.disks.r2.bucket')) && 
                               !empty(config('filesystems.disks.r2.key')) && 
                               !empty(config('filesystems.disks.r2.secret')) && 
                               !empty(config('filesystems.disks.r2.endpoint')),
        ];

        // Informasi Sistem
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'app_url' => config('app.url'),
            'app_env' => config('app.env'),
            'timezone' => config('app.timezone', 'Asia/Jakarta'),
            'meta_graph_version' => config('services.meta.graph_version', 'v22.0'),
        ];

        return view('settings.index', compact(
            'totalFiles',
            'totalSizeBytes',
            'totalSizeFormatted',
            'imageFiles',
            'videoFiles',
            'r2Count',
            'localCount',
            'r2Percentage',
            'localPercentage',
            'r2Config',
            'systemInfo'
        ));
    }

    public function testR2(Request $request): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $bucket = config('filesystems.disks.r2.bucket');
        $endpoint = config('filesystems.disks.r2.endpoint');
        $key = config('filesystems.disks.r2.key');
        $secret = config('filesystems.disks.r2.secret');

        if (empty($bucket) || empty($key) || empty($secret) || empty($endpoint)) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi Cloudflare R2 belum lengkap di file .env. Pastikan R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, R2_BUCKET, dan R2_ENDPOINT sudah terisi.',
            ], 422);
        }

        $startTime = microtime(true);
        $testKey = '.r2_health_test_' . time() . '.txt';
        $testContent = 'r2_ping_' . date('Y-m-d H:i:s') . '_v22';

        try {
            // 1. Tes Tulis (Write)
            $writeOk = Storage::disk('r2')->put($testKey, $testContent, ['visibility' => 'public']);
            if (!$writeOk) {
                throw new \Exception('Gagal menulis file uji coba ke bucket Cloudflare R2.');
            }

            // 2. Tes Baca (Read)
            $readContent = Storage::disk('r2')->get($testKey);
            if ($readContent !== $testContent) {
                throw new \Exception('Integritas data tidak cocok saat membaca kembali file dari R2.');
            }

            // 3. Tes Hapus (Delete)
            Storage::disk('r2')->delete($testKey);

            $endTime = microtime(true);
            $latencyMs = max(1, round(($endTime - $startTime) * 1000));

            return response()->json([
                'success' => true,
                'message' => 'Koneksi ke Cloudflare R2 berhasil! Operasi tulis, baca, dan hapus berjalan lancar.',
                'latency_ms' => $latencyMs,
                'bucket' => $bucket,
                'endpoint' => $endpoint,
                'public_url' => config('filesystems.disks.r2.url'),
                'timestamp' => Carbon::now()->translatedFormat('d F Y, H:i:s') . ' WIB',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung ke Cloudflare R2: ' . $e->getMessage(),
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
