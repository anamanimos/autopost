@extends('layouts.app')

@section('title', 'Project Campaigns')

@section('content')
<div class="space-y-5" x-data="projectFilter()">

    <!-- Top Action & Filter Bar (Desktop & Mobile Header) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-gray-800 pb-5">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center space-x-3">
                <div class="p-2 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-lg text-white shadow-md">
                    <i class="fa-solid fa-layer-group text-base"></i>
                </div>
                <span>Project Campaigns</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-gray-400 mt-1">
                Kelola unit automasi posting berulang, jadwal konten, dan multi-target akun Meta.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <!-- View Toggle (Desktop & Tablet) -->
            <div class="hidden sm:flex items-center bg-slate-100 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg p-0.5">
                <button @click="viewMode = 'card'" :class="viewMode === 'card' ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-gray-400 hover:text-slate-700 dark:hover:text-gray-200'" class="p-1.5 rounded-md transition text-xs" title="Tampilan Kartu">
                    <i class="fa-solid fa-grip"></i>
                </button>
                <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-gray-400 hover:text-slate-700 dark:hover:text-gray-200'" class="p-1.5 rounded-md transition text-xs" title="Tampilan Daftar">
                    <i class="fa-solid fa-list"></i>
                </button>
            </div>

            <!-- Desktop Filter Toggle Button -->
            <button @click="showFilterPanel = !showFilterPanel" :class="hasActiveFilters ? 'bg-indigo-50 dark:bg-indigo-950/60 border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300' : 'bg-slate-50 dark:bg-gray-800 border-slate-200 dark:border-gray-700 text-slate-600 dark:text-gray-400 hover:text-slate-800 dark:hover:text-gray-200'" class="hidden sm:flex px-3 py-2 text-xs font-semibold rounded-lg border transition items-center space-x-1.5">
                <i class="fa-solid fa-filter text-[10px]"></i>
                <span>Filter</span>
                <span x-show="activeFilterCount > 0" x-text="activeFilterCount" class="ml-1 px-1.5 py-0.5 bg-indigo-600 text-white text-[9px] font-bold rounded-full leading-none"></span>
            </button>

            <!-- Create Campaign Button -->
            <a href="{{ route('projects.create') }}" 
               class="w-full sm:w-auto justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition shadow-sm flex items-center space-x-2">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Buat Campaign</span>
            </a>
        </div>
    </div>

    <!-- Desktop Filter Panel (collapsible) -->
    <div x-show="showFilterPanel" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="hidden sm:block bg-slate-50/80 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-800 rounded-xl p-4 space-y-4">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700 dark:text-gray-300 uppercase tracking-wider">Filter Campaign</span>
            <button @click="clearAllFilters()" x-show="hasActiveFilters" class="text-[11px] text-rose-600 dark:text-rose-400 hover:underline font-medium">
                <i class="fa-solid fa-xmark mr-0.5"></i> Hapus Semua Filter
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Filter: Akun Target -->
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Akun Target</label>
                <div class="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto pr-1">
                    <template x-for="opt in accountOptions" :key="opt.value">
                        <button type="button" @click="toggleFilter('accounts', opt.value)" :class="filters.accounts.includes(opt.value) ? 'bg-indigo-600 text-white border-indigo-600 dark:bg-indigo-500 dark:border-indigo-500' : 'bg-white dark:bg-gray-800 text-slate-600 dark:text-gray-300 border-slate-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-600'" class="px-2.5 py-1.5 text-[11px] font-semibold rounded-lg border transition flex items-center space-x-1.5">
                            <i class="fa-regular fa-user text-[10px]"></i>
                            <span x-text="opt.label" class="truncate max-w-[120px]"></span>
                        </button>
                    </template>
                    @if($accounts->isEmpty())
                        <span class="text-[11px] text-slate-400 dark:text-gray-500 italic">Belum ada akun</span>
                    @endif
                </div>
            </div>

            <!-- Filter: Platform / Sosmed -->
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Platform Target</label>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="opt in platformOptions" :key="opt.value">
                        <button @click="toggleFilter('platforms', opt.value)" :class="filters.platforms.includes(opt.value) ? 'bg-indigo-600 text-white border-indigo-600 dark:bg-indigo-500 dark:border-indigo-500' : 'bg-white dark:bg-gray-800 text-slate-600 dark:text-gray-300 border-slate-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-600'" class="px-2.5 py-1.5 text-[11px] font-semibold rounded-lg border transition flex items-center space-x-1.5">
                            <i :class="opt.icon" class="text-[10px]"></i>
                            <span x-text="opt.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Filter: Status -->
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Status</label>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="opt in statusOptions" :key="opt.value">
                        <button @click="toggleFilter('statuses', opt.value)" :class="filters.statuses.includes(opt.value) ? 'bg-indigo-600 text-white border-indigo-600 dark:bg-indigo-500 dark:border-indigo-500' : 'bg-white dark:bg-gray-800 text-slate-600 dark:text-gray-300 border-slate-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-600'" class="px-2.5 py-1.5 text-[11px] font-semibold rounded-lg border transition flex items-center space-x-1.5">
                            <span :class="opt.value === 'active' ? 'w-1.5 h-1.5 rounded-full bg-emerald-500' : 'w-1.5 h-1.5 rounded-full bg-slate-400'"></span>
                            <span x-text="opt.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Filter: Content Type -->
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Tipe Konten</label>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="opt in contentTypeOptions" :key="opt.value">
                        <button @click="toggleFilter('contentTypes', opt.value)" :class="filters.contentTypes.includes(opt.value) ? 'bg-indigo-600 text-white border-indigo-600 dark:bg-indigo-500 dark:border-indigo-500' : 'bg-white dark:bg-gray-800 text-slate-600 dark:text-gray-300 border-slate-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-600'" class="px-2.5 py-1.5 text-[11px] font-semibold rounded-lg border transition flex items-center space-x-1.5">
                            <i :class="opt.icon" class="text-[10px]"></i>
                            <span x-text="opt.label"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Filter Tags (Desktop & Mobile) -->
    <div x-show="hasActiveFilters" x-transition class="flex flex-wrap items-center gap-2">
        <span class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Filter Aktif:</span>
        <template x-for="tag in activeFilterTags" :key="tag.key">
            <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 rounded-lg text-[11px] font-medium">
                <i :class="tag.icon" class="text-[9px]"></i>
                <span x-text="tag.label"></span>
                <button @click="removeFilter(tag.group, tag.value)" class="ml-0.5 text-indigo-400 hover:text-indigo-700 dark:text-indigo-500 dark:hover:text-indigo-200 transition">
                    <i class="fa-solid fa-xmark text-[9px]"></i>
                </button>
            </span>
        </template>
        <button @click="clearAllFilters()" class="text-[11px] text-rose-600 dark:text-rose-400 hover:underline font-semibold ml-1">
            Reset
        </button>
    </div>

    <!-- Results Count -->
    <div x-show="hasActiveFilters || searchQuery" class="text-[11px] text-slate-500 dark:text-gray-400 flex items-center justify-between">
        <span>Menampilkan <strong class="text-slate-800 dark:text-gray-200" x-text="filteredCount"></strong> dari <strong>{{ $projects->count() }}</strong> campaign</span>
        <span x-show="searchQuery" class="italic text-slate-400">Pencarian: "<span x-text="searchQuery"></span>"</span>
    </div>

    @if($projects->isEmpty())
        <div class="text-center py-16 bg-slate-100/50 dark:bg-gray-900/40 rounded-xl border border-dashed border-slate-300 dark:border-gray-800 space-y-4">
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center mx-auto text-xl">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Belum Ada Project Campaign</h3>
                <p class="text-xs text-slate-500 dark:text-gray-400 max-w-md mx-auto">
                    Buat campaign pertama Anda untuk menjadwalkan Story atau Post secara kontinu atau terjadwal ke Instagram & Facebook.
                </p>
            </div>
            <div class="pt-2">
                <a href="{{ route('projects.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition inline-flex items-center space-x-2 shadow-sm">
                    <i class="fa-solid fa-plus"></i>
                    <span>Mulai Buat Campaign</span>
                </a>
            </div>
        </div>
    @else

        <!-- ========== CARD VIEW (Desktop Grid & Always on Mobile) ========== -->
        <div x-show="viewMode === 'card' || isMobile" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5">
            @foreach($projects as $project)
                @php
                    $hasInactive = $project->hasInactiveAccount();
                    $pendingCount = $project->schedules->count();
                    $furthestDate = $project->schedules->max('target_date');
                    $furthestFormatted = $furthestDate ? \Carbon\Carbon::parse($furthestDate)->translatedFormat('d M Y') : 'Kosong';
                    $platformList = $project->targets->pluck('platform_target')->unique()->toArray();
                    $accountList = $project->targets->pluck('connected_account_id')->unique()->toArray();
                @endphp

                <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 hover:border-slate-300 dark:hover:border-gray-700 transition flex flex-col justify-between overflow-hidden shadow-sm hover:shadow-md"
                     x-show="isVisible('{{ $project->status }}', '{{ $project->content_type }}', {{ json_encode($platformList) }}, {{ json_encode($accountList) }}, '{{ addslashes($project->name) }}')"
                     x-transition>
                    
                    <div class="p-4 sm:p-5 space-y-3.5">
                        <!-- Top Badges -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center space-x-1.5 flex-wrap">
                                <!-- Content Type Badge -->
                                @if($project->content_type === 'story')
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-pink-50 dark:bg-pink-900/70 border border-pink-200 dark:border-pink-700/60 text-pink-700 dark:text-pink-300 flex items-center space-x-1">
                                        <i class="fa-solid fa-circle-notch text-[9px]"></i>
                                        <span>Story</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 flex items-center space-x-1">
                                        <i class="fa-solid fa-square-rss text-[9px]"></i>
                                        <span>Feed Post</span>
                                    </span>
                                @endif

                                <!-- Repeat Type Badge -->
                                @if($project->repeat_type === 'continuous')
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300">
                                        ♾️ Kontinu
                                    </span>
                                @elseif($project->repeat_type === 'once')
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300">
                                        🎯 1x Post
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-purple-50 dark:bg-purple-950/80 border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300">
                                        📅 s/d {{ $project->end_date ? $project->end_date->format('d/m') : '-' }}
                                    </span>
                                @endif
                            </div>

                            <!-- Status Badge -->
                            <button onclick="toggleProjectStatus({{ $project->id }}, '{{ $project->name }}')" 
                                    class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md transition flex items-center space-x-1 {{ $project->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/60' : 'bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-400 border border-slate-200 dark:border-gray-700 hover:bg-slate-200 dark:hover:bg-gray-700' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $project->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400 dark:bg-gray-500' }}"></span>
                                <span>{{ $project->status === 'active' ? 'Aktif' : 'Dijeda' }}</span>
                            </button>
                        </div>

                        <!-- Warning Badge -->
                        @if($hasInactive)
                            <div class="bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-700 dark:text-rose-300 px-3 py-2 rounded-lg text-[11px] flex items-center space-x-2">
                                <i class="fa-solid fa-triangle-exclamation text-rose-500 dark:text-rose-400 flex-shrink-0"></i>
                                <span><strong>⚠ Aset Nonaktif:</strong> Ada akun target yang terhapus di Meta.</span>
                            </div>
                        @endif

                        <!-- Title & Time -->
                        <div>
                            <a href="{{ route('projects.show', $project->id) }}" class="text-base font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition block leading-snug">
                                {{ $project->name }}
                            </a>
                            <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-gray-400 mt-1">
                                <i class="fa-regular fa-clock text-indigo-600 dark:text-indigo-400"></i>
                                <span>Tayang: <strong class="text-slate-800 dark:text-slate-200">{{ $project->target_time }} WIB</strong></span>
                            </div>
                        </div>

                        <!-- Target Accounts -->
                        <div class="space-y-1.5 pt-0.5">
                            <span class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Target Akun ({{ $project->targets->count() }}):</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($project->targets as $target)
                                    @php $acc = $target->connectedAccount; @endphp
                                    <div class="inline-flex items-center space-x-1.5 bg-slate-100/90 dark:bg-gray-900/90 border {{ ($acc && !$acc->is_active) ? 'border-rose-300 dark:border-rose-800/80 text-rose-700 dark:text-rose-300' : 'border-slate-200 dark:border-gray-800 text-slate-700 dark:text-gray-300' }} px-2 py-0.5 rounded-md text-[11px]">
                                        <span class="truncate max-w-[130px] font-medium">{{ $acc ? $acc->page_name : 'Unknown' }}</span>
                                        @if($target->platform_target === 'both')
                                            <span class="inline-flex items-center space-x-0.5 text-[9px] text-indigo-600 dark:text-indigo-400 font-bold">
                                                <i class="fa-brands fa-facebook"></i>
                                                <i class="fa-brands fa-instagram"></i>
                                            </span>
                                        @elseif($target->platform_target === 'instagram_only')
                                            <span class="text-[9px] text-pink-500 dark:text-pink-400 font-bold" title="Instagram Saja">
                                                <i class="fa-brands fa-instagram"></i>
                                            </span>
                                        @elseif($target->platform_target === 'facebook_only')
                                            <span class="text-[9px] text-blue-600 dark:text-blue-400 font-bold" title="Facebook Saja">
                                                <i class="fa-brands fa-facebook"></i>
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Media Pool Thumbnails -->
                        <div class="space-y-1.5 pt-0.5">
                            <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-gray-400">
                                <span>Media Pool: <strong class="text-slate-800 dark:text-gray-300">{{ $project->mediaFiles->count() }} file</strong></span>
                                <span>Jadwal s/d: <strong class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $furthestFormatted }}</strong></span>
                            </div>
                            <div class="flex items-center space-x-2 overflow-x-auto py-1">
                                @foreach($project->mediaFiles->take(5) as $media)
                                    <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-gray-900 border border-slate-200 dark:border-gray-800 overflow-hidden flex-shrink-0 cursor-pointer hover:scale-105 transition"
                                         onclick="openLightboxDirect('{{ $media->url }}', {{ $media->is_video ? 'true' : 'false' }}, '{{ $media->original_name }}')">
                                        @if($media->is_video)
                                            <div class="relative w-full h-full bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xs">
                                                <i class="fa-solid fa-video"></i>
                                            </div>
                                        @else
                                            <img src="{{ $media->url }}" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                @endforeach
                                @if($project->mediaFiles->count() > 5)
                                    <a href="{{ route('projects.show', $project->id) }}" class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-gray-900 border border-slate-200 dark:border-gray-800 flex items-center justify-center text-[10px] text-slate-600 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white transition flex-shrink-0">
                                        +{{ $project->mediaFiles->count() - 5 }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Bar -->
                    <div class="bg-slate-50/80 dark:bg-gray-900/80 px-4 sm:px-5 py-3 border-t border-slate-200/80 dark:border-gray-800/80 flex items-center justify-between text-xs">
                        <div class="text-[11px] text-slate-500 dark:text-gray-400">
                            Antrean Pending: <strong class="text-indigo-600 dark:text-indigo-400 font-bold">{{ $pendingCount }}</strong>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <a href="{{ route('projects.show', $project->id) }}" 
                               class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-200/70 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 rounded-lg transition min-w-[36px] min-h-[36px] flex items-center justify-center" title="Lihat Detail">
                                <i class="fa-solid fa-eye text-xs"></i>
                            </a>
                            <a href="{{ route('projects.edit', $project->id) }}" 
                               class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-slate-200/70 dark:text-gray-400 dark:hover:text-indigo-400 dark:hover:bg-gray-800 rounded-lg transition min-w-[36px] min-h-[36px] flex items-center justify-center" title="Edit Campaign">
                                <i class="fa-solid fa-pen text-xs"></i>
                            </a>
                            <button onclick="deleteProject({{ $project->id }}, '{{ $project->name }}')" 
                                    class="p-2 text-slate-500 hover:text-rose-600 hover:bg-slate-200/70 dark:text-gray-400 dark:hover:text-rose-400 dark:hover:bg-gray-800 rounded-lg transition min-w-[36px] min-h-[36px] flex items-center justify-center" title="Hapus Campaign">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        <!-- ========== LIST VIEW (Desktop Only) ========== -->
        <div x-show="viewMode === 'list' && !isMobile" x-transition class="hidden md:block card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-gray-900/80 border-b border-slate-200 dark:border-gray-800">
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Media</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Nama Campaign</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Tipe</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Platform</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Jam Tayang</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Pending</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-800/80">
                        @foreach($projects as $project)
                            @php
                                $pendingCount = $project->schedules->count();
                                $platformList = $project->targets->pluck('platform_target')->unique()->toArray();
                                $accountList = $project->targets->pluck('connected_account_id')->unique()->toArray();
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-gray-900/40 transition"
                                x-show="isVisible('{{ $project->status }}', '{{ $project->content_type }}', {{ json_encode($platformList) }}, {{ json_encode($accountList) }}, '{{ addslashes($project->name) }}')">
                                <!-- Thumbnail -->
                                <td class="px-4 py-3">
                                    @if($project->mediaFiles->first())
                                        @php $firstMedia = $project->mediaFiles->first(); @endphp
                                        @if($firstMedia->is_video)
                                            <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-gray-900 border border-slate-200 dark:border-gray-800 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xs">
                                                <i class="fa-solid fa-video"></i>
                                            </div>
                                        @else
                                            <img src="{{ $firstMedia->url }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 dark:border-gray-800">
                                        @endif
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 flex items-center justify-center text-slate-400 dark:text-gray-500 text-xs">
                                            <i class="fa-solid fa-image"></i>
                                        </div>
                                    @endif
                                </td>
                                <!-- Name -->
                                <td class="px-4 py-3">
                                    <a href="{{ route('projects.show', $project->id) }}" class="text-sm font-semibold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                        {{ $project->name }}
                                    </a>
                                    <div class="text-[10px] text-slate-400 dark:text-gray-500 mt-0.5">
                                        {{ $project->mediaFiles->count() }} media · {{ $project->targets->count() }} akun
                                    </div>
                                </td>
                                <!-- Content Type -->
                                <td class="px-4 py-3">
                                    @if($project->content_type === 'story')
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-pink-50 dark:bg-pink-900/70 border border-pink-200 dark:border-pink-700/60 text-pink-700 dark:text-pink-300">Story</span>
                                    @else
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300">Post</span>
                                    @endif
                                </td>
                                <!-- Platform -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-1.5">
                                        @php
                                            $platforms = $project->targets->pluck('platform_target')->unique();
                                            $hasIG = $platforms->contains('both') || $platforms->contains('instagram_only');
                                            $hasFB = $platforms->contains('both') || $platforms->contains('facebook_only');
                                        @endphp
                                        @if($hasIG)
                                            <span class="text-sm text-pink-500 dark:text-pink-400"><i class="fa-brands fa-instagram"></i></span>
                                        @endif
                                        @if($hasFB)
                                            <span class="text-sm text-blue-600 dark:text-blue-400"><i class="fa-brands fa-facebook"></i></span>
                                        @endif
                                    </div>
                                </td>
                                <!-- Time -->
                                <td class="px-4 py-3 text-xs font-semibold text-slate-700 dark:text-gray-300">
                                    {{ $project->target_time }} <span class="text-slate-400 dark:text-gray-500 font-normal">WIB</span>
                                </td>
                                <!-- Status -->
                                <td class="px-4 py-3">
                                    <button onclick="toggleProjectStatus({{ $project->id }}, '{{ $project->name }}')"
                                            class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md transition flex items-center space-x-1 {{ $project->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/60' : 'bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-400 border border-slate-200 dark:border-gray-700 hover:bg-slate-200 dark:hover:bg-gray-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $project->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400 dark:bg-gray-500' }}"></span>
                                        <span>{{ $project->status === 'active' ? 'Aktif' : 'Dijeda' }}</span>
                                    </button>
                                </td>
                                <!-- Pending -->
                                <td class="px-4 py-3 text-xs">
                                    <strong class="text-indigo-600 dark:text-indigo-400">{{ $pendingCount }}</strong>
                                </td>
                                <!-- Actions -->
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end space-x-1">
                                        <a href="{{ route('projects.show', $project->id) }}" class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-200/70 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 rounded-lg transition" title="Lihat Detail">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </a>
                                        <a href="{{ route('projects.edit', $project->id) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-200/70 dark:text-gray-400 dark:hover:text-indigo-400 dark:hover:bg-gray-800 rounded-lg transition" title="Edit">
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </a>
                                        <button onclick="deleteProject({{ $project->id }}, '{{ $project->name }}')" class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-slate-200/70 dark:text-gray-400 dark:hover:text-rose-400 dark:hover:bg-gray-800 rounded-lg transition" title="Hapus">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty Filter Result -->
        <div x-show="(hasActiveFilters || searchQuery) && filteredCount === 0" x-transition class="text-center py-12 space-y-3">
            <div class="w-12 h-12 bg-slate-100 dark:bg-gray-800 text-slate-400 dark:text-gray-500 rounded-xl flex items-center justify-center mx-auto text-xl">
                <i class="fa-solid fa-filter-circle-xmark"></i>
            </div>
            <div class="space-y-1">
                <h3 class="text-sm font-bold text-slate-700 dark:text-gray-300">Tidak Ada Campaign Sesuai Kriteria</h3>
                <p class="text-xs text-slate-500 dark:text-gray-400">Coba ubah kata kunci atau hapus filter untuk menampilkan campaign.</p>
            </div>
            <button @click="clearAllFilters(); searchQuery = ''; updateFilteredCount()" class="px-3 py-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                Hapus Semua Filter & Pencarian
            </button>
        </div>

    @endif

    <!-- ================================================================= -->
    <!-- MOBILE FLOATING ACTION BUTTONS (SEARCH & FILTER)                   -->
    <!-- Positioned above Mobile Bottom Navbar (bottom-20 right-4)         -->
    <!-- ================================================================= -->
    <div class="fixed bottom-20 right-4 z-40 lg:hidden flex items-center space-x-2.5" x-show="!searchOpen">
        <!-- Floating Filter Icon (Opens Bottom Sheet Modal) -->
        <button type="button" @click="mobileFilterOpen = true" 
                class="w-12 h-12 rounded-full bg-slate-900/90 dark:bg-slate-800/95 border border-slate-700/80 text-white shadow-xl flex items-center justify-center transition-all duration-200 active:scale-90 relative"
                title="Buka Filter">
            <i class="fa-solid fa-filter text-sm text-indigo-400"></i>
            <span x-show="activeFilterCount > 0" x-text="activeFilterCount" 
                  class="absolute -top-1 -right-1 w-5 h-5 bg-indigo-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white dark:border-gray-900 shadow"></span>
        </button>

        <!-- Floating Search Icon (Expands to 100% Bar) -->
        <button type="button" @click="searchOpen = true; $nextTick(() => $refs.searchInput.focus())" 
                class="w-12 h-12 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 text-white shadow-xl flex items-center justify-center transition-all duration-300 active:scale-90"
                title="Cari Campaign">
            <i class="fa-solid fa-magnifying-glass text-sm"></i>
        </button>
    </div>

    <!-- EXPANDABLE 100% SEARCH BAR (Mobile Full Width on Click) -->
    <div x-show="searchOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-6 scale-95"
         class="fixed bottom-20 inset-x-3 z-40 lg:hidden bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl p-2 rounded-2xl shadow-2xl border border-indigo-500/50 flex items-center space-x-2">
        <div class="p-2 text-indigo-600 dark:text-indigo-400">
            <i class="fa-solid fa-magnifying-glass text-sm"></i>
        </div>
        <input type="text" x-ref="searchInput" x-model="searchQuery" @input="updateFilteredCount()" 
               placeholder="Ketik untuk mencari campaign..." 
               class="flex-1 bg-transparent text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-500 focus:outline-none py-1.5 font-medium">
        <button type="button" x-show="searchQuery" @click="searchQuery = ''; updateFilteredCount()" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-gray-300 text-xs">
            <i class="fa-solid fa-circle-xmark"></i>
        </button>
        <button type="button" @click="searchOpen = false" class="px-3 py-1.5 bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 rounded-lg text-xs font-semibold hover:bg-slate-200 dark:hover:bg-gray-700">
            Tutup
        </button>
    </div>

    <!-- MOBILE FILTER BOTTOM SHEET MODAL -->
    <div x-show="mobileFilterOpen" class="fixed inset-0 z-50 lg:hidden flex items-end justify-center" style="display: none;">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" @click="mobileFilterOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <!-- Dialog Sheet -->
        <div class="relative w-full max-h-[85vh] bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-gray-800 rounded-t-2xl shadow-2xl overflow-y-auto p-5 space-y-5 z-10"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <!-- Drag Handle Bar -->
            <div class="w-12 h-1.5 rounded-full bg-slate-300 dark:bg-gray-700 mx-auto -mt-1"></div>

            <div class="flex items-center justify-between border-b border-slate-200 dark:border-gray-800 pb-3">
                <div class="flex items-center space-x-2">
                    <div class="p-1.5 bg-indigo-50 dark:bg-indigo-950/80 rounded-lg text-indigo-600 dark:text-indigo-400">
                        <i class="fa-solid fa-filter text-xs"></i>
                    </div>
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">Filter Campaign</h3>
                </div>
                <button type="button" @click="mobileFilterOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white text-base">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Filter 1: Akun Target -->
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Akun Target</label>
                <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto pr-1">
                    <template x-for="opt in accountOptions" :key="opt.value">
                        <button type="button" @click="toggleFilter('accounts', opt.value)" 
                                :class="filters.accounts.includes(opt.value) ? 'bg-indigo-600 text-white border-indigo-600 dark:bg-indigo-500 dark:border-indigo-500' : 'bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 border-slate-200 dark:border-gray-700'" 
                                class="px-3 py-2 text-xs font-semibold rounded-lg border transition flex items-center space-x-1.5">
                            <i class="fa-regular fa-user text-[10px]"></i>
                            <span x-text="opt.label"></span>
                        </button>
                    </template>
                    @if($accounts->isEmpty())
                        <span class="text-xs text-slate-400 italic">Belum ada akun</span>
                    @endif
                </div>
            </div>

            <!-- Filter 2: Platform Target -->
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Platform Target</label>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="opt in platformOptions" :key="opt.value">
                        <button type="button" @click="toggleFilter('platforms', opt.value)" 
                                :class="filters.platforms.includes(opt.value) ? 'bg-indigo-600 text-white border-indigo-600 dark:bg-indigo-500 dark:border-indigo-500' : 'bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 border-slate-200 dark:border-gray-700'" 
                                class="px-3 py-2 text-xs font-semibold rounded-lg border transition flex items-center space-x-1.5">
                            <i :class="opt.icon" class="text-[10px]"></i>
                            <span x-text="opt.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Filter 3: Status -->
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Status</label>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="opt in statusOptions" :key="opt.value">
                        <button type="button" @click="toggleFilter('statuses', opt.value)" 
                                :class="filters.statuses.includes(opt.value) ? 'bg-indigo-600 text-white border-indigo-600 dark:bg-indigo-500 dark:border-indigo-500' : 'bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 border-slate-200 dark:border-gray-700'" 
                                class="px-3 py-2 text-xs font-semibold rounded-lg border transition flex items-center space-x-1.5">
                            <span :class="opt.value === 'active' ? 'w-2 h-2 rounded-full bg-emerald-500' : 'w-2 h-2 rounded-full bg-slate-400'"></span>
                            <span x-text="opt.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Filter 4: Tipe Konten -->
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Tipe Konten</label>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="opt in contentTypeOptions" :key="opt.value">
                        <button type="button" @click="toggleFilter('contentTypes', opt.value)" 
                                :class="filters.contentTypes.includes(opt.value) ? 'bg-indigo-600 text-white border-indigo-600 dark:bg-indigo-500 dark:border-indigo-500' : 'bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 border-slate-200 dark:border-gray-700'" 
                                class="px-3 py-2 text-xs font-semibold rounded-lg border transition flex items-center space-x-1.5">
                            <i :class="opt.icon" class="text-[10px]"></i>
                            <span x-text="opt.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Action Buttons Footer -->
            <div class="pt-4 border-t border-slate-200 dark:border-gray-800 flex items-center space-x-2">
                <button type="button" @click="clearAllFilters()" 
                        class="flex-1 py-2.5 px-4 rounded-xl border border-slate-300 dark:border-gray-700 text-slate-700 dark:text-gray-300 font-semibold text-xs hover:bg-slate-100 dark:hover:bg-gray-800 transition text-center">
                    Hapus Semua
                </button>
                <button type="button" @click="mobileFilterOpen = false" 
                        class="flex-1 py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md transition text-center">
                    Terapkan (<span x-text="filteredCount"></span>)
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')

<script>
    function projectFilter() {
        const savedView = localStorage.getItem('projectViewMode') || 'card';
        return {
            viewMode: savedView,
            isMobile: window.innerWidth < 768,
            showFilterPanel: false,
            mobileFilterOpen: false,
            searchOpen: false,
            searchQuery: '',
            filters: {
                accounts: [],
                platforms: [],
                statuses: [],
                contentTypes: [],
            },

            accountOptions: [
                @foreach($accounts as $acc)
                {
                    value: {{ $acc->id }},
                    label: '{{ addslashes($acc->page_name) }}',
                },
                @endforeach
            ],
            platformOptions: [
                { value: 'both', label: 'FB + IG', icon: 'fa-solid fa-globe' },
                { value: 'instagram_only', label: 'Instagram', icon: 'fa-brands fa-instagram' },
                { value: 'facebook_only', label: 'Facebook', icon: 'fa-brands fa-facebook' },
            ],
            statusOptions: [
                { value: 'active', label: 'Aktif' },
                { value: 'paused', label: 'Dijeda' },
            ],
            contentTypeOptions: [
                { value: 'story', label: 'Story', icon: 'fa-solid fa-circle-notch' },
                { value: 'post', label: 'Feed Post', icon: 'fa-solid fa-square-rss' },
            ],

            toggleFilter(group, value) {
                const idx = this.filters[group].indexOf(value);
                if (idx > -1) {
                    this.filters[group].splice(idx, 1);
                } else {
                    this.filters[group].push(value);
                }
                this.updateFilteredCount();
            },

            removeFilter(group, value) {
                const idx = this.filters[group].indexOf(value);
                if (idx > -1) {
                    this.filters[group].splice(idx, 1);
                }
                this.updateFilteredCount();
            },

            clearAllFilters() {
                this.filters.accounts = [];
                this.filters.platforms = [];
                this.filters.statuses = [];
                this.filters.contentTypes = [];
                this.updateFilteredCount();
            },

            get hasActiveFilters() {
                return this.filters.accounts.length > 0 || this.filters.platforms.length > 0 || this.filters.statuses.length > 0 || this.filters.contentTypes.length > 0;
            },

            get activeFilterCount() {
                return this.filters.accounts.length + this.filters.platforms.length + this.filters.statuses.length + this.filters.contentTypes.length;
            },

            get activeFilterTags() {
                const tags = [];
                this.filters.accounts.forEach(v => {
                    const opt = this.accountOptions.find(o => o.value == v);
                    if (opt) tags.push({ key: 'a_' + v, group: 'accounts', value: v, label: opt.label, icon: 'fa-regular fa-user' });
                });
                this.filters.platforms.forEach(v => {
                    const opt = this.platformOptions.find(o => o.value === v);
                    if (opt) tags.push({ key: 'p_' + v, group: 'platforms', value: v, label: opt.label, icon: opt.icon });
                });
                this.filters.statuses.forEach(v => {
                    const opt = this.statusOptions.find(o => o.value === v);
                    if (opt) tags.push({ key: 's_' + v, group: 'statuses', value: v, label: opt.label, icon: v === 'active' ? 'fa-solid fa-circle-check' : 'fa-solid fa-pause' });
                });
                this.filters.contentTypes.forEach(v => {
                    const opt = this.contentTypeOptions.find(o => o.value === v);
                    if (opt) tags.push({ key: 'c_' + v, group: 'contentTypes', value: v, label: opt.label, icon: opt.icon });
                });
                return tags;
            },

            filteredCount: {{ $projects->count() }},

            updateFilteredCount() {
                this.$nextTick(() => {
                    let count = 0;
                    @foreach($projects as $project)
                        @php 
                            $platformList = $project->targets->pluck('platform_target')->unique()->toArray(); 
                            $accountList = $project->targets->pluck('connected_account_id')->unique()->toArray();
                        @endphp
                        if (this.isVisible('{{ $project->status }}', '{{ $project->content_type }}', {!! json_encode($platformList) !!}, {!! json_encode($accountList) !!}, '{{ addslashes($project->name) }}')) count++;
                    @endforeach
                    this.filteredCount = count;
                });
            },

            isVisible(status, contentType, platforms, accountIds, name) {
                // Search query filter
                if (this.searchQuery && this.searchQuery.trim() !== '') {
                    const q = this.searchQuery.trim().toLowerCase();
                    if (!name.toLowerCase().includes(q)) return false;
                }
                // Account filter
                if (this.filters.accounts.length > 0) {
                    const hasMatch = accountIds && accountIds.map(String).some(id => this.filters.accounts.map(String).includes(id));
                    if (!hasMatch) return false;
                }
                // Status filter
                if (this.filters.statuses.length > 0) {
                    if (!this.filters.statuses.includes(status)) return false;
                }
                // Content Type filter
                if (this.filters.contentTypes.length > 0) {
                    if (!this.filters.contentTypes.includes(contentType)) return false;
                }
                // Platform filter
                if (this.filters.platforms.length > 0) {
                    const hasMatch = platforms.some(p => this.filters.platforms.includes(p));
                    if (!hasMatch) return false;
                }
                return true;
            },

            init() {
                this.$watch('viewMode', (val) => localStorage.setItem('projectViewMode', val));
                window.addEventListener('resize', () => {
                    this.isMobile = window.innerWidth < 768;
                });
            }
        };
    }

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

    function deleteProject(id, name) {
        Swal.fire({
            title: `Hapus Project '${name}'?`,
            text: 'Seluruh antrean jadwal dan relasi media campaign ini akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#374151',
            confirmButtonText: 'Ya, Hapus Sekarang',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'swal2-popup-dark',
                title: 'swal2-title-dark',
                htmlContainer: 'swal2-html-dark'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Menghapus Project...', 'Mohon tunggu...');
                fetch(`/projects/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Dihapus!', data.message);
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
