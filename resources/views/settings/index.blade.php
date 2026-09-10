@extends('layouts.app')

@section('title', 'Pengaturan Sistem & Storage R2')

@section('content')
<div class="w-full space-y-6 max-w-7xl mx-auto">

    <!-- Header & Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80 dark:border-gray-800/80">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-gray-400 mb-1">
                <a href="{{ route('projects.index') }}" class="hover:text-slate-900 dark:hover:text-white transition flex items-center space-x-1">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white font-medium">Pengaturan</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <span>Pengaturan Sistem & Storage</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">
                Konfigurasi Cloudflare R2 Object Storage, pengujian konektivitas live, statistik kapasitas media, dan ringkasan server.
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('users.index') }}" 
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg border border-slate-200 dark:border-gray-800 bg-white dark:bg-slate-800/80 text-xs font-semibold text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-sm">
                <i class="fa-solid fa-users text-[11px] text-purple-500"></i>
                <span>Manajemen User</span>
            </a>
            <a href="{{ route('meta.index') }}" 
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg border border-slate-200 dark:border-gray-800 bg-white dark:bg-slate-800/80 text-xs font-semibold text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-sm">
                <i class="fa-brands fa-meta text-[11px] text-indigo-500"></i>
                <span>Integrasi Meta API</span>
            </a>
        </div>
    </div>

    <!-- ==================== SECTION 1: STATISTIK MEDIA STORAGE ==================== -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 flex items-center space-x-2">
                <i class="fa-solid fa-chart-pie text-indigo-500"></i>
                <span>Statistik Penyimpanan Media Pool</span>
            </h2>
            <span class="text-[11px] text-slate-500 dark:text-gray-400">
                Driver Aktif: 
                <strong class="font-bold {{ $r2Config['media_disk'] === 'r2' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                    {{ strtoupper($r2Config['media_disk']) }}
                </strong>
            </span>
        </div>

        <!-- Metric Cards Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total Media -->
            <div class="card-dark rounded-xl p-4 sm:p-5 border border-slate-200 dark:border-gray-800 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-photo-film"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block truncate">Total Media Pool</span>
                    <span class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white block mt-0.5">{{ number_format($totalFiles) }}</span>
                    <span class="text-[10px] text-slate-400 dark:text-gray-500">Item tersimpan</span>
                </div>
            </div>

            <!-- Card 2: Total Kapasitas -->
            <div class="card-dark rounded-xl p-4 sm:p-5 border border-slate-200 dark:border-gray-800 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-hard-drive"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block truncate">Total Ukuran File</span>
                    <span class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white block mt-0.5">{{ $totalSizeFormatted }}</span>
                    <span class="text-[10px] text-slate-400 dark:text-gray-500">{{ number_format($totalSizeBytes) }} bytes</span>
                </div>
            </div>

            <!-- Card 3: Foto & Gambar -->
            <div class="card-dark rounded-xl p-4 sm:p-5 border border-slate-200 dark:border-gray-800 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-image"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block truncate">Foto & Gambar</span>
                    <span class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white block mt-0.5">{{ number_format($imageFiles) }}</span>
                    <span class="text-[10px] text-slate-400 dark:text-gray-500">JPG, PNG, JPEG</span>
                </div>
            </div>

            <!-- Card 4: Video Konten -->
            <div class="card-dark rounded-xl p-4 sm:p-5 border border-slate-200 dark:border-gray-800 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-pink-500/10 text-pink-600 dark:text-pink-400 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-video"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block truncate">Video (Reels/Story)</span>
                    <span class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white block mt-0.5">{{ number_format($videoFiles) }}</span>
                    <span class="text-[10px] text-slate-400 dark:text-gray-500">MP4, MOV</span>
                </div>
            </div>
        </div>

        <!-- Distribusi Storage (R2 vs Local) -->
        <div class="card-dark rounded-xl p-5 border border-slate-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-network-wired text-indigo-500 text-sm"></i>
                    <span class="text-xs font-bold text-slate-900 dark:text-white">Distribusi Lokasi File Media</span>
                </div>
                <div class="flex items-center space-x-4 text-xs">
                    <div class="flex items-center space-x-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 inline-block"></span>
                        <span class="text-slate-600 dark:text-gray-300 font-semibold">Cloudflare R2: {{ $r2Count }} file ({{ $r2Percentage }}%)</span>
                    </div>
                    <div class="flex items-center space-x-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>
                        <span class="text-slate-600 dark:text-gray-300 font-semibold">Lokal VPS: {{ $localCount }} file ({{ $localPercentage }}%)</span>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="w-full bg-slate-100 dark:bg-gray-800 rounded-full h-3 overflow-hidden flex">
                <div class="bg-indigo-600 h-full transition-all duration-500" style="width: {{ $r2Percentage }}%" title="Cloudflare R2: {{ $r2Percentage }}%"></div>
                <div class="bg-amber-400 h-full transition-all duration-500" style="width: {{ $localPercentage }}%" title="Lokal VPS: {{ $localPercentage }}%"></div>
            </div>

            @if($localCount > 0)
                <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-xs text-amber-900 dark:text-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <div class="flex items-center space-x-2.5">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500 text-sm shrink-0"></i>
                        <span>Terdapat <strong>{{ $localCount }} file</strong> yang masih tersimpan di VPS lokal. Anda dapat memindahkannya ke Cloudflare R2.</span>
                    </div>
                    <div class="font-mono text-[11px] bg-white dark:bg-gray-900 px-2.5 py-1 rounded border border-amber-300 dark:border-amber-700/80 text-amber-800 dark:text-amber-300 select-all shrink-0">
                        php artisan storage:migrate-to-r2
                    </div>
                </div>
            @else
                <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 text-xs text-emerald-900 dark:text-emerald-200 flex items-center space-x-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-sm shrink-0"></i>
                    <span>Seluruh file media telah tersimpan aman di Cloudflare R2 Object Storage.</span>
                </div>
            @endif
        </div>
    </div>

    <!-- ==================== SECTION 2: CLOUDFLARE R2 & LIVE CONNECTION TEST ==================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- Kolom Kiri: Konfigurasi R2 & Tes Koneksi (lg:col-span-7) -->
        <div class="lg:col-span-7 space-y-5">
            <div class="card-dark rounded-xl p-5 sm:p-6 border border-slate-200 dark:border-gray-800 shadow-sm space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-lg bg-orange-500/10 text-orange-600 dark:text-orange-400 flex items-center justify-center text-sm shrink-0">
                            <i class="fa-solid fa-cloud"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Cloudflare R2 Storage</h2>
                            <span class="text-[10px] text-slate-400 dark:text-gray-500">S3-Compatible High-Performance Object Storage</span>
                        </div>
                    </div>
                    <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-lg text-xs font-bold {{ $r2Config['media_disk'] === 'r2' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-300' }}">
                        <span class="w-2 h-2 rounded-full {{ $r2Config['media_disk'] === 'r2' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        <span>{{ $r2Config['media_disk'] === 'r2' ? 'R2 Aktif' : 'Local VPS Aktif' }}</span>
                    </span>
                </div>

                <!-- Config Parameters Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-gray-900/60 border border-slate-200/80 dark:border-gray-800">
                        <span class="text-[10px] font-semibold text-slate-400 dark:text-gray-500 uppercase tracking-wider block">Bucket Name</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-gray-200 block truncate mt-0.5">{{ $r2Config['bucket'] }}</span>
                    </div>

                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-gray-900/60 border border-slate-200/80 dark:border-gray-800">
                        <span class="text-[10px] font-semibold text-slate-400 dark:text-gray-500 uppercase tracking-wider block">Region</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-gray-200 block truncate mt-0.5">{{ $r2Config['region'] }}</span>
                    </div>

                    <div class="sm:col-span-2 p-3 rounded-lg bg-slate-50 dark:bg-gray-900/60 border border-slate-200/80 dark:border-gray-800">
                        <span class="text-[10px] font-semibold text-slate-400 dark:text-gray-500 uppercase tracking-wider block">Public Domain / URL Prefix (R2_URL)</span>
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 block truncate mt-0.5">{{ $r2Config['url'] }}</span>
                        <span class="text-[10px] text-slate-400 dark:text-gray-500 mt-0.5 block">Digunakan oleh Meta Graph API untuk mengunduh gambar/video.</span>
                    </div>

                    <div class="sm:col-span-2 p-3 rounded-lg bg-slate-50 dark:bg-gray-900/60 border border-slate-200/80 dark:border-gray-800">
                        <span class="text-[10px] font-semibold text-slate-400 dark:text-gray-500 uppercase tracking-wider block">S3 API Endpoint</span>
                        <span class="font-mono text-slate-700 dark:text-gray-300 block truncate mt-0.5 text-[11px]">{{ $r2Config['endpoint'] }}</span>
                    </div>
                </div>

                <!-- Test Connection Interactive Section -->
                <div class="pt-2 border-t border-slate-100 dark:border-gray-800 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">Uji Konektivitas Cloudflare R2</span>
                            <span class="text-[11px] text-slate-500 dark:text-gray-400">Menulis, membaca, dan menghapus file uji coba secara real-time.</span>
                        </div>
                        <button type="button" 
                                onclick="runR2ConnectionTest()" 
                                id="btnTestR2"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition shadow-sm flex items-center space-x-2 shrink-0">
                            <i class="fa-solid fa-plug-circle-check" id="btnTestR2Icon"></i>
                            <span id="btnTestR2Text">Tes Koneksi R2</span>
                        </button>
                    </div>

                    <!-- Test Result Panel (Hidden by default, shown on result) -->
                    <div id="r2TestResult" class="hidden rounded-xl p-4 border transition-all duration-300 text-xs"></div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Status Server & Meta Graph API (lg:col-span-5) -->
        <div class="lg:col-span-5 space-y-5">
            <div class="card-dark rounded-xl p-5 sm:p-6 border border-slate-200 dark:border-gray-800 shadow-sm space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 flex items-center space-x-2 border-b border-slate-100 dark:border-gray-800 pb-3">
                    <i class="fa-solid fa-server text-indigo-500"></i>
                    <span>Informasi Lingkungan Sistem</span>
                </h2>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-gray-800/60">
                        <span class="text-slate-500 dark:text-gray-400">Meta Graph API</span>
                        <span class="font-bold text-slate-900 dark:text-white flex items-center space-x-1.5">
                            <i class="fa-brands fa-meta text-indigo-500"></i>
                            <span>{{ $systemInfo['meta_graph_version'] }}</span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-gray-800/60">
                        <span class="text-slate-500 dark:text-gray-400">Laravel Framework</span>
                        <span class="font-mono font-semibold text-slate-900 dark:text-white">{{ $systemInfo['laravel_version'] }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-gray-800/60">
                        <span class="text-slate-500 dark:text-gray-400">PHP Version</span>
                        <span class="font-mono font-semibold text-slate-900 dark:text-white">{{ $systemInfo['php_version'] }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-gray-800/60">
                        <span class="text-slate-500 dark:text-gray-400">Zona Waktu (Timezone)</span>
                        <span class="font-semibold text-slate-900 dark:text-white">{{ $systemInfo['timezone'] }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-gray-800/60">
                        <span class="text-slate-500 dark:text-gray-400">Environment</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase {{ $systemInfo['app_env'] === 'production' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                            {{ $systemInfo['app_env'] }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-slate-500 dark:text-gray-400">Application URL</span>
                        <a href="{{ $systemInfo['app_url'] }}" target="_blank" class="font-mono text-indigo-600 dark:text-indigo-400 hover:underline truncate max-w-[180px]">
                            {{ $systemInfo['app_url'] }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Petunjuk Pengaturan .env VPS -->
            <div class="card-dark rounded-xl p-5 border border-slate-200 dark:border-gray-800 shadow-sm space-y-2.5">
                <div class="flex items-center space-x-2 text-xs font-bold text-slate-900 dark:text-white">
                    <i class="fa-solid fa-terminal text-indigo-500"></i>
                    <span>Panduan Terminal Server</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-gray-400 leading-relaxed">
                    Untuk mengubah pengaturan R2 atau storage, edit file <code>.env</code> di root project VPS lalu bersihkan cache:
                </p>
                <div class="p-2.5 rounded bg-slate-900 text-slate-200 font-mono text-[10px] space-y-1">
                    <p class="text-slate-400"># Bersihkan cache setelah edit .env</p>
                    <p class="text-emerald-400">php artisan config:clear</p>
                    <p class="text-emerald-400">php artisan cache:clear</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    function runR2ConnectionTest() {
        const btn = document.getElementById('btnTestR2');
        const btnIcon = document.getElementById('btnTestR2Icon');
        const btnText = document.getElementById('btnTestR2Text');
        const resultPanel = document.getElementById('r2TestResult');

        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
        btnIcon.className = 'fa-solid fa-circle-notch fa-spin';
        btnText.textContent = 'Menguji Koneksi...';

        resultPanel.classList.add('hidden');
        resultPanel.className = 'rounded-xl p-4 border transition-all duration-300 text-xs';

        fetch("{{ route('settings.testR2') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(({ status, body }) => {
            resultPanel.classList.remove('hidden');

            if (status === 200 && body.success) {
                resultPanel.classList.add('bg-emerald-50', 'dark:bg-emerald-950/40', 'border-emerald-200', 'dark:border-emerald-800', 'text-emerald-900', 'dark:text-emerald-200');
                resultPanel.innerHTML = `
                    <div class="flex items-start space-x-3">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-xs"></i>
                        </div>
                        <div class="space-y-1 min-w-0 flex-grow">
                            <div class="flex items-center justify-between gap-2">
                                <strong class="font-bold text-emerald-800 dark:text-emerald-300 text-xs">Koneksi Cloudflare R2 Berhasil!</strong>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900 text-[10px] font-mono font-bold text-emerald-700 dark:text-emerald-300">
                                    ⚡ ${body.latency_ms} ms
                                </span>
                            </div>
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-300/90">${body.message}</p>
                            <div class="pt-2 mt-2 border-t border-emerald-200 dark:border-emerald-800/80 text-[10px] font-mono grid grid-cols-1 sm:grid-cols-2 gap-1 text-emerald-800 dark:text-emerald-300">
                                <div><strong>Bucket:</strong> ${body.bucket}</div>
                                <div><strong>Waktu:</strong> ${body.timestamp}</div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                resultPanel.classList.add('bg-rose-50', 'dark:bg-rose-950/40', 'border-rose-200', 'dark:border-rose-800', 'text-rose-900', 'dark:text-rose-200');
                resultPanel.innerHTML = `
                    <div class="flex items-start space-x-3">
                        <div class="w-7 h-7 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </div>
                        <div class="space-y-1 min-w-0 flex-grow">
                            <strong class="font-bold text-rose-800 dark:text-rose-300 text-xs">Gagal Terhubung ke R2</strong>
                            <p class="text-[11px] text-rose-700 dark:text-rose-300/90">${body.message || 'Terjadi kesalahan saat menguji koneksi.'}</p>
                            ${body.error_detail ? `<div class="mt-2 p-2 rounded bg-rose-100 dark:bg-rose-900/50 font-mono text-[10px] text-rose-800 dark:text-rose-200 overflow-x-auto">${body.error_detail}</div>` : ''}
                        </div>
                    </div>
                `;
            }
        })
        .catch(err => {
            resultPanel.classList.remove('hidden');
            resultPanel.classList.add('bg-rose-50', 'dark:bg-rose-950/40', 'border-rose-200', 'dark:border-rose-800', 'text-rose-900', 'dark:text-rose-200');
            resultPanel.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="w-7 h-7 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </div>
                    <div class="space-y-1">
                        <strong class="font-bold text-rose-800 dark:text-rose-300 text-xs">Network / Server Error</strong>
                        <p class="text-[11px] text-rose-700 dark:text-rose-300/90">${err.message}</p>
                    </div>
                </div>
            `;
        })
        .finally(() => {
            btn.disabled = false;
            btn.classList.remove('opacity-75', 'cursor-not-allowed');
            btnIcon.className = 'fa-solid fa-plug-circle-check';
            btnText.textContent = 'Tes Koneksi R2';
        });
    }
</script>
@endsection
