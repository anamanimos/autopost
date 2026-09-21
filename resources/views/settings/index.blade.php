@extends('layouts.app')

@section('title', 'Pengaturan Sistem (Storage & Meta Integration)')

@section('content')
@php
    $hasToken = !empty($credential->user_access_token) || !empty($credential->system_user_token);
    $isMetaConnected = $credential->token_status === 'valid' && $hasToken;
    $initialTab = request('tab', 'storage');
    if (!in_array($initialTab, ['storage', 'meta'])) {
        $initialTab = 'storage';
    }
@endphp

<div class="w-full space-y-6 max-w-7xl mx-auto" 
     x-data="{ 
         activeTab: '{{ $initialTab }}',
         showGuide: {{ $isMetaConnected ? 'false' : 'true' }},
         metaSubTab: '{{ $isMetaConnected ? 'accounts' : 'connect' }}'
     }">

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
                <span>Pengaturan Sistem</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">
                Kelola konfigurasi Cloudflare R2 Object Storage, Integrasi Meta Graph API (Instagram & Facebook), dan parameter server.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('users.index') }}" 
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg border border-slate-200 dark:border-gray-800 bg-white dark:bg-slate-800/80 text-xs font-semibold text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-sm">
                <i class="fa-solid fa-users text-[11px] text-purple-500"></i>
                <span>Manajemen User</span>
            </a>
        </div>
    </div>

    <!-- Tab Navigasi Utama: Storage vs Meta Integration -->
    <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-gray-800 pb-0.5 text-xs sm:text-sm font-semibold overflow-x-auto whitespace-nowrap scrollbar-none">
        <!-- Tab 1: Storage R2 -->
        <button type="button" 
                @click="activeTab = 'storage'; updateUrlTab('storage')"
                :class="activeTab === 'storage' 
                    ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-500 bg-indigo-50/60 dark:bg-indigo-950/20' 
                    : 'text-slate-500 dark:text-gray-400 hover:text-slate-800 dark:hover:text-gray-200 hover:bg-slate-100/60 dark:hover:bg-gray-800/40'"
                class="px-4 py-3 rounded-t-xl transition flex items-center space-x-2.5">
            <i class="fa-solid fa-hard-drive"></i>
            <span>Penyimpanan (Storage R2)</span>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $r2Config['media_disk'] === 'r2' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300' }}">
                {{ strtoupper($r2Config['media_disk']) }}
            </span>
        </button>

        <!-- Tab 2: Meta Integration -->
        <button type="button" 
                @click="activeTab = 'meta'; updateUrlTab('meta')"
                :class="activeTab === 'meta' 
                    ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-500 bg-indigo-50/60 dark:bg-indigo-950/20' 
                    : 'text-slate-500 dark:text-gray-400 hover:text-slate-800 dark:hover:text-gray-200 hover:bg-slate-100/60 dark:hover:bg-gray-800/40'"
                class="px-4 py-3 rounded-t-xl transition flex items-center space-x-2.5">
            <i class="fa-brands fa-meta text-base text-indigo-500"></i>
            <span>Integrasi Meta API</span>
            @if($isMetaConnected)
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300">
                    {{ $accounts->count() }} Akun Aktif
                </span>
            @else
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300">
                    Setup Diperlukan
                </span>
            @endif
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: STORAGE (CLOUDFLARE R2 & MEDIA METRICS)                            -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'storage'" x-transition class="space-y-6">
        
        <!-- SECTION 1: STATISTIK MEDIA STORAGE -->
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

        <!-- SECTION 2: CLOUDFLARE R2 & LIVE CONNECTION TEST -->
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
                            <span class="text-[10px] text-slate-400 dark:text-gray-500 mt-0.5 block">Digunakan oleh Meta Graph API untuk mengunduh file gambar dan video saat jadwal posting diproses.</span>
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

    <!-- ========================================================================= -->
    <!-- TAB 2: META INTEGRATION (FACEBOOK & INSTAGRAM GRAPH API)                   -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'meta'" x-transition class="space-y-6">

        <!-- Header Ringkas Status Meta API -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <i class="fa-brands fa-meta text-indigo-600 dark:text-indigo-400"></i>
                    <span>Integrasi Meta API Resmi</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-gray-400 mt-0.5">
                    Koneksi resmi ke Facebook Page & Instagram Business untuk otomasi posting jadwal tanpa browser emulator.
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <button @click="showGuide = !showGuide" 
                        class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-indigo-700 border border-slate-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-indigo-300 dark:border-gray-700 transition flex items-center space-x-1.5 shadow-sm">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span x-text="showGuide ? 'Tutup Panduan' : 'Buka Panduan Pemula'">Buka Panduan Pemula</span>
                </button>
                <button onclick="testConnection()" 
                        class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-amber-700 border border-slate-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-amber-400 dark:border-gray-700 transition flex items-center space-x-1.5 shadow-sm">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Uji Koneksi Meta</span>
                </button>
            </div>
        </div>

        <!-- Status Banner Utama -->
        @if($isMetaConnected)
            <div class="bg-emerald-50/90 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800/80 rounded-xl p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start space-x-3.5">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 border border-emerald-300 text-emerald-700 dark:bg-emerald-500/20 dark:border-emerald-500/30 dark:text-emerald-400 flex items-center justify-center text-lg flex-shrink-0 mt-0.5">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-sm font-bold text-emerald-950 dark:text-white">Meta API Terhubung & Siap Digunakan</h3>
                            <span class="px-2 py-0.5 text-[9px] font-extrabold uppercase rounded-md bg-emerald-200/90 text-emerald-900 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">Aktif</span>
                        </div>
                        <p class="text-xs text-emerald-900 dark:text-gray-300 mt-0.5">
                            {{ $accounts->count() }} Halaman Facebook dan {{ $accounts->whereNotNull('ig_user_id')->count() }} Akun Instagram terdaftar dalam sistem.
                        </p>
                        <p class="text-[11px] text-emerald-800 dark:text-gray-400 mt-1">
                            Masa aktif token: 
                            <strong class="text-emerald-950 dark:text-emerald-300 font-bold">
                                {{ $credential->token_expires_at ? $credential->token_expires_at->format('d M Y') . ' (' . $credential->token_expires_at->diffForHumans() . ')' : 'Permanen (System User)' }}
                            </strong>
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2.5 sm:self-center">
                    <button onclick="triggerSyncNow()" 
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-lg shadow transition flex items-center space-x-1.5">
                        <i class="fa-solid fa-rotate"></i>
                        <span>Tarik / Sync Akun Terbaru</span>
                    </button>
                </div>
            </div>
        @else
            <div class="bg-amber-50/90 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800/80 rounded-xl p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start space-x-3.5">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 border border-amber-300 text-amber-700 dark:bg-amber-500/20 dark:border-amber-500/30 dark:text-amber-400 flex items-center justify-center text-lg flex-shrink-0 mt-0.5">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-sm font-bold text-amber-950 dark:text-white">Meta API Belum Terkoneksi</h3>
                            <span class="px-2 py-0.5 text-[9px] font-extrabold uppercase rounded-md bg-amber-200/90 text-amber-900 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300 dark:border-amber-700">Perlu Setup</span>
                        </div>
                        <p class="text-xs text-amber-900 dark:text-gray-300 mt-0.5">
                            Anda belum menghubungkan akun Facebook/Instagram. Ikuti panduan 4 langkah di bawah ini untuk mulai.
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2 sm:self-center">
                    <button @click="showGuide = true; metaSubTab = 'connect'" 
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-lg shadow transition flex items-center space-x-1.5">
                        <i class="fa-solid fa-arrow-down"></i>
                        <span>Lihat Panduan Setup</span>
                    </button>
                </div>
            </div>
        @endif

        <!-- Panduan Step-by-Step Pemula (Collapsible) -->
        <div x-show="showGuide" x-transition class="card-dark rounded-xl p-6 border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50/50 dark:bg-indigo-950/20 space-y-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-gray-800 pb-3">
                <div class="flex items-center space-x-2 text-indigo-700 dark:text-indigo-400 font-bold text-sm">
                    <i class="fa-solid fa-list-ol text-base"></i>
                    <span>Panduan 4 Langkah Menghubungkan Meta API (Untuk Pemula)</span>
                </div>
                <button @click="showGuide = false" class="text-slate-400 hover:text-slate-700 dark:text-gray-400 dark:hover:text-white text-xs">
                    <i class="fa-solid fa-xmark mr-1"></i>Tutup
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                <!-- Step 1 -->
                <div class="bg-white dark:bg-gray-900/80 p-4 rounded-lg border border-slate-200 dark:border-gray-800 space-y-2 relative shadow-sm">
                    <div class="w-6 h-6 rounded-md bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">1</div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-xs">Buat Aplikasi Meta</h4>
                    <p class="text-slate-600 dark:text-gray-400 text-[11px] leading-relaxed">
                        Buka <a href="https://developers.facebook.com/apps/" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline font-semibold">developers.facebook.com</a>, buat app baru dengan tipe <strong>Bisnis</strong>.
                    </p>
                    <span class="text-[10px] text-slate-400 dark:text-gray-500 block pt-1">Tambahkan produk: <strong>Facebook Login for Business</strong> & <strong>Instagram Graph API</strong>.</span>
                </div>

                <!-- Step 2 -->
                <div class="bg-white dark:bg-gray-900/80 p-4 rounded-lg border border-slate-200 dark:border-gray-800 space-y-2 relative shadow-sm">
                    <div class="w-6 h-6 rounded-md bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">2</div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-xs">Salin Kredensial & URI</h4>
                    <p class="text-slate-600 dark:text-gray-400 text-[11px] leading-relaxed">
                        Salin <strong>App ID</strong> & <strong>App Secret</strong> dari dashboard Meta Anda ke form di tab <em>Koneksi & Kredensial</em>.
                    </p>
                    <span class="text-[10px] text-slate-400 dark:text-gray-500 block pt-1">Salin pula <strong>Redirect URI</strong> dari aplikasi ini ke pengaturan Meta App Anda.</span>
                </div>

                <!-- Step 3 -->
                <div class="bg-white dark:bg-gray-900/80 p-4 rounded-lg border border-slate-200 dark:border-gray-800 space-y-2 relative shadow-sm">
                    <div class="w-6 h-6 rounded-md bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">3</div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-xs">Login & Beri Izin</h4>
                    <p class="text-slate-600 dark:text-gray-400 text-[11px] leading-relaxed">
                        Klik tombol <strong>"Hubungkan dengan Facebook"</strong>. Beri centang seluruh Page & Akun Instagram yang ingin dikelola.
                    </p>
                    <span class="text-[10px] text-slate-400 dark:text-gray-500 block pt-1">Token jangka panjang (~60 hari) akan otomatis disimpan terenkripsi.</span>
                </div>

                <!-- Step 4 -->
                <div class="bg-white dark:bg-gray-900/80 p-4 rounded-lg border border-slate-200 dark:border-gray-800 space-y-2 relative shadow-sm">
                    <div class="w-6 h-6 rounded-md bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">4</div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-xs">Siap Buat Jadwal!</h4>
                    <p class="text-slate-600 dark:text-gray-400 text-[11px] leading-relaxed">
                        Seluruh akun Anda akan muncul di tab <em>Halaman & Akun</em>. Anda langsung bisa membuat jadwal campaign.
                    </p>
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 block pt-1">Otomasi publish akan berjalan mulus tanpa browser!</span>
                </div>
            </div>
        </div>

        <!-- Sub-Navigasi Tab Internal Meta Integration -->
        <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-gray-800 text-xs font-semibold overflow-x-auto whitespace-nowrap scrollbar-none pb-0.5">
            <button type="button" @click="metaSubTab = 'connect'"
                    :class="metaSubTab === 'connect' ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-500 bg-slate-100/70 dark:bg-gray-900/50' : 'text-slate-500 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/50 dark:hover:bg-gray-900/30'"
                    class="px-4 py-2.5 rounded-t-lg transition flex items-center space-x-2">
                <i class="fa-solid fa-key text-xs"></i>
                <span>1. Koneksi & Kredensial App</span>
            </button>

            <button type="button" @click="metaSubTab = 'accounts'"
                    :class="metaSubTab === 'accounts' ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-500 bg-slate-100/70 dark:bg-gray-900/50' : 'text-slate-500 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/50 dark:hover:bg-gray-900/30'"
                    class="px-4 py-2.5 rounded-t-lg transition flex items-center space-x-2">
                <i class="fa-solid fa-users text-xs"></i>
                <span>2. Halaman & Akun Instagram Terhubung</span>
                <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold">{{ $accounts->count() }}</span>
            </button>

            <button type="button" @click="metaSubTab = 'advanced'"
                    :class="metaSubTab === 'advanced' ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-500 bg-slate-100/70 dark:bg-gray-900/50' : 'text-slate-500 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/50 dark:hover:bg-gray-900/30'"
                    class="px-4 py-2.5 rounded-t-lg transition flex items-center space-x-2">
                <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                <span>3. Riwayat & Pengaturan Lanjutan</span>
            </button>
        </div>

        <!-- SUBTAB 1: KONEKSI & KREDENSIAL -->
        <div x-show="metaSubTab === 'connect'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Kiri: Tombol Login OAuth Facebook -->
                <div class="lg:col-span-6 card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-5 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                                <i class="fa-brands fa-facebook text-blue-600 dark:text-blue-500 text-base"></i>
                                <span>Otorisasi Akun (Metode Utama)</span>
                            </h3>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300">
                                1-Klik Login
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-gray-400 leading-relaxed">
                            Cukup login sekali menggunakan akun Facebook pengelola Page. Sistem akan mengamankan Long-Lived Token resmi untuk menerbitkan konten ke Facebook Page dan Instagram Business secara otomatis.
                        </p>

                        <div class="p-3.5 bg-slate-50 dark:bg-gray-900/80 rounded-lg border border-slate-200 dark:border-gray-800 text-[11px] text-slate-600 dark:text-gray-300 space-y-1">
                            <div class="font-semibold text-slate-900 dark:text-white flex items-center space-x-1.5">
                                <i class="fa-solid fa-shield-halved text-emerald-600 dark:text-emerald-400 text-xs"></i>
                                <span>Keamanan & Izin Resmi:</span>
                            </div>
                            <p class="text-slate-500 dark:text-gray-400 text-[10px]">
                                Aplikasi hanya meminta izin resmi untuk menerbitkan postingan & story. Kredensial disimpan terenkripsi AES-256 di database lokal Anda.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2 pt-4 border-t border-slate-200 dark:border-gray-800/80">
                        <a href="{{ route('meta.oauth') }}" 
                           class="w-full inline-flex items-center justify-center space-x-3 px-5 py-3.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg text-xs sm:text-sm shadow-md shadow-blue-600/20 transition transform active:scale-98">
                            <i class="fa-brands fa-facebook text-base"></i>
                            <span>{{ $isMetaConnected ? 'Hubungkan Ulang / Tambah Akun' : 'Hubungkan dengan Facebook Sekarang' }}</span>
                        </a>
                        <p class="text-[10px] text-center text-slate-400 dark:text-gray-500">
                            *Pastikan Meta App ID dan App Secret sudah disimpan di form sebelah kanan sebelum mengklik tombol di atas.
                        </p>
                    </div>
                </div>

                <!-- Kanan: Form Kredensial App ID & Secret -->
                <div class="lg:col-span-6 card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-5">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                            <i class="fa-solid fa-sliders text-indigo-600 dark:text-indigo-400 text-base"></i>
                            <span>Kredensial Meta App</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">
                            Diperlukan agar tombol login Facebook di sebelah kiri dapat mengenali aplikasi Anda.
                        </p>
                    </div>

                    <form action="{{ route('meta.updateCredentials') }}" method="POST" class="space-y-4 text-xs">
                        @csrf

                        <div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider text-[10px] mb-1">
                                    Meta App ID <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="app_id" value="{{ $credential->app_id }}" required placeholder="Contoh: 123456789012345"
                                       class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition font-mono">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider text-[10px] mb-1">
                                    Meta App Secret
                                </label>
                                <input type="password" name="app_secret" placeholder="{{ $credential->app_secret ? '•••••••••••••••• (Tersimpan)' : 'Masukkan App Secret' }}"
                                       class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition font-mono">
                            </div>
                        </div>

                        <!-- Kredensial Khusus Threads (Opsional jika menggunakan App yang sama) -->
                        <div class="p-3 bg-slate-100/70 dark:bg-gray-900/60 rounded-lg border border-slate-200 dark:border-gray-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-800 dark:text-gray-200 flex items-center space-x-1.5">
                                    <i class="fa-brands fa-threads text-sm"></i>
                                    <span>Kredensial Aplikasi Threads (Opsional)</span>
                                </span>
                                <span class="text-[9px] text-slate-400 dark:text-gray-500">Kosongkan jika memakai Meta App di atas</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-600 dark:text-gray-400 uppercase tracking-wider text-[9px] mb-1">
                                        Threads App ID
                                    </label>
                                    <input type="text" name="threads_app_id" value="{{ $credential->threads_app_id }}" placeholder="Opsional / default Meta App ID"
                                           class="w-full bg-white dark:bg-gray-950 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-600 dark:text-gray-400 uppercase tracking-wider text-[9px] mb-1">
                                        Threads App Secret
                                    </label>
                                    <input type="password" name="threads_app_secret" placeholder="{{ $credential->threads_app_secret ? '•••••••••••••••• (Tersimpan)' : 'Opsional / default Meta Secret' }}"
                                           class="w-full bg-white dark:bg-gray-950 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono">
                                </div>
                            </div>
                        </div>

                        <!-- Auto-Generated Redirect URIs -->
                        <div class="space-y-2">
                            <!-- 1. Meta Facebook / IG Redirect URI -->
                            <div class="bg-slate-50 dark:bg-gray-900/90 p-3 rounded-lg border border-slate-200 dark:border-gray-800 space-y-1">
                                <label class="block text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                                    Salin URL Ini ke Meta App (Facebook Login Redirect URI):
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="text" readonly value="{{ $callbackUrl }}" id="inputCallbackUrl"
                                           class="w-full bg-white dark:bg-gray-950 border border-slate-300 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-800 dark:text-gray-300 font-mono focus:outline-none">
                                    <button type="button" onclick="copyCallbackUrl()" 
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-200 text-[11px] font-semibold rounded-lg border border-slate-300 dark:border-gray-700 transition flex items-center space-x-1 flex-shrink-0">
                                        <i class="fa-regular fa-copy"></i>
                                        <span id="copyBtnText">Salin</span>
                                    </button>
                                </div>
                            </div>

                            <!-- 2. Meta Threads Redirect URI -->
                            <div class="bg-slate-50 dark:bg-gray-900/90 p-3 rounded-lg border border-slate-200 dark:border-gray-800 space-y-1">
                                <label class="block text-[10px] font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider flex items-center space-x-1">
                                    <i class="fa-brands fa-threads"></i>
                                    <span>Salin URL Ini ke Threads App (Valid OAuth Redirect URIs):</span>
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="text" readonly value="{{ $threadsCallbackUrl ?? route('threads.callback') }}" id="inputThreadsCallbackUrl"
                                           class="w-full bg-white dark:bg-gray-950 border border-slate-300 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-800 dark:text-gray-300 font-mono focus:outline-none">
                                    <button type="button" onclick="copyThreadsCallbackUrl()" 
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-200 text-[11px] font-semibold rounded-lg border border-slate-300 dark:border-gray-700 transition flex items-center space-x-1 flex-shrink-0">
                                        <i class="fa-regular fa-copy"></i>
                                        <span id="copyThreadsBtnText">Salin</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="graph_version" value="{{ $credential->graph_version ?: 'v22.0' }}">

                        <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition shadow flex items-center justify-center space-x-2">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Seluruh Kredensial App</span>
                        </button>
                    </form>
                </div>

            </div>

            <!-- Opsi Alternatif Pengguna Tingkat Lanjut: Token Manual / System User -->
            <div class="card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-4" x-data="{ openManual: false }">
                <div class="flex items-center justify-between cursor-pointer" @click="openManual = !openManual">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-terminal"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white">Metode Alternatif: Input Manual System User Token</h4>
                            <p class="text-[11px] text-slate-500 dark:text-gray-400">Gunakan jika Anda ingin memakai token permanen dari Meta Business Manager tanpa OAuth personal.</p>
                        </div>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-700 dark:text-gray-400 dark:hover:text-white text-xs">
                        <i class="fa-solid" :class="openManual ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                </div>

                <div x-show="openManual" x-transition class="pt-4 border-t border-slate-200 dark:border-gray-800 space-y-4">
                    <form action="{{ route('meta.saveManualToken') }}" method="POST" class="space-y-3 text-xs">
                        @csrf
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider text-[10px] mb-1">Tipe Token</label>
                            <select name="token_type" class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                                <option value="system_user">System User Token (Permanen / Tidak Kedaluwarsa)</option>
                                <option value="oauth_user">User Long-Lived Token</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider text-[10px] mb-1">Tempelkan Access Token</label>
                            <textarea name="access_token" required rows="2" placeholder="EAAG..."
                                      class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono break-all"></textarea>
                        </div>

                        <button type="submit" class="py-2 px-5 bg-purple-600 hover:bg-purple-500 text-white font-semibold rounded-lg text-xs transition shadow flex items-center space-x-1.5">
                            <i class="fa-solid fa-check"></i>
                            <span>Simpan & Sinkronkan Token</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- SUBTAB 2: DAFTAR AKUN TERHUBUNG -->
        <div x-show="metaSubTab === 'accounts'" class="space-y-6">
            <div class="card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-gray-800 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                            <i class="fa-solid fa-users text-indigo-600 dark:text-indigo-400"></i>
                            <span>Daftar Halaman Facebook & Akun Instagram</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400 mt-0.5">
                            Akun yang terdaftar di bawah ini siap dipilih saat Anda membuat Project Campaign.
                        </p>
                    </div>

                    <button onclick="triggerSyncNow()" 
                            class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg transition flex items-center space-x-1.5 shadow">
                        <i class="fa-solid fa-rotate text-xs"></i>
                        <span>Tarik Ulang dari Meta (Sync)</span>
                    </button>
                </div>

                @if($accounts->isEmpty())
                    <div class="text-center py-12 bg-slate-50/60 dark:bg-gray-900/40 rounded-xl border border-dashed border-slate-300 dark:border-gray-800 space-y-3">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg flex items-center justify-center mx-auto text-xl">
                            <i class="fa-solid fa-satellite-dish"></i>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">Belum Ada Akun Terdeteksi</h4>
                            <p class="text-xs text-slate-500 dark:text-gray-400 max-w-md mx-auto">
                                Silakan klik subtab <strong>Koneksi & Kredensial</strong> dan lakukan login via Facebook untuk memuat daftar akun.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($accounts as $acc)
                            <div class="bg-slate-50/90 dark:bg-gray-900/90 rounded-xl p-4 border {{ $acc->is_active ? 'border-slate-200 dark:border-gray-800 hover:border-slate-300 dark:hover:border-gray-700' : 'border-rose-300 dark:border-rose-900/60 bg-rose-50/30 dark:bg-rose-950/10' }} transition flex flex-col justify-between space-y-4 shadow-sm">
                                
                                <!-- Header Akun -->
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-600/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg flex-shrink-0">
                                            <i class="fa-brands fa-facebook-f"></i>
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-white text-sm block leading-snug">{{ $acc->page_name }}</span>
                                            <span class="text-[11px] text-slate-500 dark:text-gray-400 font-mono">Page ID: {{ $acc->page_id }}</span>
                                        </div>
                                    </div>

                                    <div>
                                        @if($acc->is_active)
                                            <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-emerald-100 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-400">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-rose-100 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-400">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Linked Instagram Info -->
                                <div class="p-3 bg-white dark:bg-gray-950/80 rounded-lg border border-slate-200 dark:border-gray-800/80 space-y-2 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Instagram Terhubung:</span>
                                        @if($acc->ig_user_id)
                                            <span class="text-[10px] text-pink-600 dark:text-pink-400 font-bold flex items-center space-x-1">
                                                <i class="fa-brands fa-instagram"></i>
                                                <span>Connected</span>
                                            </span>
                                        @else
                                            <span class="text-[10px] text-slate-400 dark:text-gray-500 italic">Belum Ada Akun IG</span>
                                        @endif
                                    </div>

                                    @if($acc->ig_user_id)
                                        <div class="flex items-center space-x-2.5">
                                            @if($acc->ig_profile_picture_url)
                                                <img src="{{ $acc->ig_profile_picture_url }}" class="w-7 h-7 rounded-full object-cover border border-pink-500/40">
                                            @else
                                                <div class="w-7 h-7 rounded-full bg-pink-100 dark:bg-pink-600/20 text-pink-600 dark:text-pink-400 flex items-center justify-center text-xs">
                                                    <i class="fa-brands fa-instagram"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <span class="font-bold text-pink-600 dark:text-pink-300 block leading-tight">&#64;{{ $acc->ig_username }}</span>
                                                <span class="text-[10px] text-slate-500 dark:text-gray-500 font-mono">IG ID: {{ $acc->ig_user_id }}</span>
                                            </div>
                                        </div>

                                        <!-- Rate Limit Bar -->
                                        <div class="pt-1.5 border-t border-slate-100 dark:border-gray-800/60 space-y-1">
                                            <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-gray-400">
                                                <span>Kuota Publish 24 Jam:</span>
                                                <strong class="text-indigo-600 dark:text-indigo-400 font-mono">{{ $acc->ig_publishing_quota_usage }} / {{ $acc->ig_publishing_quota_total }} Post</strong>
                                            </div>
                                            <div class="w-full bg-slate-200 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                                                @php
                                                    $pct = min(100, round(($acc->ig_publishing_quota_usage / max(1, $acc->ig_publishing_quota_total)) * 100));
                                                    $barColor = $pct > 80 ? 'bg-rose-500' : ($pct > 50 ? 'bg-amber-500' : 'bg-emerald-500');
                                                @endphp
                                                <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-[11px] text-slate-500 dark:text-gray-500 leading-relaxed">
                                            Halaman ini belum dihubungkan ke Instagram Business Account di Meta Business Suite. Postingan hanya akan terbit ke Facebook Page.
                                        </p>
                                    @endif
                                </div>

                                <!-- Linked Threads Info -->
                                <div class="p-3 bg-white dark:bg-gray-950/80 rounded-lg border border-slate-200 dark:border-gray-800/80 space-y-2 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Threads Terhubung:</span>
                                        @if($acc->hasThreads())
                                            <span class="text-[10px] text-slate-900 dark:text-slate-100 font-bold flex items-center space-x-1">
                                                <i class="fa-brands fa-threads"></i>
                                                <span>Connected</span>
                                            </span>
                                        @else
                                            <span class="text-[10px] text-slate-400 dark:text-gray-500 italic">Belum Terhubung</span>
                                        @endif
                                    </div>

                                    @if($acc->hasThreads())
                                        <div class="flex items-center space-x-2.5">
                                            @if($acc->threads_profile_picture_url)
                                                <img src="{{ $acc->threads_profile_picture_url }}" class="w-7 h-7 rounded-full object-cover border border-slate-400/40">
                                            @else
                                                <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center text-xs">
                                                    <i class="fa-brands fa-threads"></i>
                                                </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <span class="font-bold text-slate-900 dark:text-slate-100 block leading-tight truncate">&#64;{{ $acc->threads_username }}</span>
                                                <span class="text-[10px] text-slate-500 dark:text-gray-500 font-mono block truncate">Threads ID: {{ $acc->threads_user_id }}</span>
                                            </div>
                                            <div class="flex items-center space-x-1 shrink-0">
                                                <button onclick="testThreadsConnection({{ $acc->id }}, '{{ addslashes($acc->threads_username) }}')" 
                                                        class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-gray-800 text-amber-600 dark:text-amber-400 transition" 
                                                        title="Tes Koneksi Threads">
                                                    <i class="fa-solid fa-bolt text-xs"></i>
                                                </button>
                                                <button onclick="disconnectThreads({{ $acc->id }}, '{{ addslashes($acc->threads_username) }}')" 
                                                        class="p-1.5 rounded-md hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-500 transition" 
                                                        title="Putuskan Threads">
                                                    <i class="fa-solid fa-link-slash text-xs"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Rate Limit Bar Threads (250 Post / 24 jam) -->
                                        <div class="pt-1.5 border-t border-slate-100 dark:border-gray-800/60 space-y-1">
                                            <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-gray-400">
                                                <span>Kuota Threads (24 Jam):</span>
                                                <strong class="text-indigo-600 dark:text-indigo-400 font-mono">{{ $acc->threads_publishing_quota_usage }} / {{ $acc->threads_publishing_quota_total }} Post</strong>
                                            </div>
                                            <div class="w-full bg-slate-200 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                                                @php
                                                    $threadsPct = min(100, round(($acc->threads_publishing_quota_usage / max(1, $acc->threads_publishing_quota_total)) * 100));
                                                    $threadsBarColor = $threadsPct > 80 ? 'bg-rose-500' : ($threadsPct > 50 ? 'bg-amber-500' : 'bg-emerald-500');
                                                @endphp
                                                <div class="{{ $threadsBarColor }} h-1.5 rounded-full" style="width: {{ $threadsPct }}%"></div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="pt-1 flex items-center justify-between gap-2">
                                            <a href="{{ route('threads.oauth', ['account_id' => $acc->id]) }}" 
                                               class="flex-1 py-1.5 px-2 bg-slate-900 hover:bg-black text-white dark:bg-slate-800 dark:hover:bg-slate-700 text-[10px] font-semibold rounded-lg text-center transition flex items-center justify-center space-x-1 shadow-sm min-h-[36px]">
                                                <i class="fa-brands fa-threads"></i>
                                                <span>Hubungkan Threads</span>
                                            </a>
                                            <button type="button" 
                                                    onclick="openManualThreadsTokenModal({{ $acc->id }}, '{{ addslashes($acc->page_name) }}')" 
                                                    class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 text-[10px] font-semibold rounded-lg transition min-h-[36px]" 
                                                    title="Input Token Threads Manual">
                                                <i class="fa-solid fa-key"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <!-- Footer Card Action -->
                                <div class="flex items-center justify-between pt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                    <span>Kategori: <strong class="text-slate-700 dark:text-gray-300">{{ $acc->page_category ?: 'Bisnis' }}</strong></span>
                                    <div class="flex items-center space-x-1.5">
                                        <button onclick="testAccountConnection('{{ $acc->page_id }}', '{{ $acc->page_name }}')"
                                                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 dark:border-gray-700 transition flex items-center space-x-1">
                                            <i class="fa-solid fa-bolt text-amber-500 text-[10px]"></i>
                                            <span>Tes Akses</span>
                                        </button>
                                        @if(!$acc->is_active)
                                            <button onclick="deleteAccountItem({{ $acc->id }}, '{{ addslashes($acc->page_name) }}')"
                                                    class="px-2 py-1 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-700 border border-rose-300 dark:bg-rose-950/60 dark:hover:bg-rose-900/80 dark:text-rose-300 dark:border-rose-800/80 transition flex items-center space-x-1" title="Hapus Akun Nonaktif">
                                                <i class="fa-solid fa-trash text-[10px]"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- SUBTAB 3: RIWAYAT & LANJUTAN -->
        <div x-show="metaSubTab === 'advanced'" class="space-y-6">
            
            <!-- Refresh & Info Token -->
            <div class="card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <i class="fa-solid fa-arrows-rotate text-indigo-600 dark:text-indigo-400"></i>
                    <span>Manajemen Refresh Token</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    Token Facebook Login memiliki masa berlaku ~60 hari. Meta mengizinkan perpanjangan token kapan saja selama masa aktif belum habis.
                </p>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-slate-50 dark:bg-gray-900/80 rounded-lg border border-slate-200 dark:border-gray-800">
                    <div class="text-xs">
                        <span class="text-slate-500 dark:text-gray-400 block">Estimasi Kedaluwarsa:</span>
                        <strong class="text-slate-900 dark:text-white text-sm">
                            {{ $credential->token_expires_at ? $credential->token_expires_at->format('d F Y, H:i') : 'Permanen' }}
                        </strong>
                    </div>

                    @if($credential->user_access_token)
                        <button onclick="refreshTokenManual()" 
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg transition flex items-center space-x-1.5 shadow">
                            <i class="fa-solid fa-rotate"></i>
                            <span>Perpanjang Token Sekarang (+60 Hari)</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Webhook Token Field (Opsional) -->
            <div class="card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-3 text-xs">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <i class="fa-solid fa-tower-broadcast text-purple-600 dark:text-purple-400"></i>
                    <span>Meta Webhook Verify Token (Fitur Lanjutan Opsional)</span>
                </h3>
                <p class="text-slate-500 dark:text-gray-400">
                    Digunakan jika Anda ingin menerima event/notifikasi real-time langsung dari server Meta.
                </p>
                <div class="font-mono text-slate-700 dark:text-gray-300 p-2.5 bg-slate-50 dark:bg-gray-900 rounded-lg border border-slate-200 dark:border-gray-800">
                    Verify Token: <span class="text-indigo-600 dark:text-indigo-400">{{ $credential->webhook_verify_token ?: 'Belum diset' }}</span>
                </div>
            </div>

            <!-- Log Aktivitas -->
            <div class="card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <i class="fa-solid fa-clock-rotate-left text-indigo-600 dark:text-indigo-400"></i>
                    <span>Catatan Aktivitas Token & Sinkronisasi</span>
                </h3>

                @if($logs->isEmpty())
                    <p class="text-xs text-slate-400 dark:text-gray-500 italic">Belum ada riwayat aktivitas.</p>
                @else
                    <div class="divide-y divide-slate-200/80 dark:divide-gray-800/60 font-mono text-[11px]">
                        @foreach($logs as $log)
                            <div class="py-2.5 flex items-start justify-between gap-4">
                                <div class="space-y-0.5">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] uppercase font-bold {{ $log->status === 'success' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-400' }}">
                                            {{ $log->status }}
                                        </span>
                                        <strong class="text-slate-800 dark:text-gray-200">{{ strtoupper($log->action) }}</strong>
                                    </div>
                                    <p class="text-slate-600 dark:text-gray-400 font-sans break-words">{{ $log->details }}</p>
                                </div>
                                <span class="text-slate-400 dark:text-gray-500 text-[10px] whitespace-nowrap">{{ $log->created_at->format('d/m H:i:s') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div> <!-- Tutup SUBTAB 3: RIWAYAT & LANJUTAN (metaSubTab === 'advanced') -->

    </div> <!-- Tutup TAB 2: META INTEGRATION (activeTab === 'meta') -->

    <!-- Modal Input Token Threads Manual -->
    <div id="manualThreadsModal" class="fixed inset-0 z-50 hidden bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="card-dark rounded-xl max-w-lg w-full border border-slate-200 dark:border-gray-800 p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-gray-800 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <i class="fa-brands fa-threads text-base"></i>
                    <span>Input Token Threads Manual</span>
                </h3>
                <button type="button" onclick="closeManualThreadsTokenModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-base">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formManualThreads" onsubmit="submitManualThreadsToken(event)" class="space-y-4 text-xs">
                <input type="hidden" id="threadsAccountId" name="account_id">

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider text-[10px] mb-1">
                        Akun Target
                    </label>
                    <input type="text" id="threadsAccountName" readonly 
                           class="w-full bg-slate-100 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-2 text-xs text-slate-700 dark:text-gray-300 font-semibold focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider text-[10px] mb-1">
                        Threads User ID (Opsional jika token sudah mencakup user profil)
                    </label>
                    <input type="text" name="threads_user_id" id="inputThreadsUserId" placeholder="Contoh: 17841400000000000"
                           class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider text-[10px] mb-1">
                        Access Token Threads <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="threads_access_token" id="inputThreadsToken" required rows="3" placeholder="Tempelkan token Threads (THQ... atau token dari Threads Graph API)"
                              class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono break-all"></textarea>
                </div>

                <div class="p-3 bg-slate-50 dark:bg-gray-900/90 rounded-lg border border-slate-200 dark:border-gray-800 text-[11px] text-slate-600 dark:text-gray-400 space-y-1">
                    <span class="font-semibold text-slate-800 dark:text-gray-200 block">Catatan Token:</span>
                    <p>Sistem akan otomatis memvalidasi token ke endpoint Threads API dan menukarkannya menjadi Long-Lived Token (~60 hari) serta mengambil username & kuota publikasi.</p>
                </div>

                <div class="pt-3 border-t border-slate-200 dark:border-gray-800 flex items-center justify-end space-x-2">
                    <button type="button" onclick="closeManualThreadsTokenModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 rounded-lg text-xs font-semibold transition min-h-[44px]">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitThreadsToken" class="px-5 py-2 bg-slate-900 hover:bg-black text-white dark:bg-indigo-600 dark:hover:bg-indigo-500 font-semibold rounded-lg text-xs transition shadow flex items-center space-x-1.5 min-h-[44px]">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan & Tautkan Threads</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    // Tab URL Synchronizer
    function updateUrlTab(tab) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }

    // =========================================================================
    // STORAGE TEST CONNECTION (CLOUDFLARE R2)
    // =========================================================================
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

    // =========================================================================
    // META GRAPH API SCRIPTS
    // =========================================================================
    function copyCallbackUrl() {
        const input = document.getElementById('inputCallbackUrl');
        input.select();
        navigator.clipboard.writeText(input.value);
        document.getElementById('copyBtnText').textContent = 'Tersalin!';
        setTimeout(() => document.getElementById('copyBtnText').textContent = 'Salin', 2000);
    }

    function refreshTokenManual() {
        showLoading('Memperpanjang Token...', 'Menghubungi endpoint OAuth Meta untuk merotasi ke token 60 hari baru...');
        fetch("{{ route('meta.refreshToken') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Token Berhasil Diperpanjang!', data.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('error', 'Gagal Memperpanjang', data.message);
            }
        })
        .catch(err => {
            showAlert('error', 'Error', err.message);
        });
    }

    function testConnection() {
        showLoading('Menguji Koneksi...', 'Melakukan test call ringan ke Meta Graph API...');
        fetch("{{ route('meta.testConnection') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Koneksi Berhasil!',
                    html: `<div class="text-left text-xs text-slate-600 dark:text-gray-300 space-y-1.5">
                             <p><strong class="text-slate-900 dark:text-white">Status:</strong> Komunikasi ke Meta Graph API normal</p>
                             <p><strong class="text-slate-900 dark:text-white">Waktu Respon (Latency):</strong> <span class="text-emerald-600 dark:text-emerald-400 font-bold">${data.latency_ms} ms</span></p>
                           </div>`,
                    customClass: {
                        popup: 'swal2-popup-dark',
                        title: 'swal2-title-dark',
                        htmlContainer: 'swal2-html-dark'
                    }
                });
            } else {
                showAlert('error', 'Koneksi Gagal', data.message || 'Token Meta API belum aktif atau salah.');
            }
        })
        .catch(err => {
            showAlert('error', 'Error', err.message);
        });
    }

    function testAccountConnection(pageId, pageName) {
        showLoading(`Menguji Akses '${pageName}'...`, 'Memverifikasi Page Access Token...');
        fetch("{{ route('meta.testConnection') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ target_id: pageId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Halaman Valid!', `Halaman '${pageName}' aktif dan siap digunakan (${data.latency_ms}ms).`);
            } else {
                showAlert('error', 'Akses Gagal', data.message);
            }
        })
        .catch(err => {
            showAlert('error', 'Error', err.message);
        });
    }

    function triggerSyncNow() {
        showLoading('Menjalankan Sinkronisasi...', 'Menarik daftar Facebook Pages dan akun Instagram terbaru dari Meta...');
        fetch("{{ route('meta.syncNow') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Sinkronisasi Selesai!', data.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('error', 'Sinkronisasi Gagal', data.message);
            }
        })
        .catch(err => {
            showAlert('error', 'Error', err.message);
        });
    }

    function deleteAccountItem(id, pageName) {
        Swal.fire({
            title: `Hapus '${pageName}'?`,
            text: 'Akun nonaktif ini akan dihapus dari daftar lokal Anda.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'swal2-popup-dark',
                title: 'swal2-title-dark',
                htmlContainer: 'swal2-html-dark'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Menghapus...', 'Menghapus data akun...');
                fetch(`/meta-integration/accounts/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Berhasil Dihapus!', data.message);
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        showAlert('error', 'Gagal', data.message);
                    }
                })
                .catch(err => {
                    showAlert('error', 'Error', err.message);
                });
            }
        });
    }

    // =========================================================================
    // THREADS API SCRIPTS
    // =========================================================================
    function copyThreadsCallbackUrl() {
        const input = document.getElementById('inputThreadsCallbackUrl');
        input.select();
        navigator.clipboard.writeText(input.value);
        document.getElementById('copyThreadsBtnText').textContent = 'Tersalin!';
        setTimeout(() => document.getElementById('copyThreadsBtnText').textContent = 'Salin', 2000);
    }

    function openManualThreadsTokenModal(accountId, pageName) {
        document.getElementById('threadsAccountId').value = accountId;
        document.getElementById('threadsAccountName').value = pageName;
        document.getElementById('inputThreadsToken').value = '';
        document.getElementById('inputThreadsUserId').value = '';
        document.getElementById('manualThreadsModal').classList.remove('hidden');
    }

    function closeManualThreadsTokenModal() {
        document.getElementById('manualThreadsModal').classList.add('hidden');
    }

    function submitManualThreadsToken(e) {
        e.preventDefault();
        const accountId = document.getElementById('threadsAccountId').value;
        const token = document.getElementById('inputThreadsToken').value;
        const threadsUserId = document.getElementById('inputThreadsUserId').value;
        const btn = document.getElementById('btnSubmitThreadsToken');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memvalidasi...';

        fetch("{{ route('threads.saveManualToken') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                account_id: accountId,
                threads_access_token: token,
                threads_user_id: threadsUserId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Berhasil!', data.message);
                closeManualThreadsTokenModal();
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showAlert('error', 'Gagal', data.message || 'Gagal menyimpan token Threads.');
            }
        })
        .catch(err => {
            showAlert('error', 'Error', err.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i> Simpan & Tautkan Threads';
        });
    }

    function testThreadsConnection(id, username) {
        showLoading('Menguji Koneksi Threads...', `Menghubungi profil Threads @${username}...`);
        fetch(`/threads/${id}/test-connection`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const limitText = data.limit ? `\nKuota Terpakai: ${data.limit.quota_usage || 0} / 250 Post` : '';
                showAlert('success', 'Koneksi Threads Valid!', data.message + limitText);
            } else {
                showAlert('error', 'Koneksi Gagal', data.message);
            }
        })
        .catch(err => {
            showAlert('error', 'Error', err.message);
        });
    }

    function disconnectThreads(id, username) {
        Swal.fire({
            title: `Putuskan Threads @${username}?`,
            text: 'Token Threads akan dihapus dan akun tidak lagi menerima postingan Threads otomatis.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Putuskan',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'swal2-popup-dark',
                title: 'swal2-title-dark',
                htmlContainer: 'swal2-html-dark'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Memutuskan...', 'Menghapus kredensial Threads...');
                fetch(`/threads/${id}/disconnect`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Diputuskan', data.message);
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        showAlert('error', 'Gagal', data.message);
                    }
                })
                .catch(err => {
                    showAlert('error', 'Error', err.message);
                });
            }
        });
    }
</script>
@endsection
