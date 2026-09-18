@extends('layouts.app')

@section('title', 'Buat Project Campaign Baru')

@php
    $defaultName = '';
    if (isset($sourceProject) && $sourceProject) {
        $baseName = preg_replace('/ \(Salinan( \d+)?\)$/i', '', $sourceProject->name);
        $similarCount = \App\Models\ProjectCampaign::where('name', 'LIKE', $baseName . ' (Salinan%')->count();
        $defaultName = $similarCount > 0 ? $baseName . ' (Salinan ' . ($similarCount + 1) . ')' : $baseName . ' (Salinan)';
    }
    $selectedContentType = old('content_type', $sourceProject->content_type ?? 'story');
    $selectedRepeatType = old('repeat_type', $sourceProject->repeat_type ?? 'continuous');
    $captionVal = old('caption', $sourceProject->caption ?? '');
    $startDateVal = old('start_date', (isset($sourceProject) && $sourceProject->start_date) ? $sourceProject->start_date->format('Y-m-d') : date('Y-m-d'));
    $endDateVal = old('end_date', (isset($sourceProject) && $sourceProject->end_date) ? $sourceProject->end_date->format('Y-m-d') : date('Y-m-d', strtotime('+30 days')));
    $targetTimeVal = old('target_time', (isset($sourceProject) && $sourceProject->target_time) ? $sourceProject->target_time : '07:30');
    $excludeDaysVal = old('exclude_days', isset($sourceProject) ? ($sourceProject->exclude_days ?? []) : [0]);

    $sourceTargets = [];
    if (isset($sourceProject) && $sourceProject->relationLoaded('targets')) {
        foreach ($sourceProject->targets as $t) {
            $sourceTargets[$t->connected_account_id] = $t->platform_target;
        }
    }

    $existingMediaData = [];
    if (isset($sourceProject) && $sourceProject && $sourceProject->relationLoaded('mediaFiles')) {
        $existingMediaData = $sourceProject->mediaFiles->map(function ($m) {
            return [
                'id' => $m->id,
                'name' => $m->original_name,
                'url' => $m->url,
                'is_video' => (bool)$m->is_video,
            ];
        })->values()->all();
    }
@endphp

