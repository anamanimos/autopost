@php
    $modalAccounts = \App\Models\ConnectedAccount::where('is_active', true)->orderBy('page_name')->get();
    $modalRecentMedia = \App\Models\MediaFile::latest()->take(24)->get();
@endphp

<div x-data="directPostModal()" 
     x-show="isOpen" 
     @open-direct-post-modal.window="openModal()"
     @keydown.escape.window="if(!isSubmitting) closeModal()"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="direct-post-modal-title" 
     role="dialog" 
     aria-modal="true">

    <!-- Backdrop -->
    <div x-show="isOpen" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity"
         @click="if(!isSubmitting) closeModal()"></div>

    <!-- Modal Panel -->
    <div class="flex min-h-screen items-center justify-center p-3 sm:p-4 text-center">
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative w-full max-w-2xl transform rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-gray-800 text-left shadow-2xl transition-all overflow-hidden flex flex-col max-h-[92vh]">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-gray-800 bg-slate-50/70 dark:bg-slate-850/60 flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/20">
                        <i class="fa-solid fa-paper-plane text-base"></i>
                    </div>
                    <div>
                        <h3 id="direct-post-modal-title" class="text-base font-bold text-slate-900 dark:text-white">
                            Post Langsung ke Meta
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Terbitkan konten secara instan ke Instagram dan Facebook Page tanpa penjadwalan.
                        </p>
                    </div>
                </div>
                <button type="button" 
                        @click="closeModal()" 
                        :disabled="isSubmitting"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 p-2 rounded-lg transition min-h-[44px] min-w-[44px] flex items-center justify-center disabled:opacity-40"
                        title="Tutup Modal">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <form @submit.prevent="submitDirectPost" class="overflow-y-auto p-5 space-y-5 flex-1 text-xs">

                <!-- Section 1: Target Akun & Platform -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="font-bold text-slate-800 dark:text-gray-200 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-solid fa-users text-indigo-500"></i>
                            <span>Target Akun Terhubung</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[10px] text-slate-400 dark:text-gray-500" x-text="selectedAccountsCount + ' akun dipilih'"></span>
                    </div>

                    @if($modalAccounts->isEmpty())
                        <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                            Belum ada akun Meta terhubung. Silakan hubungkan akun terlebih dahulu di halaman <a href="{{ route('settings.index', ['tab' => 'meta']) }}" class="underline font-semibold">Pengaturan Meta</a>.
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-48 overflow-y-auto pr-1">
                            @foreach($modalAccounts as $acc)
                                @php
                                    $hasIg = !empty($acc->ig_user_id) || !empty($acc->instagram_business_id);
                                    $hasFb = !empty($acc->page_id) || !empty($acc->facebook_page_id);
                                    $hasThreads = $acc->hasThreads();
                                    $defaultPlatform = $hasThreads ? 'all' : 'both';
                                @endphp
                                <div class="p-3 rounded-lg border transition-all text-xs flex flex-col justify-between gap-2"
                                     :class="isAccountSelected({{ $acc->id }}) ? 'bg-indigo-50/50 dark:bg-indigo-950/30 border-indigo-500 ring-1 ring-indigo-500/50' : 'bg-slate-50/60 dark:bg-gray-800/40 border-slate-200 dark:border-gray-700 hover:border-slate-300 dark:hover:border-gray-600'">
                                    
                                    <label class="flex items-start space-x-2.5 cursor-pointer select-none">
                                        <input type="checkbox" 
                                               value="{{ $acc->id }}" 
                                               @change="toggleAccount({{ $acc->id }}, '{{ $defaultPlatform }}')"
                                               :checked="isAccountSelected({{ $acc->id }})"
                                               class="mt-0.5 rounded border-slate-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold text-slate-800 dark:text-gray-100 truncate text-xs">{{ $acc->page_name }}</div>
                                            <div class="flex items-center gap-2 mt-0.5 text-[10px]">
                                                @if($hasIg)
                                                    <span class="inline-flex items-center text-pink-600 dark:text-pink-400 font-medium">
                                                        <i class="fa-brands fa-instagram mr-1"></i> IG
                                                    </span>
                                                @endif
                                                @if($hasFb)
                                                    <span class="inline-flex items-center text-blue-600 dark:text-blue-400 font-medium">
                                                        <i class="fa-brands fa-facebook mr-1"></i> FB
                                                    </span>
                                                @endif
                                                @if($hasThreads)
                                                    <span class="inline-flex items-center text-slate-700 dark:text-slate-300 font-medium">
                                                        <i class="fa-brands fa-threads mr-1"></i> Threads
                                                    </span>
                                                @endif
                                                @if(!$hasIg && !$hasFb && !$hasThreads)
                                                    <span class="text-slate-400 italic">Belum terhubung</span>
                                                @endif
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Platform target picker per account -->
                                    <div x-show="isAccountSelected({{ $acc->id }})" x-transition class="pt-2 border-t border-slate-200 dark:border-gray-700/80">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="text-slate-500 dark:text-gray-400">Target:</span>
                                            <select @change="updatePlatformTarget({{ $acc->id }}, $event.target.value)" 
                                                    class="py-1 px-2 text-[11px] rounded bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-200 focus:outline-none focus:border-indigo-500">
                                                @if($hasThreads)
                                                    <option value="all">Semua (FB, IG & Threads)</option>
                                                    <option value="both">Facebook & Instagram</option>
                                                    <option value="threads_only">Threads Saja</option>
                                                    <option value="ig_threads">Instagram & Threads</option>
                                                    <option value="fb_threads">Facebook & Threads</option>
                                                    @if($hasIg) <option value="instagram_only">Instagram Saja</option> @endif
                                                    @if($hasFb) <option value="facebook_only">Facebook Saja</option> @endif
                                                @elseif($hasIg && $hasFb)
                                                    <option value="both">Keduanya (FB & IG)</option>
                                                    <option value="instagram_only">Instagram Saja</option>
                                                    <option value="facebook_only">Facebook Saja</option>
                                                @elseif($hasIg)
                                                    <option value="instagram_only">Instagram Saja</option>
                                                @elseif($hasFb)
                                                    <option value="facebook_only">Facebook Saja</option>
                                                @else
                                                    <option value="both">Keduanya</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Section 2: Tipe Konten -->
                <div class="space-y-2">
                    <label class="font-bold text-slate-800 dark:text-gray-200 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-solid fa-shapes text-indigo-500"></i>
                        <span>Tipe Konten Meta</span>
                        <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="p-3 rounded-lg border cursor-pointer flex items-center space-x-3 transition min-h-[44px]"
                               :class="contentType === 'post' ? 'bg-indigo-50/60 dark:bg-indigo-950/40 border-indigo-500 ring-1 ring-indigo-500/50' : 'bg-slate-50/50 dark:bg-gray-800/40 border-slate-200 dark:border-gray-700 hover:border-slate-300 dark:hover:border-gray-600'">
                            <input type="radio" name="direct_content_type" value="post" x-model="contentType" class="text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white block text-xs">
                                    <i class="fa-solid fa-square-rss text-indigo-500 mr-1"></i> Feed Post
                                </span>
                                <span class="text-[10px] text-slate-500 dark:text-gray-400">Postingan beranda dengan caption</span>
                            </div>
                        </label>

                        <label class="p-3 rounded-lg border cursor-pointer flex items-center space-x-3 transition min-h-[44px]"
                               :class="contentType === 'story' ? 'bg-indigo-50/60 dark:bg-indigo-950/40 border-indigo-500 ring-1 ring-indigo-500/50' : 'bg-slate-50/50 dark:bg-gray-800/40 border-slate-200 dark:border-gray-700 hover:border-slate-300 dark:hover:border-gray-600'">
                            <input type="radio" name="direct_content_type" value="story" x-model="contentType" class="text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white block text-xs">
                                    <i class="fa-solid fa-circle-notch text-pink-500 mr-1"></i> Story
                                </span>
                                <span class="text-[10px] text-slate-500 dark:text-gray-400">Cerita 24 jam (vertikal 9:16)</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Section 3: Media Upload / Selection -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="font-bold text-slate-800 dark:text-gray-200 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-solid fa-photo-film text-indigo-500"></i>
                            <span>Media Konten (Foto / Video)</span>
                            <span class="text-rose-500">*</span>
                        </label>

                        <!-- Source Switcher -->
                        <div class="flex items-center rounded-lg bg-slate-100 dark:bg-gray-800 p-0.5 text-[11px]">
                            <button type="button" 
                                    @click="mediaSource = 'upload'"
                                    :class="mediaSource === 'upload' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm font-bold' : 'text-slate-500 dark:text-gray-400'"
                                    class="px-2.5 py-1 rounded-md transition">
                                Upload Baru
                            </button>
                            <button type="button" 
                                    @click="mediaSource = 'library'; loadLibraryMedia()"
                                    :class="mediaSource === 'library' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm font-bold' : 'text-slate-500 dark:text-gray-400'"
                                    class="px-2.5 py-1 rounded-md transition">
                                Media Library
                            </button>
                        </div>
                    </div>

                    <!-- Mode A: Upload Baru Dropzone -->
                    <div x-show="mediaSource === 'upload'" class="space-y-3">
                        <div x-show="!previewUrl" 
                             @dragover.prevent="isDragging = true"
                             @dragleave.prevent="isDragging = false"
                             @drop.prevent="handleFileDrop($event)"
                             :class="isDragging ? 'border-indigo-500 bg-indigo-50/30 dark:bg-indigo-950/20' : 'border-slate-300 dark:border-gray-700 bg-slate-50/50 dark:bg-gray-800/30'"
                             class="border-2 border-dashed rounded-xl p-6 text-center transition cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500"
                             @click="$refs.fileInput.click()">
                            
                            <input type="file" 
                                   x-ref="fileInput" 
                                   @change="handleFileSelect($event)" 
                                   accept="image/jpeg,image/png,image/jpg,video/mp4,video/quicktime"
                                   class="hidden">
                            
                            <div class="space-y-2">
                                <div class="w-12 h-12 mx-auto rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                </div>
                                <div class="text-xs">
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">Klik untuk memilih file</span>
                                    <span class="text-slate-500 dark:text-gray-400"> atau seret ke area ini</span>
                                </div>
                                <p class="text-[10px] text-slate-400 dark:text-gray-500">
                                    Format: JPG, PNG, MP4, MOV (Maksimal 50MB)
                                </p>
                            </div>
                        </div>

                        <!-- Preview Area for Uploaded File -->
                        <div x-show="previewUrl" class="relative rounded-xl overflow-hidden border border-slate-200 dark:border-gray-700 bg-slate-900 p-2 flex items-center justify-center max-h-56">
                            <template x-if="!isVideo">
                                <img :src="previewUrl" class="max-h-52 max-w-full object-contain rounded-lg">
                            </template>
                            <template x-if="isVideo">
                                <video :src="previewUrl" controls class="max-h-52 max-w-full rounded-lg"></video>
                            </template>

                            <!-- File details overlay -->
                            <div class="absolute top-3 left-3 bg-slate-950/80 backdrop-blur-sm text-white px-2.5 py-1 rounded text-[10px] font-mono flex items-center space-x-2">
                                <i :class="isVideo ? 'fa-solid fa-video text-purple-400' : 'fa-solid fa-image text-emerald-400'"></i>
                                <span x-text="fileName" class="truncate max-w-[150px]"></span>
                                <span class="text-slate-400" x-text="fileSizeFormatted"></span>
                            </div>

                            <!-- Remove button -->
                            <button type="button" 
                                    @click="clearFileSelection()" 
                                    class="absolute top-3 right-3 w-8 h-8 rounded-lg bg-rose-600/90 hover:bg-rose-600 text-white flex items-center justify-center shadow-lg transition"
                                    title="Hapus File">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Mode B: Pilih dari Media Library -->
                    <div x-show="mediaSource === 'library'" class="space-y-3">
                        <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-gray-400">
                            <span>Pilih salah satu media dari library yang telah diunggah:</span>
                            <button type="button" @click="loadLibraryMedia(true)" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                <i class="fa-solid fa-rotate text-[10px]"></i> Segarkan
                            </button>
                        </div>

                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 max-h-48 overflow-y-auto p-1 border border-slate-200 dark:border-gray-700 rounded-lg bg-slate-50/50 dark:bg-gray-800/30">
                            <template x-for="m in libraryMedia" :key="m.id">
                                <div @click="selectLibraryMedia(m)" 
                                     :class="selectedMediaId === m.id ? 'ring-2 ring-indigo-500 border-indigo-500 scale-95' : 'hover:border-slate-400 dark:hover:border-gray-500'"
                                     class="relative aspect-square rounded-lg overflow-hidden border border-slate-200 dark:border-gray-700 cursor-pointer transition bg-slate-900 group">
                                    <template x-if="m.media_type !== 'video'">
                                        <img :src="m.url" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="m.media_type === 'video'">
                                        <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-slate-950/80">
                                            <i class="fa-solid fa-circle-play text-xl text-indigo-400"></i>
                                            <span class="text-[9px] mt-1 font-mono">VIDEO</span>
                                        </div>
                                    </template>

                                    <!-- Selected Check Badge -->
                                    <div x-show="selectedMediaId === m.id" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] shadow">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                </div>
                            </template>

                            <div x-show="libraryMedia.length === 0" class="col-span-full py-8 text-center text-slate-400">
                                <i class="fa-regular fa-image text-2xl mb-1 block"></i>
                                <span>Belum ada media di library. Silakan unggah baru.</span>
                            </div>
                        </div>

                        <!-- Selected media preview info -->
                        <div x-show="selectedMediaId && selectedMediaObject" class="p-2 rounded-lg bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2 truncate">
                                <i :class="selectedMediaObject?.media_type === 'video' ? 'fa-solid fa-video text-purple-500' : 'fa-solid fa-image text-emerald-500'"></i>
                                <span class="font-medium text-slate-800 dark:text-gray-200 truncate" x-text="selectedMediaObject?.original_name"></span>
                            </div>
                            <button type="button" @click="selectedMediaId = null; selectedMediaObject = null" class="text-rose-500 hover:underline text-[11px]">
                                Batalkan
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Caption / Deskripsi -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="font-bold text-slate-800 dark:text-gray-200 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-solid fa-comment-dots text-indigo-500"></i>
                            <span>Caption / Teks Postingan</span>
                        </label>
                        <span class="text-[10px] text-slate-400 dark:text-gray-500" x-text="caption.length + ' karakter'"></span>
                    </div>
                    <textarea x-model="caption" 
                              rows="3" 
                              placeholder="Tuliskan caption untuk postingan Anda..." 
                              class="w-full p-2.5 rounded-lg border border-slate-200 dark:border-gray-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-gray-200 placeholder-slate-400 focus:outline-none focus:border-indigo-500 resize-y text-xs"></textarea>
                    <p class="text-[10px] text-slate-400 dark:text-gray-500">
                        Catatan: Pada Story Instagram, Meta API tidak menampilkan teks caption.
                    </p>
                </div>

                <!-- Section 5: Nama Campaign (Opsional) -->
                <div class="space-y-1.5">
                    <label class="font-bold text-slate-800 dark:text-gray-200 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-solid fa-tag text-indigo-500"></i>
                        <span>Label / Nama Riwayat (Opsional)</span>
                    </label>
                    <input type="text" 
                           x-model="campaignName" 
                           placeholder="Contoh: Promo Kilat Siang Ini (Kosongkan untuk penamaan otomatis)" 
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-gray-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-gray-200 placeholder-slate-400 focus:outline-none focus:border-indigo-500 text-xs">
                </div>

                <!-- Progress / Status Warning -->
                <div x-show="errorMessage" x-transition class="p-3 rounded-lg bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs flex items-start space-x-2">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <span x-text="errorMessage"></span>
                </div>

                <!-- Modal Footer Actions -->
                <div class="pt-3 border-t border-slate-200 dark:border-gray-800 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5">
                    <button type="button" 
                            @click="closeModal()" 
                            :disabled="isSubmitting"
                            class="w-full sm:w-auto px-4 py-2.5 rounded-lg border border-slate-300 dark:border-gray-700 text-slate-700 dark:text-gray-300 font-semibold text-xs hover:bg-slate-100 dark:hover:bg-gray-800 transition min-h-[44px] flex items-center justify-center disabled:opacity-40">
                        Batal
                    </button>

                    <button type="submit" 
                            :disabled="isSubmitting || selectedAccountsCount === 0 || (!fileToUpload && !selectedMediaId)"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/25 transition min-h-[44px] flex items-center justify-center space-x-2 disabled:opacity-40 disabled:cursor-not-allowed">
                        <template x-if="!isSubmitting">
                            <span class="flex items-center space-x-2">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Terbitkan Sekarang</span>
                            </span>
                        </template>
                        <template x-if="isSubmitting">
                            <span class="flex items-center space-x-2">
                                <i class="fa-solid fa-circle-notch fa-spin"></i>
                                <span>Sedang Menerbitkan ke Meta...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function directPostModal() {
        return {
            isOpen: false,
            isSubmitting: false,
            mediaSource: 'upload', // 'upload' or 'library'
            isDragging: false,
            fileToUpload: null,
            previewUrl: null,
            fileName: '',
            fileSizeFormatted: '',
            isVideo: false,
            selectedMediaId: null,
            selectedMediaObject: null,
            contentType: 'post',
            caption: '',
            campaignName: '',
            errorMessage: '',
            selectedTargets: {}, // { account_id: 'both'|'instagram_only'|'facebook_only' }
            libraryMedia: [
                @foreach($modalRecentMedia as $rm)
                {
                    id: {{ $rm->id }},
                    original_name: '{{ addslashes($rm->original_name) }}',
                    url: '{{ $rm->url }}',
                    media_type: '{{ $rm->media_type }}',
                    file_size: {{ $rm->file_size ?: 0 }}
                },
                @endforeach
            ],

            init() {
                // Default target setup: select first account if available
                @if($modalAccounts->isNotEmpty())
                    this.selectedTargets[{{ $modalAccounts->first()->id }}] = 'both';
                @endif
            },

            openModal() {
                this.isOpen = true;
                this.errorMessage = '';
                document.body.classList.add('overflow-hidden');
            },

            closeModal() {
                this.isOpen = false;
                document.body.classList.remove('overflow-hidden');
            },

            get selectedAccountsCount() {
                return Object.keys(this.selectedTargets).length;
            },

            isAccountSelected(accountId) {
                return accountId in this.selectedTargets;
            },

            toggleAccount(accountId, defaultPlatform = 'both') {
                if (accountId in this.selectedTargets) {
                    delete this.selectedTargets[accountId];
                } else {
                    this.selectedTargets[accountId] = defaultPlatform;
                }
            },

            updatePlatformTarget(accountId, platform) {
                if (accountId in this.selectedTargets) {
                    this.selectedTargets[accountId] = platform;
                }
            },

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    this.processFile(file);
                }
            },

            handleFileDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer.files[0];
                if (file) {
                    this.processFile(file);
                }
            },

            processFile(file) {
                if (file.size > 50 * 1024 * 1024) {
                    this.errorMessage = 'Ukuran file melebihi batas maksimal 50MB.';
                    return;
                }
                this.fileToUpload = file;
                this.fileName = file.name;
                this.fileSizeFormatted = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                this.isVideo = file.type.startsWith('video/') || /\.(mp4|mov)$/i.test(file.name);
                this.previewUrl = URL.createObjectURL(file);
                this.selectedMediaId = null;
                this.selectedMediaObject = null;
                this.errorMessage = '';
            },

            clearFileSelection() {
                this.fileToUpload = null;
                if (this.previewUrl) {
                    URL.revokeObjectURL(this.previewUrl);
                    this.previewUrl = null;
                }
                this.fileName = '';
                this.fileSizeFormatted = '';
                this.isVideo = false;
                if (this.$refs.fileInput) {
                    this.$refs.fileInput.value = '';
                }
            },

            selectLibraryMedia(media) {
                this.selectedMediaId = media.id;
                this.selectedMediaObject = media;
                this.fileToUpload = null;
                this.previewUrl = null;
                this.errorMessage = '';
            },

            loadLibraryMedia(force = false) {
                if (this.libraryMedia.length > 0 && !force) return;
                fetch("{{ route('schedules.recentMedia') }}", {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.media) {
                        this.libraryMedia = data.media;
                    }
                })
                .catch(err => console.error('Gagal memuat media library:', err));
            },

            submitDirectPost() {
                this.errorMessage = '';
                if (this.selectedAccountsCount === 0) {
                    this.errorMessage = 'Pilih minimal satu akun target untuk menerbitkan.';
                    return;
                }

                if (!this.fileToUpload && !this.selectedMediaId) {
                    this.errorMessage = 'Pilih atau unggah file media (foto/video).';
                    return;
                }

                const formData = new FormData();
                if (this.campaignName.trim() !== '') {
                    formData.append('name', this.campaignName.trim());
                }
                formData.append('content_type', this.contentType);
                formData.append('caption', this.caption);

                if (this.fileToUpload) {
                    formData.append('media_file', this.fileToUpload);
                } else if (this.selectedMediaId) {
                    formData.append('existing_media_id', this.selectedMediaId);
                }

                let targetIndex = 0;
                for (const [accId, platform] of Object.entries(this.selectedTargets)) {
                    formData.append(`targets[${targetIndex}][account_id]`, accId);
                    formData.append(`targets[${targetIndex}][platform_target]`, platform);
                    targetIndex++;
                }

                this.isSubmitting = true;

                Swal.fire({
                    title: 'Menerbitkan Konten Langsung...',
                    html: `
                        <div class="space-y-3 text-center py-2">
                            <div class="w-12 h-12 mx-auto rounded-full border-4 border-indigo-600 border-t-transparent animate-spin"></div>
                            <p class="text-xs text-slate-600 dark:text-gray-300">
                                Mengirim media dan memproses penerbitan via Meta Graph API...
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

                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                fetch("{{ route('schedules.directPost') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json().then(data => ({ status: res.status, data })))
                .then(({ status, data }) => {
                    this.isSubmitting = false;

                    if (data.success) {
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
                            title: 'Berhasil Diterbitkan!',
                            html: `<div class="text-xs text-slate-600 dark:text-gray-300">${data.message}${logDetails}</div>`,
                            confirmButtonColor: '#4f46e5',
                            confirmButtonText: 'Selesai',
                            customClass: {
                                popup: 'swal2-popup-dark',
                                title: 'swal2-title-dark',
                                htmlContainer: 'swal2-html-dark'
                            }
                        }).then(() => {
                            this.closeModal();
                            window.location.reload();
                        });
                    } else {
                        const errMsg = data.message || 'Terjadi kesalahan saat memproses penerbitan.';
                        this.errorMessage = errMsg;
                        Swal.fire({
                            icon: 'error',
                            title: 'Penerbitan Gagal',
                            text: errMsg,
                            confirmButtonColor: '#4f46e5',
                            confirmButtonText: 'Tutup',
                            customClass: {
                                popup: 'swal2-popup-dark',
                                title: 'swal2-title-dark',
                                htmlContainer: 'swal2-html-dark'
                            }
                        });
                    }
                })
                .catch(err => {
                    this.isSubmitting = false;
                    const msg = 'Koneksi terganggu atau terjadi error: ' + err.message;
                    this.errorMessage = msg;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Jaringan',
                        text: msg,
                        confirmButtonColor: '#4f46e5',
                        customClass: {
                            popup: 'swal2-popup-dark',
                            title: 'swal2-title-dark',
                            htmlContainer: 'swal2-html-dark'
                        }
                    });
                });
            }
        };
    }
</script>
