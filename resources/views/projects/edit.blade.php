@extends('layouts.app')

@section('title', 'Edit Project Campaign: ' . $project->name)

@section('content')
<div class="w-full space-y-6">

    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-gray-400">
        <a href="{{ route('projects.index') }}" class="hover:text-slate-900 dark:hover:text-white transition flex items-center space-x-1">
            <i class="fa-solid fa-layer-group"></i>
            <span>Project Campaigns</span>
        </a>
        <span>/</span>
        <a href="{{ route('projects.show', $project->id) }}" class="hover:text-slate-900 dark:hover:text-white transition">{{ $project->name }}</a>
        <span>/</span>
        <span class="text-slate-900 dark:text-white font-semibold">Edit Campaign</span>
    </div>

    <!-- Main Card Form -->
    <div class="card-dark rounded-xl p-6 sm:p-8 shadow-xl border border-slate-200 dark:border-gray-800 space-y-6">
        <div class="border-b border-slate-200 dark:border-gray-800 pb-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center space-x-3">
                <div class="p-2.5 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-lg text-white text-lg">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <span>Edit Project Campaign</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-gray-400 mt-1">
                Perbarui konfigurasi target akun, waktu tayang, atau tambahkan materi media pool baru.
            </p>
        </div>

        <form id="formEditProject" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="images_per_post" value="1">

            <!-- 1. Nama Project -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider mb-2">Nama Project / Campaign</label>
                <input type="text" name="name" value="{{ $project->name }}" required placeholder="Contoh: Campaign Pagi" 
                       class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
            </div>

            <!-- 2. Tipe Konten: Story vs Feed Post -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider">Tipe Konten Publish</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-center space-x-3 bg-slate-50 dark:bg-gray-900 p-4 rounded-lg border border-slate-200 dark:border-gray-700 cursor-pointer hover:border-indigo-500 transition">
                        <input type="radio" name="content_type" value="story" {{ $project->content_type === 'story' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                                <i class="fa-solid fa-circle-notch text-pink-500"></i>
                                <span>Story (Instagram & Facebook)</span>
                            </div>
                            <span class="text-[11px] text-slate-500 dark:text-gray-400">Format vertikal 9:16. Menghilang setelah 24 jam.</span>
                        </div>
                    </label>

                    <label class="flex items-center space-x-3 bg-slate-50 dark:bg-gray-900 p-4 rounded-lg border border-slate-200 dark:border-gray-700 cursor-pointer hover:border-indigo-500 transition">
                        <input type="radio" name="content_type" value="post" {{ $project->content_type === 'post' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                                <i class="fa-solid fa-square-rss text-blue-500"></i>
                                <span>Feed Post / Carousel / Reels</span>
                            </div>
                            <span class="text-[11px] text-slate-500 dark:text-gray-400">Postingan beranda permanen dengan caption dan multi-media.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 3. Caption -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider mb-2">Caption Konten</label>
                <textarea name="caption" rows="3" placeholder="Tuliskan caption postingan..."
                          class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">{{ $project->caption }}</textarea>
            </div>

            <!-- 4. Target Akun & Kontrol Platform Per Akun -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider">Target Akun & Platform</label>
                        <p class="text-[11px] text-slate-500 dark:text-gray-400">Pilih akun yang dituju dan tentukan platform tujuan per target.</p>
                    </div>
                </div>

                @php
                    $targetMap = $project->targets->keyBy('connected_account_id');
                @endphp

                <div class="bg-slate-50/80 dark:bg-gray-900/90 rounded-xl border border-slate-200 dark:border-gray-800 divide-y divide-slate-200 dark:divide-gray-800/80 overflow-hidden">
                    @foreach($accounts as $acc)
                        @php
                            $isSelected = $targetMap->has($acc->id);
                            $selectedPlatform = $isSelected ? $targetMap->get($acc->id)->platform_target : 'both';
                        @endphp
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-100/60 dark:hover:bg-gray-800/30 transition">
                            <label class="flex items-center space-x-3 cursor-pointer flex-grow">
                                <input type="checkbox" name="selected_accounts[]" value="{{ $acc->id }}" {{ $isSelected ? 'checked' : '' }}
                                       class="account-checkbox rounded text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-gray-800 border-slate-300 dark:border-gray-700 w-4 h-4"
                                       onchange="toggleTargetRow({{ $acc->id }})">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-600/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-base flex-shrink-0">
                                        <i class="fa-brands fa-facebook-f"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-900 dark:text-white text-xs block leading-tight">{{ $acc->page_name }}</span>
                                        <span class="text-[11px] text-slate-500 dark:text-gray-400">
                                            @if($acc->ig_username)
                                                <i class="fa-brands fa-instagram text-pink-500 ml-0.5 mr-1"></i>&#64;{{ $acc->ig_username }}
                                            @else
                                                <span class="text-slate-400 dark:text-gray-500 italic">Tanpa Akun Instagram</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </label>

                            <div id="platformControl_{{ $acc->id }}" class="{{ $isSelected ? 'flex' : 'hidden' }} items-center space-x-2 pl-7 sm:pl-0">
                                <span class="text-[11px] text-slate-500 dark:text-gray-400 font-medium">Platform:</span>
                                <select name="platform_targets[{{ $acc->id }}]" class="bg-white dark:bg-gray-950 border border-slate-300 dark:border-gray-700 rounded-lg px-3 py-1.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                                    <option value="both" {{ $selectedPlatform === 'both' ? 'selected' : '' }}>Both (FB Page & Instagram)</option>
                                    <option value="instagram_only" {{ $selectedPlatform === 'instagram_only' ? 'selected' : '' }}>Instagram Saja (Skip FB)</option>
                                    <option value="facebook_only" {{ $selectedPlatform === 'facebook_only' ? 'selected' : '' }}>Facebook Page Saja (Skip IG)</option>
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 5. Moda Pengulangan (Repeat Mode) -->
            <div class="space-y-3">
                <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider">Moda Pengulangan Posting (Repeat Mode)</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                    <label class="flex items-start space-x-3 bg-slate-50 dark:bg-gray-900 p-4 rounded-lg border border-slate-200 dark:border-gray-700 cursor-pointer hover:border-indigo-500 transition">
                        <input type="radio" name="repeat_type" value="continuous" {{ $project->repeat_type === 'continuous' ? 'checked' : '' }} class="mt-0.5 text-indigo-600 focus:ring-indigo-500" onchange="toggleRepeatFields()">
                        <div>
                            <strong class="text-slate-900 dark:text-white block font-semibold">♾️ Kontinu Selamanya</strong>
                            <span class="text-slate-500 dark:text-gray-400 text-[11px]">Tayang setiap hari tanpa henti secara otomatis.</span>
                        </div>
                    </label>

                    <label class="flex items-start space-x-3 bg-slate-50 dark:bg-gray-900 p-4 rounded-lg border border-slate-200 dark:border-gray-700 cursor-pointer hover:border-indigo-500 transition">
                        <input type="radio" name="repeat_type" value="once" {{ $project->repeat_type === 'once' ? 'checked' : '' }} class="mt-0.5 text-indigo-600 focus:ring-indigo-500" onchange="toggleRepeatFields()">
                        <div>
                            <strong class="text-slate-900 dark:text-white block font-semibold">🎯 Hanya 1x Post</strong>
                            <span class="text-slate-500 dark:text-gray-400 text-[11px]">Posting 1 kali saja pada tanggal yang dipilih.</span>
                        </div>
                    </label>

                    <label class="flex items-start space-x-3 bg-slate-50 dark:bg-gray-900 p-4 rounded-lg border border-slate-200 dark:border-gray-700 cursor-pointer hover:border-indigo-500 transition">
                        <input type="radio" name="repeat_type" value="until_date" {{ $project->repeat_type === 'until_date' ? 'checked' : '' }} class="mt-0.5 text-indigo-600 focus:ring-indigo-500" onchange="toggleRepeatFields()">
                        <div>
                            <strong class="text-slate-900 dark:text-white block font-semibold">📅 Sampai Tanggal Tertentu</strong>
                            <span class="text-slate-500 dark:text-gray-400 text-[11px]">Berulang harian hingga tanggal akhir campaign.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Dynamic Date Inputs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="dateInputsContainer">
                <div id="startDateWrapper">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider mb-2" id="startDateLabel">
                        Mulai Tanggal Berapa (Posting Perdana) <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="start_date" id="inputStartDate" value="{{ $project->start_date ? $project->start_date->format('Y-m-d') : date('Y-m-d') }}" required
                           class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                    <p id="startDateHelp" class="text-[11px] text-slate-500 dark:text-gray-400 mt-1.5 flex items-center space-x-1.5">
                        <i class="fa-regular fa-calendar text-indigo-600 dark:text-indigo-400"></i>
                        <span>Jadwal posting harian akan mulai dibuat dari tanggal ini ke depan secara otomatis.</span>
                    </p>
                </div>

                <div id="endDateWrapper" class="hidden">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                        Sampai Tanggal Berapa (Tanggal Berakhir) <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="end_date" value="{{ $project->end_date ? $project->end_date->format('Y-m-d') : date('Y-m-d', strtotime('+30 days')) }}" 
                           class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                    <p class="text-[11px] text-slate-500 dark:text-gray-400 mt-1.5 flex items-center space-x-1.5">
                        <i class="fa-regular fa-calendar-xmark text-rose-500"></i>
                        <span>Campaign akan otomatis berhenti posting setelah tanggal ini.</span>
                    </p>
                </div>
            </div>

            <!-- Jam Tayang Harian -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider mb-2">Jam Tayang Posting Harian (HH:mm WIB)</label>
                <input type="time" name="target_time" id="inputTargetTime" value="{{ $project->target_time }}" required 
                       class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                <p id="onceTimeNotice" class="hidden text-[11px] text-amber-600 dark:text-amber-400 mt-1.5 flex items-center space-x-1">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Khusus mode 1x Post: Waktu tayang minimal <strong>30 menit dari jam sekarang</strong>.</span>
                </p>
            </div>

            <!-- Exclude Days -->
            @php
                $exclude = $project->exclude_days ?? [];
            @endphp
            <div id="excludeDaysWrapper">
                <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider mb-2">Kecualikan Hari (Jangan Posting Pada Hari Ini)</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-3 text-xs">
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
                        <label class="flex items-center space-x-2 bg-slate-50 dark:bg-gray-900 p-3 rounded-lg border border-slate-200 dark:border-gray-800 cursor-pointer hover:border-indigo-500 dark:hover:border-gray-700 transition text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="exclude_days[]" value="{{ $val }}" {{ in_array($val, $exclude) ? 'checked' : '' }} class="rounded text-indigo-600 bg-white dark:bg-gray-800 border-slate-300 dark:border-gray-700">
                            <span>{{ $dayName }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Existing Media Pool Preview -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider">Media Pool Saat Ini ({{ $project->mediaFiles->count() }} file)</label>
                </div>
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
                    @foreach($project->mediaFiles as $media)
                        <div class="group relative bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-800 rounded-lg overflow-hidden shadow cursor-pointer transition hover:border-indigo-500"
                             onclick="openLightboxDirect('{{ $media->url }}', {{ $media->is_video ? 'true' : 'false' }}, '{{ $media->original_name }}')">
                            @if($media->is_video)
                                <div class="w-full h-20 bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <i class="fa-solid fa-video text-lg"></i>
                                </div>
                            @else
                                <img src="{{ $media->url }}" class="w-full h-20 object-cover group-hover:scale-105 transition duration-300">
                            @endif
                            <div class="p-1.5 bg-slate-100 dark:bg-gray-950/90 text-[9px] text-slate-600 dark:text-gray-400 truncate font-mono border-t border-slate-200 dark:border-gray-800/60">
                                {{ $media->original_name }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Upload Additional Media -->
            <div class="space-y-3">
                <label class="block text-xs font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider">Tambah File Media Baru (Opsional)</label>
                <div class="border-2 border-dashed border-slate-300 dark:border-gray-700 hover:border-indigo-500 rounded-xl p-4 text-center bg-slate-50/60 dark:bg-gray-900/60 transition">
                    <input type="file" name="media_files[]" multiple accept="image/jpeg,image/png,video/mp4,video/quicktime" id="inputEditMediaFiles" class="hidden">
                    <label for="inputEditMediaFiles" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-200 text-xs font-semibold rounded-lg border border-slate-300 dark:border-gray-700 cursor-pointer transition inline-block">
                        <i class="fa-solid fa-cloud-arrow-up mr-1.5 text-indigo-600 dark:text-indigo-400"></i>Pilih File Baru Untuk Ditambahkan
                    </label>
                    <p class="text-[11px] text-slate-500 dark:text-gray-400 mt-1" id="editFileNotice">File baru akan digabung ke Media Pool yang sudah ada</p>
                </div>
            </div>

            <!-- Form Action Buttons -->
            <div class="pt-6 border-t border-slate-200 dark:border-gray-800 flex items-center justify-end space-x-4">
                <a href="{{ route('projects.show', $project->id) }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 font-semibold rounded-lg text-xs transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition shadow-md flex items-center space-x-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Perubahan Campaign</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleTargetRow(accId) {
        const checkbox = document.querySelector(`input[value="${accId}"]`);
        const ctrl = document.getElementById(`platformControl_${accId}`);
        if (checkbox && ctrl) {
            if (checkbox.checked) {
                ctrl.classList.remove('hidden');
                ctrl.classList.add('flex');
            } else {
                ctrl.classList.add('hidden');
                ctrl.classList.remove('flex');
            }
        }
    }

    function toggleRepeatFields() {
        const repeatType = document.querySelector('input[name="repeat_type"]:checked').value;
        const startWrapper = document.getElementById('startDateWrapper');
        const endWrapper = document.getElementById('endDateWrapper');
        const startLabel = document.getElementById('startDateLabel');
        const startHelp = document.getElementById('startDateHelp');
        const excludeDaysWrapper = document.getElementById('excludeDaysWrapper');
        const onceNotice = document.getElementById('onceTimeNotice');

        // Mulai Tanggal Berapa SELALU TAMPIL
        startWrapper.classList.remove('hidden');

        if (repeatType === 'continuous') {
            endWrapper.classList.add('hidden');
            startLabel.innerHTML = 'Mulai Tanggal Berapa (Posting Perdana) <span class="text-rose-400">*</span>';
            startHelp.innerHTML = '<i class="fa-regular fa-calendar text-indigo-400 mr-1"></i>Jadwal posting harian akan mulai dibuat dari tanggal ini ke depan secara otomatis.';
            excludeDaysWrapper.classList.remove('hidden');
            onceNotice.classList.add('hidden');
        } else if (repeatType === 'once') {
            endWrapper.classList.add('hidden');
            startLabel.innerHTML = 'Tanggal Tayang (Hanya 1x Post) <span class="text-rose-400">*</span>';
            startHelp.innerHTML = '<i class="fa-regular fa-clock text-amber-400 mr-1"></i>Konten hanya akan dipublikasikan satu kali pada tanggal ini.';
            excludeDaysWrapper.classList.add('hidden');
            onceNotice.classList.remove('hidden');
        } else if (repeatType === 'until_date') {
            endWrapper.classList.remove('hidden');
            startLabel.innerHTML = 'Mulai Tanggal Berapa <span class="text-rose-400">*</span>';
            startHelp.innerHTML = '<i class="fa-regular fa-calendar text-indigo-400 mr-1"></i>Tanggal dimulainya jadwal posting harian.';
            excludeDaysWrapper.classList.remove('hidden');
            onceNotice.classList.add('hidden');
        }
    }

    toggleRepeatFields();

    document.getElementById('inputEditMediaFiles').addEventListener('change', function() {
        const count = this.files.length;
        document.getElementById('editFileNotice').textContent = count > 0 ? `👍 ${count} file baru dipilih dan siap diunggah` : 'File baru akan digabung ke Media Pool';
    });

    document.getElementById('formEditProject').addEventListener('submit', function(e) {
        e.preventDefault();

        const selectedAccounts = Array.from(document.querySelectorAll('.account-checkbox:checked'));
        if (selectedAccounts.length === 0) {
            showAlert('warning', 'Pilih Target Akun', 'Silakan centang minimal 1 akun target Meta.');
            return;
        }

        const formData = new FormData(this);
        formData.append('_method', 'PUT');

        selectedAccounts.forEach((cb, idx) => {
            const accId = cb.value;
            const platformSelect = document.querySelector(`select[name="platform_targets[${accId}]"]`);
            const platformVal = platformSelect ? platformSelect.value : 'both';

            formData.append(`targets[${idx}][account_id]`, accId);
            formData.append(`targets[${idx}][platform_target]`, platformVal);
        });

        showLoading('Menyimpan Perubahan...', 'Memperbarui konfigurasi campaign...');

        fetch("{{ route('projects.update', $project->id) }}", {
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
                showAlert('success', 'Campaign Diperbarui!', data.message);
                setTimeout(() => window.location.href = data.redirect || "{{ route('projects.show', $project->id) }}", 1500);
            } else {
                showAlert('error', 'Gagal Memperbarui', data.message);
            }
        })
        .catch(err => {
            showAlert('error', 'Kesalahan Sistem', err.message);
        });
    });
</script>
@endsection