@section('content')
<div class="w-full space-y-5 max-w-7xl mx-auto">

    <!-- Header & Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80 dark:border-gray-800/80">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-gray-400 mb-1">
                <a href="{{ route('projects.index') }}" class="hover:text-slate-900 dark:hover:text-white transition flex items-center space-x-1">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Campaigns</span>
                </a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white font-medium">{{ isset($sourceProject) && $sourceProject ? 'Duplikat Campaign' : 'Buat Baru' }}</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center space-x-2.5">
                <span>{{ isset($sourceProject) && $sourceProject ? 'Duplikat Campaign: ' . $sourceProject->name : 'Buat Campaign Baru' }}</span>
            </h1>
        </div>
        <a href="{{ route('projects.index') }}" 
           class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg border border-slate-200 dark:border-gray-800 bg-white dark:bg-slate-800/80 text-xs font-semibold text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-sm w-fit">
            <i class="fa-solid fa-arrow-left text-[11px]"></i>
            <span>Kembali ke Daftar</span>
        </a>
    </div>

    <!-- Banner Notifikasi Duplikat -->
    @if(isset($sourceProject) && $sourceProject)
        <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800 text-indigo-900 dark:text-indigo-200 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-copy"></i>
                </div>
                <div>
                    <strong class="block font-bold">Mode Duplikasi Campaign (Belum Disimpan)</strong>
                    <span>Formulir di bawah telah diisi dengan data dari <strong>{{ $sourceProject->name }}</strong>. Silakan sesuaikan data lalu klik tombol <strong>Simpan & Inisialisasi</strong> untuk menyimpan.</span>
                </div>
            </div>
            <a href="{{ route('projects.create') }}" class="px-3 py-1.5 rounded-lg border border-indigo-300 dark:border-indigo-700 bg-white dark:bg-gray-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 font-semibold text-[11px] shrink-0 transition text-center">
                Mulai Formulir Kosong
            </a>
        </div>
    @endif

    @if($accounts->isEmpty())
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs flex items-center space-x-3">
            <i class="fa-solid fa-triangle-exclamation text-amber-500 text-base shrink-0"></i>
            <div>
                <strong class="block font-semibold">Belum ada akun Meta yang terhubung!</strong>
                <span>Hubungkan akun terlebih dahulu di menu <a href="{{ route('settings.index', ['tab' => 'meta']) }}" class="underline font-bold text-amber-900 dark:text-white">Pengaturan (Integrasi Meta)</a> sebelum membuat campaign.</span>
            </div>
        </div>
    @endif

    <form id="formCreateProject" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <input type="hidden" name="images_per_post" value="1">

        <!-- ==================== KOLOM KIRI: DETAIL & JADWAL ==================== -->
        <div class="lg:col-span-7 space-y-5">
            
            <!-- Card 1: Detail Campaign -->
            <div class="card-dark rounded-xl p-5 sm:p-6 border border-slate-200 dark:border-gray-800 shadow-sm space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 flex items-center space-x-2 border-b border-slate-100 dark:border-gray-800 pb-3">
                    <i class="fa-solid fa-sliders text-indigo-500"></i>
                    <span>Informasi Campaign</span>
                </h2>

                <!-- Nama Project -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 mb-1.5">
                        Nama Campaign <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required value="{{ old('name', $defaultName) }}" placeholder="Contoh: Campaign Pagi (Promo & Quotes)" 
                           class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition">
                </div>

                <!-- Tipe Konten: Story vs Feed Post -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 mb-1.5">
                        Tipe Konten Publish <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer relative">
                            <input type="radio" name="content_type" value="story" {{ $selectedContentType === 'story' ? 'checked' : '' }} class="peer hidden" onchange="updateContentTypeHint()">
                            <div class="p-3 rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 peer-checked:border-indigo-600 peer-checked:ring-2 peer-checked:ring-indigo-500/20 peer-checked:bg-indigo-50/40 dark:peer-checked:bg-indigo-950/20 transition flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-pink-500/10 text-pink-600 dark:text-pink-400 flex items-center justify-center text-sm shrink-0">
                                    <i class="fa-solid fa-circle-notch"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Story</div>
                                    <div class="text-[10px] text-slate-500 dark:text-gray-400 truncate">Instagram & FB Story</div>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer relative">
                            <input type="radio" name="content_type" value="post" {{ $selectedContentType === 'post' ? 'checked' : '' }} class="peer hidden" onchange="updateContentTypeHint()">
                            <div class="p-3 rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 peer-checked:border-indigo-600 peer-checked:ring-2 peer-checked:ring-indigo-500/20 peer-checked:bg-indigo-50/40 dark:peer-checked:bg-indigo-950/20 transition flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center text-sm shrink-0">
                                    <i class="fa-solid fa-square-rss"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Feed Post</div>
                                    <div class="text-[10px] text-slate-500 dark:text-gray-400 truncate">Feed & Reels</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Caption -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300">
                            Caption Postingan
                        </label>
                        <span class="text-[10px] text-slate-400 dark:text-gray-500" id="captionHint">Opsional untuk Story</span>
                    </div>
                    <textarea name="caption" rows="3" placeholder="Tuliskan caption postingan atau hashtag di sini..."
                              class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3.5 py-2 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition">{{ $captionVal }}</textarea>
                </div>
            </div>

            <!-- Card 2: Jadwal & Waktu Tayang -->
            <div class="card-dark rounded-xl p-5 sm:p-6 border border-slate-200 dark:border-gray-800 shadow-sm space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 flex items-center space-x-2 border-b border-slate-100 dark:border-gray-800 pb-3">
                    <i class="fa-solid fa-calendar-days text-indigo-500"></i>
                    <span>Jadwal dan Waktu Tayang</span>
                </h2>

                <!-- Moda Pengulangan (Repeat Mode) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 mb-1.5">
                        Moda Pengulangan <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-2.5">
                        <label class="cursor-pointer relative">
                            <input type="radio" name="repeat_type" value="instant" {{ $selectedRepeatType === 'instant' ? 'checked' : '' }} class="peer hidden" onchange="toggleRepeatFields()">
                            <div class="p-2 sm:p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 peer-checked:border-emerald-600 peer-checked:ring-2 peer-checked:ring-emerald-500/20 peer-checked:bg-emerald-50/40 dark:peer-checked:bg-emerald-950/20 transition text-center min-h-[44px]">
                                <div class="text-emerald-500 text-sm mb-0.5"><i class="fa-solid fa-paper-plane"></i></div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">Post Langsung</div>
                                <div class="text-[10px] text-slate-500 dark:text-gray-400 leading-tight">Tayang sekarang</div>
                            </div>
                        </label>

                        <label class="cursor-pointer relative">
                            <input type="radio" name="repeat_type" value="continuous" {{ $selectedRepeatType === 'continuous' ? 'checked' : '' }} class="peer hidden" onchange="toggleRepeatFields()">
                            <div class="p-2 sm:p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 peer-checked:border-indigo-600 peer-checked:ring-2 peer-checked:ring-indigo-500/20 peer-checked:bg-indigo-50/40 dark:peer-checked:bg-indigo-950/20 transition text-center min-h-[44px]">
                                <div class="text-indigo-600 dark:text-indigo-400 text-sm mb-0.5"><i class="fa-solid fa-arrows-rotate"></i></div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">Kontinu</div>
                                <div class="text-[10px] text-slate-500 dark:text-gray-400 leading-tight">Rolling harian</div>
                            </div>
                        </label>

                        <label class="cursor-pointer relative">
                            <input type="radio" name="repeat_type" value="once" {{ $selectedRepeatType === 'once' ? 'checked' : '' }} class="peer hidden" onchange="toggleRepeatFields()">
                            <div class="p-2 sm:p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 peer-checked:border-indigo-600 peer-checked:ring-2 peer-checked:ring-indigo-500/20 peer-checked:bg-indigo-50/40 dark:peer-checked:bg-indigo-950/20 transition text-center min-h-[44px]">
                                <div class="text-amber-500 text-sm mb-0.5"><i class="fa-solid fa-bullseye"></i></div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">1x Post</div>
                                <div class="text-[10px] text-slate-500 dark:text-gray-400 leading-tight">Sekali tayang</div>
                            </div>
                        </label>

                        <label class="cursor-pointer relative">
                            <input type="radio" name="repeat_type" value="until_date" {{ $selectedRepeatType === 'until_date' ? 'checked' : '' }} class="peer hidden" onchange="toggleRepeatFields()">
                            <div class="p-2 sm:p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 peer-checked:border-indigo-600 peer-checked:ring-2 peer-checked:ring-indigo-500/20 peer-checked:bg-indigo-50/40 dark:peer-checked:bg-indigo-950/20 transition text-center min-h-[44px]">
                                <div class="text-purple-500 text-sm mb-0.5"><i class="fa-regular fa-calendar-check"></i></div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">Hingga Tgl</div>
                                <div class="text-[10px] text-slate-500 dark:text-gray-400 leading-tight">Rentang waktu</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Instant Post Banner Notice -->
                <div id="instantNoticeWrapper" class="hidden p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-3 shadow-sm">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div>
                        <strong class="block font-semibold">Moda Post Langsung Aktif</strong>
                        <span>Konten akan langsung dipublikasikan sekarang juga ke akun Meta yang dipilih saat formulir disimpan. Tidak memerlukan konfigurasi tanggal atau jam tayang.</span>
                    </div>
                </div>

                <!-- Dynamic Date & Time Inputs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="dateInputsContainer">
                    <div id="startDateWrapper">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 mb-1" id="startDateLabel">
                            Mulai Tanggal <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="inputStartDate" value="{{ $startDateVal }}" required
                               class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                        <p id="startDateHelp" class="text-[10px] text-slate-400 dark:text-gray-500 mt-1">Jadwal dimulai dari tanggal ini ke depan.</p>
                    </div>

                    <div id="endDateWrapper" class="hidden">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 mb-1">
                            Sampai Tanggal <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="end_date" id="inputEndDate" value="{{ $endDateVal }}" 
                               class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                        <p class="text-[10px] text-slate-400 dark:text-gray-500 mt-1">Berhenti tayang setelah tanggal ini.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 mb-1">
                            Jam Tayang (WIB) <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" name="target_time" id="inputTargetTime" value="{{ $targetTimeVal }}" required 
                               class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                        <p id="onceTimeNotice" class="hidden text-[10px] text-amber-500 mt-1 font-medium">Khusus 1x: Min. 30 menit dari jam sekarang.</p>
                    </div>
                </div>

                <!-- Exclude Days (Pill Style) -->
                <div id="excludeDaysWrapper" class="pt-1">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 mb-1.5">
                        Kecualikan Hari (Hari Libur Posting)
                    </label>
                    <div class="flex flex-wrap gap-1.5 sm:gap-2">
                        @php
                            $days = [
                                0 => 'Minggu',
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                            ];
                        @endphp
                        @foreach($days as $val => $dayName)
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" name="exclude_days[]" value="{{ $val }}" {{ in_array($val, $excludeDaysVal) ? 'checked' : '' }} class="peer hidden">
                                <span class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[11px] font-semibold text-slate-600 dark:text-gray-300 peer-checked:bg-rose-500 peer-checked:border-rose-500 peer-checked:text-white transition shadow-sm">
                                    {{ $dayName }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <span class="text-[10px] text-slate-400 dark:text-gray-500 block mt-1.5">Hari bertanda merah dikecualikan dari jadwal posting.</span>
                </div>
            </div>
        </div>

        <!-- ==================== KOLOM KANAN: TARGET & MEDIA ==================== -->
        <div class="lg:col-span-5 space-y-5">
            
            <!-- Card 3: Target Akun Meta -->
            <div class="card-dark rounded-xl p-5 border border-slate-200 dark:border-gray-800 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-gray-800 pb-2.5">
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 flex items-center space-x-2">
                            <i class="fa-solid fa-bullseye text-indigo-500"></i>
                            <span>Target Akun Meta</span>
                        </h2>
                        <span class="text-[10px] text-slate-500 dark:text-gray-400" id="selectedAccountCounter">Pilih akun tujuan</span>
                    </div>
                    <button type="button" onclick="toggleAllAccounts()" id="btnSelectAll" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">
                        Pilih Semua
                    </button>
                </div>

                <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1 divide-y divide-slate-100 dark:divide-gray-800/60">
                    @forelse($accounts as $acc)
                        @php
                            $isTargeted = isset($sourceTargets[$acc->id]);
                            $platformTarget = $sourceTargets[$acc->id] ?? 'both';
                        @endphp
                        <div class="pt-2 first:pt-0">
                            <div class="flex items-center justify-between gap-2 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-800/40 transition">
                                <label class="flex items-center space-x-2.5 cursor-pointer flex-grow min-w-0">
                                    <input type="checkbox" name="selected_accounts[]" value="{{ $acc->id }}" 
                                           {{ $isTargeted ? 'checked' : '' }}
                                           class="account-checkbox rounded text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-gray-800 border-slate-300 dark:border-gray-700 w-4 h-4 shrink-0"
                                           onchange="toggleTargetRow({{ $acc->id }})">
                                    <div class="min-w-0">
                                        <span class="font-semibold text-xs text-slate-900 dark:text-white block truncate">{{ $acc->page_name }}</span>
                                        <span class="text-[10px] text-slate-500 dark:text-gray-400 flex items-center space-x-1 truncate">
                                            @if($acc->ig_username)
                                                <i class="fa-brands fa-instagram text-pink-500"></i><span>&#64;{{ $acc->ig_username }}</span>
                                            @else
                                                <span class="italic text-slate-400">Facebook Page</span>
                                            @endif
                                        </span>
                                    </div>
                                </label>

                                <div id="platformControl_{{ $acc->id }}" class="{{ $isTargeted ? '' : 'hidden' }} shrink-0">
                                    <select name="platform_targets[{{ $acc->id }}]" class="bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-2 py-1 text-[11px] text-slate-800 dark:text-gray-200 focus:outline-none focus:border-indigo-500 transition">
                                        <option value="both" {{ $platformTarget === 'both' ? 'selected' : '' }}>Both (FB & IG)</option>
                                        <option value="instagram_only" {{ $platformTarget === 'instagram_only' ? 'selected' : '' }}>Instagram Saja</option>
                                        <option value="facebook_only" {{ $platformTarget === 'facebook_only' ? 'selected' : '' }}>FB Page Saja</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-xs text-slate-400">
                            Belum ada akun Meta yang terhubung.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Card 4: Unggah Materi Media Pool -->
            <div class="card-dark rounded-xl p-5 border border-slate-200 dark:border-gray-800 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-gray-800 pb-2.5">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 flex items-center space-x-2">
                        <i class="fa-solid fa-photo-film text-pink-500"></i>
                        <span>Materi Media Pool</span>
                    </h2>
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="openMediaLibraryModal()" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline flex items-center space-x-1 min-h-[44px]">
                            <i class="fa-solid fa-folder-open text-[11px]"></i>
                            <span>Pilih Library</span>
                        </button>
                        <span class="text-slate-300 dark:text-gray-700 text-xs">|</span>
                        <span id="mediaCountBadge" class="text-xs font-bold text-indigo-600 dark:text-indigo-400">0 File</span>
                    </div>
                </div>

                <!-- Dropzone Area -->
                <div id="dropzone" class="border-2 border-dashed border-slate-300 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-500 rounded-xl p-5 text-center bg-slate-50/50 dark:bg-gray-900/50 transition cursor-pointer group">
                    <input type="file" id="inputMediaFiles" multiple accept="image/jpeg,image/png,video/mp4,video/quicktime" class="hidden">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto text-lg mb-2 group-hover:scale-110 transition duration-200">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                        Klik untuk pilih file atau tarik kemari
                    </p>
                    <p class="text-[10px] text-slate-400 dark:text-gray-500 mt-0.5">Format: JPG, PNG, MP4, MOV</p>
                </div>

                <!-- Thumbnail Preview Grid -->
                <div id="previewContainer" class="hidden space-y-2">
                    <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-gray-400">
                        <span>Preview Media:</span>
                        <button type="button" onclick="clearAllMedia()" class="text-rose-500 hover:underline text-[10px]">Hapus Semua</button>
                    </div>
                    <div id="previewGrid" class="grid grid-cols-3 sm:grid-cols-4 gap-2 max-h-52 overflow-y-auto pr-1"></div>
                </div>
            </div>
        </div>

        <!-- ==================== FOOTER / BOTTOM ACTION BAR ==================== -->
        <div class="lg:col-span-12 card-dark rounded-xl p-4 sm:p-5 border border-slate-200 dark:border-gray-800 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-2.5 text-xs text-slate-500 dark:text-gray-400">
                <i class="fa-solid fa-circle-check text-indigo-500 text-sm shrink-0"></i>
                <span id="footerNoticeText">Pastikan seluruh konfigurasi campaign, target akun, dan media pool sudah lengkap sebelum menyimpan.</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto justify-end">
                <a href="{{ route('projects.index') }}" 
                   class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 font-semibold rounded-lg text-xs transition text-center min-h-[44px] inline-flex items-center justify-center min-w-[80px]">
                    Batal
                </a>
                
                <button type="button" id="btnDirectPublish" onclick="submitForm(true)"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-lg text-xs transition shadow-md flex items-center justify-center space-x-2 min-h-[44px]">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span id="btnDirectPublishText">Simpan & Post Langsung</span>
                </button>

                <button type="button" id="btnInitSchedule" onclick="submitForm(false)"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition shadow-md flex items-center justify-center space-x-2 min-h-[44px]">
                    <i class="fa-solid fa-rocket"></i>
                    <span>Simpan & Inisialisasi</span>
                </button>
            </div>
        </div>
    </form>

    <!-- Modal Media Library Picker -->
    <div id="mediaLibraryModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="card-dark rounded-xl border border-slate-200 dark:border-gray-800 shadow-2xl max-w-2xl w-full p-5 space-y-4 max-h-[85vh] flex flex-col bg-white dark:bg-slate-900">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-gray-800">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-folder-open text-indigo-500"></i>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pilih Media dari Library</h3>
                </div>
                <button type="button" onclick="closeMediaLibraryModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg p-1 min-h-[44px] min-w-[44px] flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="mediaLibraryLoading" class="py-10 text-center text-xs text-slate-500">
                <div class="w-8 h-8 mx-auto rounded-full border-2 border-indigo-600 border-t-transparent animate-spin mb-2"></div>
                <span>Memuat daftar media...</span>
            </div>

            <div id="mediaLibraryEmpty" class="hidden py-10 text-center text-xs text-slate-400">
                Belum ada file media yang tersimpan di library.
            </div>

            <div id="mediaLibraryGrid" class="grid grid-cols-3 sm:grid-cols-4 gap-2.5 overflow-y-auto max-h-96 pr-1 hidden">
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-gray-800">
                <span class="text-[11px] text-slate-500 dark:text-gray-400">Klik media untuk menambahkan ke Media Pool campaign</span>
                <button type="button" onclick="closeMediaLibraryModal()" class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 text-xs font-semibold hover:bg-slate-200 dark:hover:bg-gray-700 min-h-[44px]">
                    Selesai
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // State Media Files
    let selectedFiles = [];
    let existingMedia = {!! json_encode($existingMediaData) !!};

    lightboxItems = [];
    currentLightboxIdx = 0;

    // Dropzone & Media Handling
    const dropzone = document.getElementById('dropzone');
    const inputMediaFiles = document.getElementById('inputMediaFiles');
    const previewContainer = document.getElementById('previewContainer');
    const previewGrid = document.getElementById('previewGrid');
    const mediaCountBadge = document.getElementById('mediaCountBadge');

    if (dropzone) {
        dropzone.addEventListener('click', () => inputMediaFiles.click());

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.classList.add('border-indigo-500', 'bg-indigo-50/20');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.classList.remove('border-indigo-500', 'bg-indigo-50/20');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            const files = Array.from(e.dataTransfer.files);
            addFiles(files);
        });

        inputMediaFiles.addEventListener('change', function() {
            const files = Array.from(this.files);
            addFiles(files);
            this.value = '';
        });
    }

    function addFiles(files) {
        const validExtensions = /\.(jpe?g|png|mp4|mov)$/i;
        files.forEach(f => {
            if (f.type.startsWith('image/') || f.type.startsWith('video/') || f.name.match(validExtensions)) {
                if (!selectedFiles.some(existing => existing.name === f.name && existing.size === f.size)) {
                    selectedFiles.push(f);
                }
            }
        });
        renderPreviews();
    }

    function removeExistingMedia(index, event) {
        if (event) event.stopPropagation();
        existingMedia.splice(index, 1);
        renderPreviews();
    }

    function removeFile(index, event) {
        if (event) event.stopPropagation();
        selectedFiles.splice(index, 1);
        renderPreviews();
    }

    function clearAllMedia() {
        selectedFiles = [];
        existingMedia = [];
        renderPreviews();
    }

    function renderPreviews() {
        previewGrid.innerHTML = '';
        lightboxItems = [];

        const totalCount = existingMedia.length + selectedFiles.length;

        if (totalCount === 0) {
            previewContainer.classList.add('hidden');
            mediaCountBadge.textContent = '0 File';
            return;
        }

        previewContainer.classList.remove('hidden');
        mediaCountBadge.textContent = `${totalCount} File`;

        // 1. Render Existing Media dari Project yang Diduplikat
        existingMedia.forEach((m, index) => {
            lightboxItems.push({
                url: m.url,
                name: m.name,
                isVideo: !!m.is_video
            });

            const card = document.createElement('div');
            card.className = 'group relative rounded-lg border border-indigo-300 dark:border-indigo-700/80 bg-slate-100 dark:bg-gray-800 overflow-hidden shadow-sm cursor-pointer aspect-square';

            let mediaHtml = m.is_video 
                ? `<video src="${m.url}" class="w-full h-full object-cover"></video>` 
                : `<img src="${m.url}" class="w-full h-full object-cover group-hover:scale-105 transition duration-200">`;

            card.innerHTML = `
                ${mediaHtml}
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <button type="button" onclick="removeExistingMedia(${index}, event)" title="Hapus Dari Salinan" class="absolute top-1 right-1 w-5 h-5 bg-rose-600 hover:bg-rose-700 text-white rounded-full flex items-center justify-center text-[10px] shadow z-10">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="absolute bottom-1 left-1 px-1 py-0.5 rounded bg-indigo-600/90 text-[8px] text-white font-mono flex items-center space-x-0.5 shadow-sm">
                    <i class="fa-solid fa-copy text-[7px]"></i>
                    <span>SALINAN</span>
                </div>
                ${m.is_video ? '<div class="absolute top-1 left-1 px-1 py-0.5 rounded bg-black/60 text-[8px] text-white font-mono flex items-center space-x-0.5"><i class="fa-solid fa-video text-[7px]"></i><span>VID</span></div>' : ''}
            `;

            const currentIdx = lightboxItems.length - 1;
            card.onclick = () => {
                currentLightboxIdx = currentIdx;
                updateLightboxView();
                document.getElementById('lightboxModal').classList.remove('hidden');
            };

            previewGrid.appendChild(card);
        });

        // 2. Render File Baru yang Diunggah
        selectedFiles.forEach((file, index) => {
            const fileUrl = URL.createObjectURL(file);
            const isVideo = file.type.startsWith('video/') || file.name.match(/\.(mp4|mov)$/i);

            lightboxItems.push({
                url: fileUrl,
                name: file.name,
                isVideo: !!isVideo
            });

            const card = document.createElement('div');
            card.className = 'group relative rounded-lg border border-slate-200 dark:border-gray-800 bg-slate-100 dark:bg-gray-800 overflow-hidden shadow-sm cursor-pointer aspect-square';

            let mediaHtml = isVideo 
                ? `<video src="${fileUrl}" class="w-full h-full object-cover"></video>` 
                : `<img src="${fileUrl}" class="w-full h-full object-cover group-hover:scale-105 transition duration-200">`;

            card.innerHTML = `
                ${mediaHtml}
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <button type="button" onclick="removeFile(${index}, event)" title="Hapus" class="absolute top-1 right-1 w-5 h-5 bg-rose-600 hover:bg-rose-700 text-white rounded-full flex items-center justify-center text-[10px] shadow z-10">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                ${isVideo ? '<div class="absolute bottom-1 left-1 px-1 py-0.5 rounded bg-black/60 text-[8px] text-white font-mono flex items-center space-x-0.5"><i class="fa-solid fa-video text-[7px]"></i><span>VID</span></div>' : ''}
            `;

            const currentIdx = lightboxItems.length - 1;
            card.onclick = () => {
                currentLightboxIdx = currentIdx;
                updateLightboxView();
                document.getElementById('lightboxModal').classList.remove('hidden');
            };

            previewGrid.appendChild(card);
        });
    }

    // Account Target Selection Handling
    function toggleTargetRow(accId) {
        const checkbox = document.querySelector(`input[value="${accId}"]`);
        const ctrl = document.getElementById(`platformControl_${accId}`);
        if (checkbox && ctrl) {
            if (checkbox.checked) {
                ctrl.classList.remove('hidden');
            } else {
                ctrl.classList.add('hidden');
            }
        }
        updateAccountCounter();
    }

    function toggleAllAccounts() {
        const checkboxes = Array.from(document.querySelectorAll('.account-checkbox'));
        const allChecked = checkboxes.length > 0 && checkboxes.every(cb => cb.checked);
        const targetState = !allChecked;

        checkboxes.forEach(cb => {
            cb.checked = targetState;
            toggleTargetRow(cb.value);
        });
    }

    function updateAccountCounter() {
        const checked = document.querySelectorAll('.account-checkbox:checked').length;
        const total = document.querySelectorAll('.account-checkbox').length;
        const counter = document.getElementById('selectedAccountCounter');
        const btnSelectAll = document.getElementById('btnSelectAll');

        if (counter) {
            counter.textContent = checked > 0 ? `${checked} dari ${total} akun dipilih` : 'Pilih akun tujuan';
        }
        if (btnSelectAll) {
            btnSelectAll.textContent = (checked === total && total > 0) ? 'Batal Semua' : 'Pilih Semua';
        }
    }

    // Media Library Modal Handling
    let libraryMediaLoaded = false;

    function openMediaLibraryModal() {
        const modal = document.getElementById('mediaLibraryModal');
        modal.classList.remove('hidden');
        if (!libraryMediaLoaded) {
            loadMediaLibrary();
        }
    }

    function closeMediaLibraryModal() {
        const modal = document.getElementById('mediaLibraryModal');
        modal.classList.add('hidden');
    }

    function loadMediaLibrary() {
        const loading = document.getElementById('mediaLibraryLoading');
        const empty = document.getElementById('mediaLibraryEmpty');
        const grid = document.getElementById('mediaLibraryGrid');

        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        grid.classList.add('hidden');

        fetch("{{ route('schedules.recentMedia') }}", {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            loading.classList.add('hidden');
            libraryMediaLoaded = true;

            if (data.success && data.media && data.media.length > 0) {
                grid.innerHTML = '';
                grid.classList.remove('hidden');

                data.media.forEach(item => {
                    const card = document.createElement('div');
                    card.className = 'group relative rounded-lg border border-slate-200 dark:border-gray-800 bg-slate-100 dark:bg-gray-800 overflow-hidden shadow-sm cursor-pointer aspect-square hover:border-indigo-500 transition';
                    
                    const isVideo = item.media_type === 'video';
                    const mediaHtml = isVideo 
                        ? `<video src="${item.url}" class="w-full h-full object-cover"></video>` 
                        : `<img src="${item.url}" class="w-full h-full object-cover group-hover:scale-105 transition duration-200">`;

                    card.innerHTML = `
                        ${mediaHtml}
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-semibold">
                            <i class="fa-solid fa-plus mr-1"></i> Tambah
                        </div>
                        <div class="absolute bottom-1 left-1 px-1 py-0.5 rounded bg-slate-950/80 text-[8px] text-white font-mono truncate max-w-[80%]">
                            ${item.original_name}
                        </div>
                        ${isVideo ? '<div class="absolute top-1 left-1 px-1 py-0.5 rounded bg-black/60 text-[8px] text-white font-mono flex items-center space-x-0.5"><i class="fa-solid fa-video text-[7px]"></i><span>VID</span></div>' : ''}
                    `;

                    card.onclick = () => {
                        selectLibraryMedia(item);
                        closeMediaLibraryModal();
                    };

                    grid.appendChild(card);
                });
            } else {
                empty.classList.remove('hidden');
            }
        })
        .catch(err => {
            loading.classList.add('hidden');
            showAlert('error', 'Gagal Memuat Media', err.message);
        });
    }

    function selectLibraryMedia(item) {
        if (existingMedia.some(m => m.id === item.id)) {
            showAlert('info', 'Media Sudah Ada', 'Media ini sudah ada dalam Media Pool campaign.');
            return;
        }

        existingMedia.push({
            id: item.id,
            name: item.original_name,
            url: item.url,
            is_video: item.media_type === 'video'
        });

        renderPreviews();
        showAlert('success', 'Media Ditambahkan', `File ${item.original_name} berhasil ditambahkan ke Media Pool.`);
    }

    // Repeat Mode Handling
    function toggleRepeatFields() {
        const checkedRepeat = document.querySelector('input[name="repeat_type"]:checked');
        if (!checkedRepeat) return;
        const repeatType = checkedRepeat.value;
        const dateInputsContainer = document.getElementById('dateInputsContainer');
        const startWrapper = document.getElementById('startDateWrapper');
        const endWrapper = document.getElementById('endDateWrapper');
        const startLabel = document.getElementById('startDateLabel');
        const startHelp = document.getElementById('startDateHelp');
        const excludeDaysWrapper = document.getElementById('excludeDaysWrapper');
        const onceNotice = document.getElementById('onceTimeNotice');
        const instantNotice = document.getElementById('instantNoticeWrapper');
        const timeInput = document.getElementById('inputTargetTime');
        const startDateInput = document.getElementById('inputStartDate');
        const btnInitSchedule = document.getElementById('btnInitSchedule');
        const btnDirectPublish = document.getElementById('btnDirectPublish');
        const btnDirectPublishText = document.getElementById('btnDirectPublishText');

        if (repeatType === 'instant') {
            dateInputsContainer.classList.add('hidden');
            excludeDaysWrapper.classList.add('hidden');
            if (instantNotice) instantNotice.classList.remove('hidden');
            startDateInput.removeAttribute('required');
            timeInput.removeAttribute('required');
            if (btnInitSchedule) btnInitSchedule.classList.add('hidden');
            if (btnDirectPublishText) btnDirectPublishText.textContent = 'Post Langsung Sekarang';
        } else {
            dateInputsContainer.classList.remove('hidden');
            if (instantNotice) instantNotice.classList.add('hidden');
            startDateInput.setAttribute('required', 'required');
            timeInput.setAttribute('required', 'required');
            if (btnInitSchedule) btnInitSchedule.classList.remove('hidden');
            if (btnDirectPublishText) btnDirectPublishText.textContent = 'Simpan & Post Langsung';

            if (repeatType === 'continuous') {
                endWrapper.classList.add('hidden');
                startLabel.innerHTML = 'Mulai Tanggal <span class="text-rose-500">*</span>';
                startHelp.textContent = 'Jadwal dimulai dari tanggal ini ke depan.';
                excludeDaysWrapper.classList.remove('hidden');
                onceNotice.classList.add('hidden');
            } else if (repeatType === 'once') {
                endWrapper.classList.add('hidden');
                startLabel.innerHTML = 'Tanggal Tayang <span class="text-rose-500">*</span>';
                startHelp.textContent = 'Konten tayang 1 kali pada tanggal ini.';
                excludeDaysWrapper.classList.add('hidden');
                onceNotice.classList.remove('hidden');

                if (!timeInput.value) {
                    const now = new Date();
                    now.setMinutes(now.getMinutes() + 35);
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    timeInput.value = `${hours}:${minutes}`;
                }
            } else if (repeatType === 'until_date') {
                endWrapper.classList.remove('hidden');
                startLabel.innerHTML = 'Mulai Tanggal <span class="text-rose-500">*</span>';
                startHelp.textContent = 'Tanggal dimulainya jadwal posting.';
                excludeDaysWrapper.classList.remove('hidden');
                onceNotice.classList.add('hidden');
            }
        }
    }

    function updateContentTypeHint() {
        const checkedContent = document.querySelector('input[name="content_type"]:checked');
        if (!checkedContent) return;
        const contentType = checkedContent.value;
        const hint = document.getElementById('captionHint');
        if (hint) {
            hint.textContent = contentType === 'story' ? 'Opsional untuk Story' : 'Disarankan untuk Feed Post';
        }
    }

    // Initialize state on page load
    toggleRepeatFields();
    updateAccountCounter();
    updateContentTypeHint();
    if (existingMedia.length > 0) {
        renderPreviews();
    }

    // Form Submit Handling
    function submitForm(isDirectPublish = false) {
        const selectedAccounts = Array.from(document.querySelectorAll('.account-checkbox:checked'));
        if (selectedAccounts.length === 0) {
            showAlert('warning', 'Pilih Target Akun', 'Silakan centang minimal 1 akun target Meta untuk campaign ini.');
            return;
        }

        if (selectedFiles.length === 0 && existingMedia.length === 0) {
            showAlert('warning', 'Unggah Media', 'Silakan pilih atau tarik minimal 1 file gambar atau video ke area Media Pool.');
            return;
        }

        const form = document.getElementById('formCreateProject');
        const checkedRepeat = document.querySelector('input[name="repeat_type"]:checked');
        const isInstantMode = checkedRepeat && checkedRepeat.value === 'instant';

        if (!isInstantMode && !form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);

        // Append targets payload
        selectedAccounts.forEach((cb, idx) => {
            const accId = cb.value;
            const platformSelect = document.querySelector(`select[name="platform_targets[${accId}]"]`);
            const platformVal = platformSelect ? platformSelect.value : 'both';

            formData.append(`targets[${idx}][account_id]`, accId);
            formData.append(`targets[${idx}][platform_target]`, platformVal);
        });

        // Append existing media IDs dari project yang diduplikat atau library
        existingMedia.forEach((m) => {
            formData.append('existing_media_ids[]', m.id);
        });

        // Append media files baru yang diupload
        selectedFiles.forEach((f) => {
            formData.append('media_files[]', f);
        });

        if (isDirectPublish || isInstantMode) {
            formData.append('direct_publish', '1');
            Swal.fire({
                title: 'Menerbitkan Konten Langsung...',
                html: `
                    <div class="space-y-3 text-center py-2">
                        <div class="w-12 h-12 mx-auto rounded-full border-4 border-emerald-600 border-t-transparent animate-spin"></div>
                        <p class="text-xs text-slate-600 dark:text-gray-300">
                            Menyimpan campaign dan memproses penerbitan langsung via Meta Graph API...
                        </p>
                    </div>
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                customClass: {
                    popup: 'swal2-popup-dark',
                    title: 'swal2-title-dark',
                    htmlContainer: 'swal2-html-dark'
                }
            });
        } else {
            showLoading('Menginisialisasi Campaign...', 'Menyimpan konfigurasi campaign dan membuat antrean jadwal rolling...');
        }

        fetch("{{ route('projects.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json().then(data => ({ status: res.status, data })))
        .then(({ status, data }) => {
            if (data.success) {
                if (data.direct_published) {
                    let logDetails = '';
                    if (data.logs && data.logs.length > 0) {
                        logDetails = '<div class="mt-3 text-left max-h-32 overflow-y-auto text-[11px] p-2 bg-slate-100 dark:bg-gray-800 rounded border border-slate-200 dark:border-gray-700 space-y-1">';
                        data.logs.forEach(l => {
                            const isSuccess = l.action_status === 'success';
                            logDetails += `<div class="flex items-center justify-between">
                                <span class="font-semibold uppercase">${l.platform}:</span>
                                <span class="${isSuccess ? 'text-emerald-500 font-bold' : 'text-rose-500'}">${l.action_status}</span>
                            </div>`;
                        });
                        logDetails += '</div>';
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Campaign Berhasil Diterbitkan!',
                        html: `<div class="text-xs text-slate-600 dark:text-gray-300">${data.message}${logDetails}</div>`,
                        confirmButtonColor: '#059669',
                        confirmButtonText: 'Buka Detail Campaign',
                        customClass: {
                            popup: 'swal2-popup-dark',
                            title: 'swal2-title-dark',
                            htmlContainer: 'swal2-html-dark'
                        }
                    }).then(() => {
                        window.location.href = data.redirect || "{{ route('projects.index') }}";
                    });
                } else {
                    showAlert('success', 'Campaign Berhasil Dibuat!', data.message);
                    setTimeout(() => window.location.href = data.redirect || "{{ route('projects.index') }}", 1500);
                }
            } else {
                showAlert('error', 'Gagal Memproses Campaign', data.message);
            }
        })
        .catch(err => {
            showAlert('error', 'Kesalahan Sistem', err.message);
        });
    }

    // Form Submit Event Handler
    document.getElementById('formCreateProject').addEventListener('submit', function(e) {
        e.preventDefault();
        const checkedRepeat = document.querySelector('input[name="repeat_type"]:checked');
        const isInstant = checkedRepeat && checkedRepeat.value === 'instant';
        submitForm(isInstant);
    });
</script>
@endsection
