@extends('layouts.app')

@section('title', 'Antrean Posting & Monitoring')

@section('content')
<div class="space-y-6">

    <!-- Top Action & Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-gray-800 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center space-x-3">
                <div class="p-2 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-lg text-white shadow-md">
                    <i class="fa-solid fa-calendar-check text-base"></i>
                </div>
                <span>Antrean Posting & Monitoring</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-gray-400 mt-1">
                Pantau antrean jadwal posting, status eksekusi per platform, dan jalankan publish on-demand.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            @if($stats['failed'] > 0)
                <button onclick="triggerRetryFailed()" 
                        class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 dark:bg-rose-950/80 dark:hover:bg-rose-900/80 dark:border-rose-800 dark:text-rose-300 transition flex items-center space-x-1.5 shadow-sm">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Retry Jadwal Gagal ({{ $stats['failed'] }})</span>
                </button>
            @endif

            <button onclick="triggerPublishNow()" 
                    class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition flex items-center space-x-1.5">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Publish Antrean Sekarang</span>
            </button>
        </div>
    </div>

    <!-- Stats Cards Summary Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <a href="{{ route('schedules.index') }}" class="card-dark rounded-xl p-4 border border-slate-200/90 dark:border-gray-800 hover:border-slate-300 dark:hover:border-gray-700 transition shadow-sm">
            <span class="text-[10px] font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider block">Total Antrean</span>
            <span class="text-2xl font-bold text-slate-900 dark:text-white mt-1 block">{{ $stats['total'] }}</span>
        </a>

        <a href="{{ route('schedules.index', ['status' => 'pending']) }}" class="card-dark rounded-xl p-4 border border-slate-200/90 dark:border-gray-800 hover:border-slate-300 dark:hover:border-gray-700 transition shadow-sm">
            <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider block">Menunggu (Pending)</span>
            <span class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1 block">{{ $stats['pending'] }}</span>
        </a>

        <a href="{{ route('schedules.index', ['status' => 'completed']) }}" class="card-dark rounded-xl p-4 border border-slate-200/90 dark:border-gray-800 hover:border-slate-300 dark:hover:border-gray-700 transition shadow-sm">
            <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Berhasil (Completed)</span>
            <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 block">{{ $stats['completed'] }}</span>
        </a>

        <a href="{{ route('schedules.index', ['status' => 'failed_all']) }}" class="card-dark rounded-xl p-4 border border-slate-200/90 dark:border-gray-800 hover:border-slate-300 dark:hover:border-gray-700 transition shadow-sm">
            <span class="text-[10px] font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider block">Gagal / Parsial</span>
            <span class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1 block">{{ $stats['failed'] }}</span>
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="card-dark rounded-xl p-4 border border-slate-200/90 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs shadow-sm">
        <form action="{{ route('schedules.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
            <div class="flex items-center space-x-2">
                <span class="text-slate-600 dark:text-gray-400 font-medium">Status:</span>
                <select name="status" onchange="this.form.submit()" class="bg-white dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-1.5 text-xs text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="failed_all" {{ $statusFilter === 'failed_all' ? 'selected' : '' }}>Gagal / Parsial</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <span class="text-slate-600 dark:text-gray-400 font-medium">Project:</span>
                <select name="project_id" onchange="this.form.submit()" class="bg-white dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-1.5 text-xs text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500">
                    <option value="">Semua Campaign</option>
                    @foreach($projects as $proj)
                        <option value="{{ $proj->id }}" {{ $projectFilter == $proj->id ? 'selected' : '' }}>{{ $proj->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($statusFilter !== 'all' || $projectFilter)
                <a href="{{ route('schedules.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Reset Filter</a>
            @endif
        </form>

        <span class="text-slate-500 dark:text-gray-400 text-[11px]">Menampilkan {{ $schedules->count() }} dari total {{ $schedules->total() }} jadwal</span>
    </div>

    <!-- Schedules Table -->
    <div class="card-dark rounded-xl border border-slate-200/90 dark:border-gray-800 overflow-hidden shadow-sm">
        @if($schedules->isEmpty())
            <div class="text-center py-12 space-y-2">
                <i class="fa-regular fa-calendar-xmark text-3xl text-slate-400 dark:text-gray-500"></i>
                <p class="text-xs text-slate-500 dark:text-gray-400">Tidak ada jadwal antrean yang cocok dengan filter saat ini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-gray-300">
                    <thead class="bg-slate-100/90 dark:bg-gray-900/80 text-slate-600 dark:text-gray-400 uppercase font-semibold text-[10px] tracking-wider border-b border-slate-200 dark:border-gray-800">
                        <tr>
                            <th class="py-3.5 px-4">Media</th>
                            <th class="py-3.5 px-4">Campaign & Tipe</th>
                            <th class="py-3.5 px-4">Jadwal Tayang</th>
                            <th class="py-3.5 px-4">Target Akun</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-gray-800/60">
                        @foreach($schedules as $sch)
                            @php
                                $project = $sch->projectCampaign;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-900/40 transition">
                                <!-- Media Thumbnail -->
                                <td class="py-3 px-4">
                                    <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-gray-900 border border-slate-200 dark:border-gray-800 overflow-hidden flex-shrink-0 cursor-pointer hover:border-indigo-500 transition"
                                         onclick="openLightboxDirect('{{ $sch->media_url }}', false, '{{ $project ? $project->name : 'Jadwal' }}')">
                                        <img src="{{ $sch->media_url }}" class="w-full h-full object-cover">
                                    </div>
                                </td>

                                <!-- Campaign & Tipe -->
                                <td class="py-3 px-4">
                                    @if($project)
                                        <a href="{{ route('projects.show', $project->id) }}" class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition block">
                                            {{ $project->name }}
                                        </a>
                                        <div class="flex items-center space-x-1.5 mt-0.5">
                                            @if($project->content_type === 'story')
                                                <span class="text-[10px] text-pink-600 dark:text-pink-400 font-semibold"><i class="fa-solid fa-circle-notch text-[9px] mr-1"></i>Story</span>
                                            @else
                                                <span class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold"><i class="fa-solid fa-square-rss text-[9px] mr-1"></i>Feed Post</span>
                                            @endif
                                            <span class="text-slate-300 dark:text-gray-600">•</span>
                                            <span class="text-[10px] text-slate-500 dark:text-gray-400 uppercase font-mono">{{ $project->repeat_type }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 dark:text-gray-500 italic">Campaign dihapus</span>
                                    @endif
                                </td>

                                <!-- Jadwal Tayang -->
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-900 dark:text-white block">{{ $sch->target_date->format('d M Y') }}</span>
                                    <span class="text-[11px] text-slate-500 dark:text-gray-400 flex items-center space-x-1">
                                        <i class="fa-regular fa-clock text-[10px] text-indigo-600 dark:text-indigo-400"></i>
                                        <span>{{ $sch->target_time }} WIB</span>
                                    </span>
                                </td>

                                <!-- Target Akun -->
                                <td class="py-3 px-4">
                                    @if($project)
                                        <div class="space-y-1">
                                            @foreach($project->targets->take(2) as $target)
                                                @php $acc = $target->connectedAccount; @endphp
                                                <div class="flex items-center space-x-1 text-[11px]">
                                                    <span class="truncate max-w-[120px] text-slate-700 dark:text-gray-300 font-medium">{{ $acc ? $acc->page_name : '-' }}</span>
                                                    @if($target->platform_target === 'both')
                                                        <span class="text-[9px] text-indigo-600 dark:text-indigo-400 font-bold">(FB+IG)</span>
                                                    @elseif($target->platform_target === 'instagram_only')
                                                        <span class="text-[9px] text-pink-600 dark:text-pink-400 font-bold">(IG)</span>
                                                    @elseif($target->platform_target === 'facebook_only')
                                                        <span class="text-[9px] text-blue-600 dark:text-blue-400 font-bold">(FB)</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if($project->targets->count() > 2)
                                                <span class="text-[10px] text-slate-500 dark:text-gray-500">+{{ $project->targets->count() - 2 }} akun lainnya</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>

                                <!-- Status -->
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
                                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-gray-400 group-hover:border-slate-500 transition">
                                                Dilewati <i class="fa-solid fa-pen text-[8px] ml-0.5 opacity-60"></i>
                                            </span>
                                        @elseif($sch->status === 'partially_failed')
                                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-orange-50 dark:bg-orange-950/80 border border-orange-200 dark:border-orange-800 text-orange-700 dark:text-orange-400 group-hover:border-orange-500 transition">
                                                Parsial <i class="fa-solid fa-pen text-[8px] ml-0.5 opacity-60"></i>
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 group-hover:border-rose-500 transition">
                                                Gagal <i class="fa-solid fa-pen text-[8px] ml-0.5 opacity-60"></i>
                                            </span>
                                        @endif
                                    </button>

                                    @if($sch->notes)
                                        <p class="text-[10px] text-slate-500 dark:text-gray-400 truncate max-w-xs mt-1" title="{{ $sch->notes }}">
                                            {{ $sch->notes }}
                                        </p>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <!-- Detail Log Modal Button -->
                                        <button onclick="showScheduleLogModal({{ $sch->id }})" 
                                                class="px-2 py-1 text-[11px] font-semibold rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 border border-slate-300 dark:border-gray-700 transition" title="Lihat Detail Log Eksekusi">
                                            <i class="fa-solid fa-list-check"></i>
                                        </button>

                                        <!-- Publish / Terbitkan Ulang Single Button -->
                                        @if($sch->status === 'completed')
                                            <button onclick="promptRepublishOption({{ $sch->id }}, '{{ $sch->target_date ? $sch->target_date->translatedFormat('d M Y') : '' }} {{ $sch->target_time }} WIB', '{{ $project ? addslashes($project->name) : 'Campaign' }}')" 
                                                    class="px-2.5 py-1 text-[11px] font-semibold rounded-md bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-600/20 dark:hover:bg-amber-600 dark:text-amber-300 dark:hover:text-white border border-amber-200 dark:border-amber-500/30 transition shadow-sm" title="Pilihan Terbitkan Ulang">
                                                <i class="fa-solid fa-arrows-rotate"></i>
                                            </button>
                                        @elseif(in_array($sch->status, ['failed', 'partially_failed']))
                                            <button onclick="publishScheduledWithProgress({{ $sch->id }}, '{{ $sch->target_date ? $sch->target_date->translatedFormat('d M Y') : '' }} {{ $sch->target_time }} WIB', '{{ $project ? addslashes($project->name) : 'Campaign' }}')" 
                                                    class="px-2.5 py-1 text-[11px] font-semibold rounded-md bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-600/20 dark:hover:bg-rose-600 dark:text-rose-300 dark:hover:text-white border border-rose-200 dark:border-rose-500/30 transition shadow-sm" title="Coba Terbitkan Lagi (Live Progress)">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                        @else
                                            <button onclick="publishScheduledWithProgress({{ $sch->id }}, '{{ $sch->target_date ? $sch->target_date->translatedFormat('d M Y') : '' }} {{ $sch->target_time }} WIB', '{{ $project ? addslashes($project->name) : 'Campaign' }}')" 
                                                    class="px-2.5 py-1 text-[11px] font-semibold rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600 dark:text-indigo-300 dark:hover:text-white border border-indigo-200 dark:border-indigo-500/30 transition shadow-sm" title="Terbitkan Sekarang (Live Progress)">
                                                <i class="fa-solid fa-paper-plane"></i>
                                            </button>
                                        @endif

                                        <!-- Ubah Status Button -->
                                        <button onclick="openChangeStatusModal({{ $sch->id }}, '{{ $sch->status }}', '{{ $sch->target_date ? $sch->target_date->translatedFormat('d M Y') : '' }}')"
                                                class="p-1.5 text-slate-400 hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400 transition" title="Ubah Status Jadwal">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Hapus Button -->
                                        <button onclick="deleteSchedule({{ $sch->id }})" 
                                                class="p-1.5 text-slate-400 hover:text-rose-600 dark:text-gray-500 dark:hover:text-rose-400 transition" title="Hapus Jadwal">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200 dark:border-gray-800">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal Detail Log Eksekusi Per Target Per Platform -->
<div id="scheduleLogModal" class="fixed inset-0 z-50 hidden bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="card-dark rounded-xl max-w-2xl w-full border border-slate-200 dark:border-gray-800 p-6 space-y-5 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-gray-800 pb-3">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                <i class="fa-solid fa-list-check text-indigo-600 dark:text-indigo-400"></i>
                <span id="logModalTitle">Detail Log Eksekusi Jadwal</span>
            </h3>
            <button onclick="closeLogModal()" class="text-slate-400 hover:text-slate-600 dark:text-gray-400 dark:hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div id="logModalContent" class="space-y-3 max-h-96 overflow-y-auto">
            <p class="text-xs text-slate-500 dark:text-gray-400 italic">Memuat log eksekusi...</p>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-gray-800 flex justify-end">
            <button onclick="closeLogModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 rounded-lg text-xs font-semibold transition">
                Tutup
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function showScheduleLogModal(id) {
        document.getElementById('scheduleLogModal').classList.remove('hidden');
        document.getElementById('logModalContent').innerHTML = '<p class="text-xs text-gray-400 italic">Memuat data log...</p>';

        fetch(`/schedules/${id}/logs`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const logs = data.logs;
                if (logs.length === 0) {
                    document.getElementById('logModalContent').innerHTML = `
                        <div class="text-center py-6 text-xs text-slate-500 dark:text-gray-400 italic">
                            Jadwal ini belum pernah dieksekusi atau belum ada catatan log.
                        </div>
                    `;
                    return;
                }

                let html = '<div class="divide-y divide-slate-200/80 dark:divide-gray-800/80">';
                logs.forEach(l => {
                    const isIg = l.platform === 'instagram';
                    const isSuccess = l.action_status === 'success';
                    const isSkipped = l.action_status === 'skipped';
                    const statusClass = isSuccess ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-400' : (isSkipped ? 'bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-400' : 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-400');

                    html += `
                        <div class="py-3 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm ${isIg ? 'text-pink-600 dark:text-pink-400' : 'text-blue-600 dark:text-blue-400'} font-bold">
                                        <i class="fa-brands ${isIg ? 'fa-instagram' : 'fa-facebook'} mr-1"></i>
                                        ${isIg ? 'Instagram' : 'Facebook Page'}
                                    </span>
                                    <span class="text-xs text-slate-600 dark:text-gray-300 font-medium">(${l.connected_account ? l.connected_account.page_name : '-'})</span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${statusClass}">
                                    ${l.action_status}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-gray-400 font-mono">
                                ${l.media_id ? `<span class="text-emerald-600 dark:text-emerald-400">Media ID: ${l.media_id}</span>` : ''}
                                ${l.error_message ? `<span class="text-rose-600 dark:text-rose-400">${l.error_message}</span>` : ''}
                            </div>
                            <div class="text-[10px] text-slate-400 dark:text-gray-500">
                                Waktu Eksekusi: ${l.executed_at || '-'}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';

                document.getElementById('logModalContent').innerHTML = html;
            } else {
                document.getElementById('logModalContent').innerHTML = '<p class="text-xs text-rose-500 dark:text-rose-400">Gagal memuat log.</p>';
            }
        })
        .catch(err => {
            document.getElementById('logModalContent').innerHTML = `<p class="text-xs text-rose-500 dark:text-rose-400">Error: ${err.message}</p>`;
        });
    }

    function closeLogModal() {
        document.getElementById('scheduleLogModal').classList.add('hidden');
    }

    function runSingleSchedule(id, dateInfo, projectName = 'Campaign') {
        publishScheduledWithProgress(id, dateInfo, projectName);
    }

    function promptRepublishOption(id, dateInfo, campaignName) {
        Swal.fire({
            title: 'Pilihan Terbitkan Ulang',
            html: `<div class="text-left text-xs text-slate-600 dark:text-gray-300 space-y-3">
                <p>Jadwal tanggal: <strong class="text-slate-900 dark:text-white font-mono">${dateInfo}</strong></p>
                <div class="space-y-2 text-xs">
                    <button type="button" id="btnRepublishDirectIdx" class="w-full text-left p-3 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800/80 hover:border-indigo-500 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition cursor-pointer group">
                        <strong class="text-indigo-700 dark:text-indigo-300 group-hover:text-indigo-900 dark:group-hover:text-white block font-semibold flex items-center space-x-1.5">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span>Terbitkan Sekarang (Live Loading)</span>
                        </strong>
                        <span class="text-[10px] text-slate-500 dark:text-gray-400 block mt-0.5">Langsung publish ulang ke Meta Graph API saat ini juga dengan visual progress lengkap.</span>
                    </button>

                    <button type="button" id="btnResetPendingOnlyIdx" class="w-full text-left p-3 rounded-lg bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/80 hover:border-amber-500 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition cursor-pointer group">
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
                const btnDirect = document.getElementById('btnRepublishDirectIdx');
                const btnPending = document.getElementById('btnResetPendingOnlyIdx');

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

    function triggerRetryFailed() {
        showLoading('Me-reset Jadwal...', 'Mengembalikan status seluruh jadwal gagal ke PENDING...');
        fetch("{{ route('schedules.retryFailed') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Berhasil', data.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('info', 'Info', data.message);
            }
        })
        .catch(err => {
            showAlert('error', 'Error', err.message);
        });
    }

    function deleteSchedule(id) {
        Swal.fire({
            title: 'Hapus Item Antrean?',
            text: 'Item jadwal posting ini akan dihapus dari antrean.',
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
                showLoading('Menghapus...', 'Mohon tunggu...');
                fetch(`/schedules/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Dihapus', data.message);
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

    function resetToPending(id, dateInfo) {
        promptRepublishOption(id, dateInfo, 'Jadwal');
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
            cancelButtonColor: '#374151',
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
