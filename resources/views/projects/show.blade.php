@extends('layouts.app')

@section('title', $project->name . ' - Detail Campaign')

@section('content')
<div class="space-y-8">

    <!-- Breadcrumb & Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-gray-800 pb-5">
        <div class="space-y-1">
            <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-gray-400">
                <a href="{{ route('projects.index') }}" class="hover:text-slate-900 dark:hover:text-white transition flex items-center space-x-1">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Project Campaigns</span>
                </a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white font-semibold">{{ $project->name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center space-x-3">
                <span>{{ $project->name }}</span>
                <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase rounded-md {{ $project->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-slate-600 dark:text-gray-400' }}">
                    {{ $project->status === 'active' ? 'Aktif' : 'Dijeda' }}
                </span>
            </h1>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('projects.edit', $project->id) }}" 
               class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-200 dark:border-gray-700 transition flex items-center space-x-1.5 shadow-sm">
                <i class="fa-solid fa-pen"></i>
                <span>Edit</span>
            </a>
            <button onclick="toggleProjectStatus({{ $project->id }}, '{{ $project->name }}')" 
                    class="px-3.5 py-2 text-xs font-semibold rounded-lg {{ $project->status === 'active' ? 'bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 dark:bg-amber-950 dark:hover:bg-amber-900 dark:border-amber-800 dark:text-amber-300' : 'bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 dark:bg-emerald-950 dark:hover:bg-emerald-900 dark:border-emerald-800 dark:text-emerald-300' }} transition flex items-center space-x-1.5 shadow-sm">
                <i class="fa-solid {{ $project->status === 'active' ? 'fa-pause' : 'fa-play' }}"></i>
                <span>{{ $project->status === 'active' ? 'Jeda Campaign' : 'Aktifkan Kembali' }}</span>
            </button>
        </div>
    </div>

    <!-- Soft-deactivation warning if any target is inactive -->
    @if($project->hasInactiveAccount())
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs flex items-center space-x-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 dark:text-rose-400 text-xl flex-shrink-0"></i>
            <div>
                <strong class="block font-bold">⚠ Aset Tidak Ditemukan / Nonaktif:</strong>
                <span>Satu atau lebih akun Meta target pada campaign ini tidak lagi terdeteksi di Meta (Soft-Deactivation). Publishing ke akun tersebut otomatis dilewati untuk menjaga kelancaran campaign.</span>
            </div>
        </div>
    @endif

    <!-- Key Campaign Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Tipe Konten -->
        <div class="card-dark rounded-xl p-4 border border-slate-200/90 dark:border-gray-800">
            <span class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Tipe Konten</span>
            <div class="mt-1 flex items-center space-x-2">
                @if($project->content_type === 'story')
                    <i class="fa-solid fa-circle-notch text-pink-500"></i>
                    <span class="font-bold text-slate-900 dark:text-white text-sm">Story</span>
                @else
                    <i class="fa-solid fa-square-rss text-blue-500 dark:text-blue-400"></i>
                    <span class="font-bold text-slate-900 dark:text-white text-sm">Feed Post</span>
                @endif
            </div>
        </div>

        <!-- Jam Tayang -->
        <div class="card-dark rounded-xl p-4 border border-slate-200/90 dark:border-gray-800">
            <span class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Jam Tayang</span>
            <div class="mt-1 flex items-center space-x-2">
                <i class="fa-regular fa-clock text-indigo-600 dark:text-indigo-400"></i>
                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $project->target_time }} WIB</span>
            </div>
        </div>

        <!-- Moda Pengulangan -->
        <div class="card-dark rounded-xl p-4 border border-slate-200/90 dark:border-gray-800">
            <span class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Moda Pengulangan</span>
            <div class="mt-1 font-bold text-slate-900 dark:text-white text-sm">
                @if($project->repeat_type === 'continuous')
                    ♾️ Kontinu
                @elseif($project->repeat_type === 'once')
                    🎯 1x Post
                @else
                    📅 s/d {{ $project->end_date ? $project->end_date->format('d/m/Y') : '-' }}
                @endif
            </div>
        </div>

        <!-- Jadwal Terakhir -->
        <div class="card-dark rounded-xl p-4 border border-slate-200/90 dark:border-gray-800">
            <span class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Jadwal Terakhir</span>
            <div class="mt-1 font-bold text-indigo-600 dark:text-indigo-400 text-sm">
                {{ $furthestDateFormatted }}
            </div>
        </div>

        <!-- Antrean Status -->
        <div class="card-dark rounded-xl p-4 border border-slate-200/90 dark:border-gray-800 col-span-2 sm:col-span-1">
            <span class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Antrean</span>
            <div class="mt-1 flex items-center space-x-3 text-xs">
                <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ $pendingCount }} Pending</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $completedCount }} OK</span>
                @if($failedCount > 0)
                    <span class="text-rose-600 dark:text-rose-400 font-bold">{{ $failedCount }} Gagal</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Target Accounts Section (Bagian 2.1) -->
    <div class="card-dark rounded-xl p-5 border border-slate-200/90 dark:border-gray-800 space-y-4">
        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
            <i class="fa-solid fa-bullseye text-indigo-600 dark:text-indigo-400"></i>
            <span>Target Akun & Platform Dituju ({{ $project->targets->count() }})</span>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            @foreach($project->targets as $target)
                @php
                    $acc = $target->connectedAccount;
                @endphp
                <div class="bg-slate-100/90 dark:bg-gray-900/90 p-3.5 rounded-lg border {{ ($acc && !$acc->is_active) ? 'border-rose-300 dark:border-rose-800/80 text-rose-700 dark:text-rose-300' : 'border-slate-200 dark:border-gray-800' }} flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-600/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-sm flex-shrink-0">
                            <i class="fa-brands fa-facebook-f"></i>
                        </div>
                        <div>
                            <span class="font-bold text-slate-900 dark:text-white text-xs block leading-tight">{{ $acc ? $acc->page_name : 'Unknown' }}</span>
                            <span class="text-[11px] text-slate-500 dark:text-gray-400">
                                @if($acc && $acc->ig_username)
                                    <i class="fa-brands fa-instagram text-pink-500 dark:text-pink-400 ml-0.5 mr-1"></i>&#64;{{ $acc->ig_username }}
                                @else
                                    <span class="text-slate-400 dark:text-gray-500 italic">Tanpa IG</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    <div>
                        @if($target->platform_target === 'both')
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-indigo-50 dark:bg-indigo-950 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300">
                                Both (FB + IG)
                            </span>
                        @elseif($target->platform_target === 'instagram_only')
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-pink-50 dark:bg-pink-950 border border-pink-200 dark:border-pink-800 text-pink-700 dark:text-pink-300">
                                Instagram Saja
                            </span>
                        @elseif($target->platform_target === 'facebook_only')
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300">
                                Facebook Saja
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Media Pool Section -->
    <div class="card-dark rounded-xl p-5 border border-slate-200/90 dark:border-gray-800 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                <i class="fa-solid fa-photo-film text-indigo-600 dark:text-indigo-400"></i>
                <span>Media Pool Assets ({{ $project->mediaFiles->count() }})</span>
            </h2>
            
            <form id="formAddMedia" enctype="multipart/form-data" class="flex items-center space-x-2">
                <input type="file" name="media_files[]" multiple accept="image/jpeg,image/png,video/mp4,video/quicktime" id="inputDirectAddMedia" class="hidden" onchange="uploadDirectMedia()">
                <label for="inputDirectAddMedia" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs rounded-lg cursor-pointer transition flex items-center space-x-1.5 shadow-sm">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Tambah Media</span>
                </label>
            </form>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
            @foreach($project->mediaFiles as $media)
                <div class="group relative bg-slate-100 dark:bg-gray-900 border border-slate-200 dark:border-gray-800 rounded-lg overflow-hidden shadow-sm cursor-pointer transition hover:border-indigo-500"
                     onclick="openLightboxDirect('{{ $media->url }}', {{ $media->is_video ? 'true' : 'false' }}, '{{ $media->original_name }}')">
                    @if($media->is_video)
                        <div class="w-full h-28 bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <i class="fa-solid fa-video text-2xl"></i>
                        </div>
                    @else
                        <img src="{{ $media->url }}" class="w-full h-28 object-cover group-hover:scale-105 transition duration-300">
                    @endif
                    <div class="p-2 bg-slate-50 dark:bg-gray-950/90 text-[10px] text-slate-600 dark:text-gray-300 truncate font-mono border-t border-slate-200 dark:border-gray-800/60">
                        {{ $media->original_name }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Antrean Jadwal (Schedules) for this campaign -->
    <div class="card-dark rounded-xl p-5 border border-slate-200/90 dark:border-gray-800 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                <i class="fa-solid fa-calendar-days text-indigo-600 dark:text-indigo-400"></i>
                <span>Antrean Jadwal Tayang ({{ $project->schedules->count() }} Total)</span>
            </h2>

            <div class="flex flex-wrap items-center gap-2">
                @php
                    $firstPending = $project->schedules->where('status', 'pending')->first();
                @endphp
                @if($firstPending)
                    <button onclick="publishScheduledWithProgress({{ $firstPending->id }}, '{{ $firstPending->target_date ? $firstPending->target_date->translatedFormat('d M Y') : '' }} {{ $firstPending->target_time }} WIB', '{{ addslashes($project->name) }}')" 
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white shadow-sm shadow-indigo-600/20 transition flex items-center space-x-1.5"
                            title="Publikasikan jadwal pending terdekat dengan visual progress">
                        <i class="fa-solid fa-bolt text-amber-300 text-xs"></i>
                        <span>Terbitkan Jadwal Terdekat</span>
                    </button>
                @endif

                <button onclick="triggerPublishNowWithProgress({{ $project->id }}, '{{ addslashes($project->name) }}')" 
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-200 dark:border-gray-700 transition flex items-center space-x-1.5 shadow-sm"
                        title="Jalankan publikasi semua jadwal campaign ini yang sudah jatuh tempo">
                    <i class="fa-solid fa-paper-plane text-xs text-indigo-600 dark:text-indigo-400"></i>
                    <span>Publikasikan Jatuh Tempo</span>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-gray-300">
                <thead class="bg-slate-100/90 dark:bg-gray-900/80 text-slate-600 dark:text-gray-400 uppercase font-semibold text-[10px] tracking-wider border-b border-slate-200 dark:border-gray-800">
                    <tr>
                        <th class="py-3 px-4">Media</th>
                        <th class="py-3 px-4">Tanggal & Jam Tayang</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Catatan Eksekusi</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-gray-800/60">
                    @foreach($project->schedules->take(20) as $sch)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-900/40 transition">
                            <td class="py-3 px-4">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-gray-900 border border-slate-200 dark:border-gray-800 overflow-hidden flex-shrink-0 cursor-pointer"
                                     onclick="openLightboxDirect('{{ $sch->media_url }}', false, 'Jadwal {{ $sch->target_date->format('d M Y') }}')">
                                    <img src="{{ $sch->media_url }}" class="w-full h-full object-cover">
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $sch->target_date->format('d M Y') }}</span>
                                <span class="text-[11px] text-slate-500 dark:text-gray-400">{{ $sch->target_time }} WIB</span>
                            </td>
                            <td class="py-3 px-4">
                                <button type="button" onclick="openChangeStatusModal({{ $sch->id }}, '{{ $sch->status }}', '{{ $sch->target_date ? $sch->target_date->translatedFormat('d M Y') : '' }}')"
                                        class="inline-flex items-center space-x-1 cursor-pointer group" title="Klik untuk mengubah status tanggal ini">
                                    @if($sch->status === 'completed')
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 group-hover:border-emerald-500 transition">
                                            Selesai <i class="fa-solid fa-pen text-[8px] ml-0.5 opacity-60"></i>
                                        </span>
                                    @elseif($sch->status === 'pending')
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400 group-hover:border-amber-500 transition">
                                            Pending <i class="fa-solid fa-pen text-[8px] ml-0.5 opacity-60"></i>
                                        </span>
                                    @elseif($sch->status === 'processing')
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-400 animate-pulse">
                                            Memproses
                                        </span>
                                    @elseif($sch->status === 'skipped')
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-gray-400 group-hover:border-slate-400 transition">
                                            Dilewati <i class="fa-solid fa-pen text-[8px] ml-0.5 opacity-60"></i>
                                        </span>
                                    @elseif($sch->status === 'partially_failed')
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-orange-50 dark:bg-orange-950/80 border border-orange-200 dark:border-orange-800 text-orange-700 dark:text-orange-400 group-hover:border-orange-500 transition">
                                            Sebagian Gagal <i class="fa-solid fa-pen text-[8px] ml-0.5 opacity-60"></i>
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 group-hover:border-rose-500 transition">
                                            Gagal <i class="fa-solid fa-pen text-[8px] ml-0.5 opacity-60"></i>
                                        </span>
                                    @endif
                                </button>
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-gray-400 text-[11px]">
                                {{ $sch->notes ?: '-' }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end space-x-1.5">
                                    @if($sch->status === 'completed')
                                        <button onclick="promptRepublishOption({{ $sch->id }}, '{{ $sch->target_date ? $sch->target_date->translatedFormat('d M Y') : '' }} {{ $sch->target_time }} WIB', '{{ addslashes($project->name) }}')" 
                                                class="px-2.5 py-1 text-[11px] font-semibold rounded-md bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-600/20 dark:hover:bg-amber-600 dark:text-amber-300 dark:hover:text-white border border-amber-200 dark:border-amber-500/30 transition shadow-sm flex items-center space-x-1" 
                                                title="Terbitkan ulang jadwal ini">
                                            <i class="fa-solid fa-arrows-rotate text-[10px]"></i>
                                            <span>Terbitkan Ulang</span>
                                        </button>
                                    @elseif(in_array($sch->status, ['failed', 'partially_failed']))
                                        <button onclick="publishScheduledWithProgress({{ $sch->id }}, '{{ $sch->target_date ? $sch->target_date->translatedFormat('d M Y') : '' }} {{ $sch->target_time }} WIB', '{{ addslashes($project->name) }}')" 
                                                class="px-2.5 py-1 text-[11px] font-semibold rounded-md bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-600/20 dark:hover:bg-rose-600 dark:text-rose-300 dark:hover:text-white border border-rose-200 dark:border-rose-500/30 transition shadow-sm flex items-center space-x-1" 
                                                title="Coba terbitkan lagi dengan visual live loading">
                                            <i class="fa-solid fa-rotate-left text-[10px]"></i>
                                            <span>Coba Lagi</span>
                                        </button>
                                    @else
                                        <button onclick="publishScheduledWithProgress({{ $sch->id }}, '{{ $sch->target_date ? $sch->target_date->translatedFormat('d M Y') : '' }} {{ $sch->target_time }} WIB', '{{ addslashes($project->name) }}')" 
                                                class="px-2.5 py-1 text-[11px] font-semibold rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600 dark:text-indigo-300 dark:hover:text-white border border-indigo-200 dark:border-indigo-500/30 transition shadow-sm flex items-center space-x-1" 
                                                title="Terbitkan konten terjadwal ini sekarang juga dengan live progress">
                                            <i class="fa-solid fa-paper-plane text-[10px]"></i>
                                            <span>Terbitkan Sekarang</span>
                                        </button>
                                    @endif

                                    <!-- Tombol Ubah Status -->
                                    <button onclick="openChangeStatusModal({{ $sch->id }}, '{{ $sch->status }}', '{{ $sch->target_date ? $sch->target_date->translatedFormat('d M Y') : '' }}')"
                                            class="px-2 py-1 text-[11px] font-semibold rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 dark:border-gray-700 transition" 
                                            title="Ubah Status Jadwal Tanggal Ini">
                                        <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Riwayat Log Publikasi Per Target Per Platform (Bagian 2.1 & 11) -->
    <div class="card-dark rounded-xl p-5 border border-slate-200/90 dark:border-gray-800 space-y-4">
        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
            <i class="fa-solid fa-list-check text-indigo-600 dark:text-indigo-400"></i>
            <span>Log Publikasi Per Target & Per Platform (50 Terakhir)</span>
        </h2>

        @if($project->publishLogs->isEmpty())
            <p class="text-xs text-slate-500 dark:text-gray-500 italic">Belum ada riwayat publikasi untuk campaign ini.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-gray-300">
                    <thead class="bg-slate-100/90 dark:bg-gray-900/80 text-slate-600 dark:text-gray-400 uppercase font-semibold text-[10px] tracking-wider border-b border-slate-200 dark:border-gray-800">
                        <tr>
                            <th class="py-3 px-4">Waktu</th>
                            <th class="py-3 px-4">Target Akun</th>
                            <th class="py-3 px-4">Platform</th>
                            <th class="py-3 px-4">Tipe</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Detail / Media ID / Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-gray-800/60 font-mono text-[11px]">
                        @foreach($project->publishLogs as $log)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-900/40 transition">
                                <td class="py-2.5 px-4 text-slate-500 dark:text-gray-400">
                                    {{ $log->executed_at ? $log->executed_at->format('d/m H:i:s') : '-' }}
                                </td>
                                <td class="py-2.5 px-4 font-sans font-medium text-slate-900 dark:text-white">
                                    {{ $log->connectedAccount ? $log->connectedAccount->page_name : '-' }}
                                </td>
                                <td class="py-2.5 px-4">
                                    @if($log->platform === 'instagram')
                                        <span class="inline-flex items-center space-x-1 text-pink-500 dark:text-pink-400 font-sans font-semibold">
                                            <i class="fa-brands fa-instagram"></i>
                                            <span>Instagram</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center space-x-1 text-blue-600 dark:text-blue-400 font-sans font-semibold">
                                            <i class="fa-brands fa-facebook"></i>
                                            <span>Facebook</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 uppercase text-[10px] text-slate-600 dark:text-gray-400 font-semibold">
                                    {{ $log->content_type }}
                                </td>
                                <td class="py-2.5 px-4">
                                    @if($log->action_status === 'success')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] uppercase font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">Sukses</span>
                                    @elseif($log->action_status === 'skipped')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] uppercase font-bold bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-400 border border-slate-200 dark:border-gray-700">Skipped</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md text-[10px] uppercase font-bold bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800">Gagal</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-slate-700 dark:text-gray-300 break-words max-w-xs font-sans">
                                    @if($log->media_id)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-mono text-[10px]">ID: {{ $log->media_id }}</span>
                                    @elseif($log->error_message)
                                        <span class="text-rose-600 dark:text-rose-400 text-[11px]">{{ $log->error_message }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection

@section('scripts')
<script>
    function toggleProjectStatus(id, name) {
        showLoading('Mengubah Status...', 'Menyimpan status campaign...');
        fetch(`/projects/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Status Diperbarui', data.message);
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showAlert('error', 'Gagal', data.message);
            }
        })
        .catch(err => {
            showAlert('error', 'Error', err.message);
        });
    }

    function uploadDirectMedia() {
        const input = document.getElementById('inputDirectAddMedia');
        if (input.files.length === 0) return;

        const formData = new FormData();
        Array.from(input.files).forEach(f => formData.append('media_files[]', f));

        showLoading('Mengunggah Media...', 'Menambahkan media ke pool...');
        fetch("{{ route('projects.addMedia', $project->id) }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Berhasil!', data.message);
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showAlert('error', 'Gagal', data.message);
            }
        })
        .catch(err => {
            showAlert('error', 'Error', err.message);
        });
    }

    function runSingleSchedule(id, dateInfo) {
        publishScheduledWithProgress(id, dateInfo, '{{ addslashes($project->name) }}');
    }

    function promptRepublishOption(id, dateInfo, campaignName) {
        Swal.fire({
            title: 'Pilihan Terbitkan Ulang',
            html: `<div class="text-left text-xs text-slate-600 dark:text-gray-300 space-y-3">
                <p>Jadwal tanggal: <strong class="text-slate-900 dark:text-white font-mono">${dateInfo}</strong></p>
                <div class="space-y-2 text-xs">
                    <button type="button" id="btnRepublishDirect" class="w-full text-left p-3 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800/80 hover:border-indigo-500 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition cursor-pointer group">
                        <strong class="text-indigo-700 dark:text-indigo-300 group-hover:text-indigo-900 dark:group-hover:text-white block font-semibold flex items-center space-x-1.5">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span>Terbitkan Sekarang (Live Loading)</span>
                        </strong>
                        <span class="text-[10px] text-slate-500 dark:text-gray-400 block mt-0.5">Langsung publish ulang ke Meta Graph API saat ini juga dengan visual progress lengkap.</span>
                    </button>

                    <button type="button" id="btnResetPendingOnly" class="w-full text-left p-3 rounded-lg bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/80 hover:border-amber-500 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition cursor-pointer group">
                        <strong class="text-amber-700 dark:text-amber-300 group-hover:text-amber-900 dark:group-hover:text-white block font-semibold flex items-center space-x-1.5">
                            <i class="fa-solid fa-rotate-left text-xs"></i>
                            <span>Kembalikan ke Status PENDING</span>
                        </strong>
                        <span class="text-[10px] text-slate-500 dark:text-gray-400 block mt-0.5">Status jadwal dikembalikan ke PENDING sehingga siap terbit otomatis atau manual nanti.</span>
                    </button>
                </div>
            </div>`,
            icon: 'question',
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonColor: '#64748b',
            cancelButtonText: 'Batal',
            didOpen: () => {
                const btnDirect = document.getElementById('btnRepublishDirect');
                const btnPending = document.getElementById('btnResetPendingOnly');

                if (btnDirect) {
                    btnDirect.onclick = () => {
                        Swal.close();
                        publishScheduledWithProgress(id, dateInfo, campaignName, true);
                    };
                }
                if (btnPending) {
                    btnPending.onclick = () => {
                        Swal.close();
                        updateScheduleStatusAjax(id, 'pending');
                    };
                }
            },
            customClass: {
                popup: 'swal2-popup-dark',
                title: 'swal2-title-dark',
                htmlContainer: 'swal2-html-dark'
            }
        });
    }

    function resetToPending(id, dateInfo) {
        promptRepublishOption(id, dateInfo, '{{ addslashes($project->name) }}');
    }

    function openChangeStatusModal(id, currentStatus, dateInfo) {
        Swal.fire({
            title: `Ubah Status Jadwal`,
            html: `<div class="text-left text-xs text-slate-600 dark:text-gray-300 space-y-3">
                <p>Pilih status baru untuk jadwal tanggal <strong class="text-slate-900 dark:text-white">${dateInfo}</strong>:</p>
                <div class="space-y-2 text-xs">
                    <label class="flex items-center space-x-2.5 p-2.5 rounded-lg bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 cursor-pointer hover:border-amber-500 transition text-left">
                        <input type="radio" name="swal_status" value="pending" ${currentStatus === 'pending' ? 'checked' : ''} class="text-amber-500 focus:ring-amber-500">
                        <div>
                            <strong class="text-amber-600 dark:text-amber-400 block font-semibold">PENDING (Siap Terbit / Terbitkan Ulang)</strong>
                            <span class="text-[10px] text-slate-500 dark:text-gray-400">Siap dieksekusi otomatis oleh scheduler atau diterbitkan manual.</span>
                        </div>
                    </label>

                    <label class="flex items-center space-x-2.5 p-2.5 rounded-lg bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 cursor-pointer hover:border-emerald-500 transition text-left">
                        <input type="radio" name="swal_status" value="completed" ${currentStatus === 'completed' ? 'checked' : ''} class="text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <strong class="text-emerald-600 dark:text-emerald-400 block font-semibold">SELESAI (Sudah Terbit)</strong>
                            <span class="text-[10px] text-slate-500 dark:text-gray-400">Menandai konten ini sudah terbit.</span>
                        </div>
                    </label>

                    <label class="flex items-center space-x-2.5 p-2.5 rounded-lg bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 cursor-pointer hover:border-slate-500 transition text-left">
                        <input type="radio" name="swal_status" value="skipped" ${currentStatus === 'skipped' ? 'checked' : ''} class="text-slate-400 focus:ring-slate-500">
                        <div>
                            <strong class="text-slate-800 dark:text-gray-300 block font-semibold">DILEWATI (Skip)</strong>
                            <span class="text-[10px] text-slate-500 dark:text-gray-400">Lewati tanggal ini, scheduler tidak akan mempostingnya.</span>
                        </div>
                    </label>
                </div>
            </div>`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Simpan Perubahan',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const selected = document.querySelector('input[name="swal_status"]:checked');
                if (!selected) {
                    Swal.showValidationMessage('Pilih salah satu status');
                    return false;
                }
                return selected.value;
            },
            customClass: {
                popup: 'swal2-popup-dark',
                title: 'swal2-title-dark',
                htmlContainer: 'swal2-html-dark'
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                updateScheduleStatusAjax(id, result.value);
            }
        });
    }

    function updateScheduleStatusAjax(id, newStatus) {
        showLoading('Memperbarui...', 'Menyimpan perubahan status jadwal...');
        fetch(`/schedules/${id}/status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Berhasil!', data.message);
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showAlert('error', 'Gagal', data.message);
            }
        })
        .catch(err => {
            showAlert('error', 'Error', err.message);
        });
    }
</script>
@endsection
