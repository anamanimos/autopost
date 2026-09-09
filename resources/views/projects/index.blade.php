@extends('layouts.app')

@section('title', 'Project Campaigns')

@section('content')
<div class="space-y-6">

    <!-- Top Action & Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-gray-800 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center space-x-3">
                <div class="p-2 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-lg text-white shadow-md">
                    <i class="fa-solid fa-layer-group text-base"></i>
                </div>
                <span>Project Campaigns</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-gray-400 mt-1">
                Kelola unit automasi posting berulang, jadwal konten, dan multi-target akun Meta.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('projects.create') }}" 
               class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition shadow-sm flex items-center space-x-2">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Buat Project Campaign Baru</span>
            </a>
        </div>
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
        <!-- Campaigns Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($projects as $project)
                @php
                    $hasInactive = $project->hasInactiveAccount();
                    $pendingCount = $project->schedules->count();
                    $furthestDate = $project->schedules->max('target_date');
                    $furthestFormatted = $furthestDate ? \Carbon\Carbon::parse($furthestDate)->translatedFormat('d M Y') : 'Kosong';
                @endphp

                <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 hover:border-slate-300 dark:hover:border-gray-700 transition flex flex-col justify-between overflow-hidden shadow-sm hover:shadow-md">
                    
                    <div class="p-5 space-y-4">
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

                        <!-- Warning Badge jika ada Aset Nonaktif (Soft-Deactivation) -->
                        @if($hasInactive)
                            <div class="bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-700 dark:text-rose-300 px-3 py-2 rounded-lg text-[11px] flex items-center space-x-2">
                                <i class="fa-solid fa-triangle-exclamation text-rose-500 dark:text-rose-400 flex-shrink-0"></i>
                                <span><strong>⚠ Aset Tidak Ditemukan / Nonaktif:</strong> Ada akun target yang terhapus di Meta.</span>
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

                        <!-- Target Accounts & Platform Badges (Bagian 2.1) -->
                        <div class="space-y-1.5 pt-1">
                            <span class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Target Akun ({{ $project->targets->count() }}):</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($project->targets as $target)
                                    @php
                                        $acc = $target->connectedAccount;
                                    @endphp
                                    <div class="inline-flex items-center space-x-1.5 bg-slate-100/90 dark:bg-gray-900/90 border {{ ($acc && !$acc->is_active) ? 'border-rose-300 dark:border-rose-800/80 text-rose-700 dark:text-rose-300' : 'border-slate-200 dark:border-gray-800 text-slate-700 dark:text-gray-300' }} px-2.5 py-1 rounded-md text-[11px]">
                                        <span class="truncate max-w-[130px] font-medium">{{ $acc ? $acc->page_name : 'Unknown' }}</span>
                                        
                                        <!-- Platform Target Icon -->
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
                        <div class="space-y-1.5 pt-1">
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
                    <div class="bg-slate-50/80 dark:bg-gray-900/80 px-5 py-3 border-t border-slate-200/80 dark:border-gray-800/80 flex items-center justify-between text-xs">
                        <div class="text-[11px] text-slate-500 dark:text-gray-400">
                            Antrean Pending: <strong class="text-indigo-600 dark:text-indigo-400 font-bold">{{ $pendingCount }}</strong>
                        </div>
                        <div class="flex items-center space-x-1">
                            <a href="{{ route('projects.show', $project->id) }}" 
                               class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-200/70 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 rounded-lg transition" title="Lihat Detail">
                                <i class="fa-solid fa-eye text-xs"></i>
                            </a>
                            <a href="{{ route('projects.edit', $project->id) }}" 
                               class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-200/70 dark:text-gray-400 dark:hover:text-indigo-400 dark:hover:bg-gray-800 rounded-lg transition" title="Edit Campaign">
                                <i class="fa-solid fa-pen text-xs"></i>
                            </a>
                            <button onclick="deleteProject({{ $project->id }}, '{{ $project->name }}')" 
                                    class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-slate-200/70 dark:text-gray-400 dark:hover:text-rose-400 dark:hover:bg-gray-800 rounded-lg transition" title="Hapus Campaign">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

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
