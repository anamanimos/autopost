@extends('layouts.app')

@section('title', 'Integrasi Meta API (Instagram & Facebook)')

@section('content')
@php
    $hasToken = !empty($credential->user_access_token) || !empty($credential->system_user_token);
    $isConnected = $credential->token_status === 'valid' && $hasToken;
    $hasAppId = !empty($credential->app_id);
@endphp

<div class="w-full space-y-6" x-data="{ 
    showGuide: {{ $isConnected ? 'false' : 'true' }},
    authMode: 'oauth',
    currentTab: '{{ $isConnected ? 'accounts' : 'connect' }}'
}">

    <!-- Header & Status Ringkas -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-gray-800 pb-5">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center space-x-3">
                <div class="p-2.5 bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-500 rounded-lg text-white shadow-md shadow-indigo-600/20">
                    <i class="fa-brands fa-meta text-lg"></i>
                </div>
                <span>Integrasi Meta API Resmi</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">
                Koneksi resmi ke Facebook Page & Instagram Business untuk otomasi posting tanpa emulator / browser headless.
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
                <span>Uji Koneksi</span>
            </button>
        </div>
    </div>

    <!-- Tab Navigasi Utama (Tepat di bawah Header Integrasi Meta API) -->
    <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-gray-800 text-xs font-semibold">
        <button type="button" @click="currentTab = 'connect'"
                :class="currentTab === 'connect' ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-500 bg-slate-100/70 dark:bg-gray-900/50' : 'text-slate-500 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/50 dark:hover:bg-gray-900/30'"
                class="px-4 py-3 rounded-t-lg transition flex items-center space-x-2">
            <i class="fa-solid fa-key text-xs"></i>
            <span>1. Koneksi & Kredensial App</span>
        </button>

        <button type="button" @click="currentTab = 'accounts'"
                :class="currentTab === 'accounts' ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-500 bg-slate-100/70 dark:bg-gray-900/50' : 'text-slate-500 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/50 dark:hover:bg-gray-900/30'"
                class="px-4 py-3 rounded-t-lg transition flex items-center space-x-2">
            <i class="fa-solid fa-users text-xs"></i>
            <span>2. Halaman & Akun Instagram Terhubung</span>
            <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold">{{ $accounts->count() }}</span>
        </button>

        <button type="button" @click="currentTab = 'advanced'"
                :class="currentTab === 'advanced' ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-500 bg-slate-100/70 dark:bg-gray-900/50' : 'text-slate-500 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/50 dark:hover:bg-gray-900/30'"
                class="px-4 py-3 rounded-t-lg transition flex items-center space-x-2">
            <i class="fa-solid fa-clock-rotate-left text-xs"></i>
            <span>3. Riwayat & Pengaturan Lanjutan</span>
        </button>
    </div>

    <!-- Status Banner Utama -->
    @if($isConnected)
        <div class="bg-emerald-50/90 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800/80 rounded-xl p-5 shadow-sm dark:shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start space-x-3.5">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 border border-emerald-300 text-emerald-700 dark:bg-emerald-500/20 dark:border-emerald-500/30 dark:text-emerald-400 flex items-center justify-center text-lg flex-shrink-0 mt-0.5">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <h2 class="text-sm font-bold text-emerald-950 dark:text-white">Meta API Terhubung & Siap Digunakan</h2>
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
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-lg shadow-md transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-rotate"></i>
                    <span>Tarik / Sync Akun Terbaru</span>
                </button>
            </div>
        </div>
    @else
        <div class="bg-amber-50/90 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800/80 rounded-xl p-5 shadow-sm dark:shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start space-x-3.5">
                <div class="w-10 h-10 rounded-lg bg-amber-100 border border-amber-300 text-amber-700 dark:bg-amber-500/20 dark:border-amber-500/30 dark:text-amber-400 flex items-center justify-center text-lg flex-shrink-0 mt-0.5">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <h2 class="text-sm font-bold text-amber-950 dark:text-white">Meta API Belum Terkoneksi</h2>
                        <span class="px-2 py-0.5 text-[9px] font-extrabold uppercase rounded-md bg-amber-200/90 text-amber-900 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300 dark:border-amber-700">Perlu Setup</span>
                    </div>
                    <p class="text-xs text-amber-900 dark:text-gray-300 mt-0.5">
                        Anda belum menghubungkan akun Facebook/Instagram. Ikuti panduan 4 langkah di bawah ini untuk mulai.
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-2 sm:self-center">
                <button @click="showGuide = true; currentTab = 'connect'" 
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-lg shadow-md transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-arrow-down"></i>
                    <span>Lihat Panduan Setup</span>
                </button>
            </div>
        </div>
    @endif

    <!-- Panduan Step-by-Step Pemula (Collapsible) -->
    <div x-show="showGuide" x-transition class="card-dark rounded-xl p-6 border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50/50 dark:bg-indigo-950/20 space-y-6 shadow-sm dark:shadow-xl">
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
                <h3 class="font-bold text-slate-900 dark:text-white text-xs">Buat Aplikasi Meta</h3>
                <p class="text-slate-600 dark:text-gray-400 text-[11px] leading-relaxed">
                    Buka <a href="https://developers.facebook.com/apps/" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline font-semibold">developers.facebook.com</a>, buat app baru dengan tipe <strong>Bisnis</strong>.
                </p>
                <span class="text-[10px] text-slate-400 dark:text-gray-500 block pt-1">Tambahkan produk: <strong>Facebook Login for Business</strong> & <strong>Instagram Graph API</strong>.</span>
            </div>

            <!-- Step 2 -->
            <div class="bg-white dark:bg-gray-900/80 p-4 rounded-lg border border-slate-200 dark:border-gray-800 space-y-2 relative shadow-sm">
                <div class="w-6 h-6 rounded-md bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">2</div>
                <h3 class="font-bold text-slate-900 dark:text-white text-xs">Salin Kredensial & URI</h3>
                <p class="text-slate-600 dark:text-gray-400 text-[11px] leading-relaxed">
                    Salin <strong>App ID</strong> & <strong>App Secret</strong> dari dashboard Meta Anda ke form di tab <em>Koneksi & Kredensial</em>.
                </p>
                <span class="text-[10px] text-slate-400 dark:text-gray-500 block pt-1">Salin pula <strong>Redirect URI</strong> dari aplikasi ini ke pengaturan Meta App Anda.</span>
            </div>

            <!-- Step 3 -->
            <div class="bg-white dark:bg-gray-900/80 p-4 rounded-lg border border-slate-200 dark:border-gray-800 space-y-2 relative shadow-sm">
                <div class="w-6 h-6 rounded-md bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">3</div>
                <h3 class="font-bold text-slate-900 dark:text-white text-xs">Login & Beri Izin</h3>
                <p class="text-slate-600 dark:text-gray-400 text-[11px] leading-relaxed">
                    Klik tombol <strong>"Hubungkan dengan Facebook"</strong>. Beri centang seluruh Page & Akun Instagram yang ingin dikelola.
                </p>
                <span class="text-[10px] text-slate-400 dark:text-gray-500 block pt-1">Token jangka panjang (~60 hari) akan otomatis disimpan terenkripsi.</span>
            </div>

            <!-- Step 4 -->
            <div class="bg-white dark:bg-gray-900/80 p-4 rounded-lg border border-slate-200 dark:border-gray-800 space-y-2 relative shadow-sm">
                <div class="w-6 h-6 rounded-md bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">4</div>
                <h3 class="font-bold text-slate-900 dark:text-white text-xs">Siap Buat Jadwal!</h3>
                <p class="text-slate-600 dark:text-gray-400 text-[11px] leading-relaxed">
                    Seluruh akun Anda akan muncul di tab <em>Aset Terhubung</em>. Anda langsung bisa membuat jadwal campaign.
                </p>
                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 block pt-1">Otomasi publish akan berjalan mulus tanpa browser!</span>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 1: KONEKSI & KREDENSIAL ==================== -->
    <div x-show="currentTab === 'connect'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Kiri: Tombol Login OAuth Facebook (Opsi Paling Mudah) -->
            <div class="lg:col-span-6 card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-5 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                            <i class="fa-brands fa-facebook text-blue-600 dark:text-blue-500 text-base"></i>
                            <span>Otorisasi Akun (Metode Utama)</span>
                        </h2>
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
                        <span>{{ $isConnected ? 'Hubungkan Ulang / Tambah Akun' : 'Hubungkan dengan Facebook Sekarang' }}</span>
                    </a>
                    <p class="text-[10px] text-center text-slate-400 dark:text-gray-500">
                        *Pastikan App ID sudah disimpan sebelum mengklik tombol di atas.
                    </p>
                </div>
            </div>

            <!-- Kanan: Form Kredensial App ID & Secret -->
            <div class="lg:col-span-6 card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-5">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <i class="fa-solid fa-sliders text-indigo-600 dark:text-indigo-400 text-base"></i>
                        <span>Kredensial Meta App</span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">
                        Diperlukan agar tombol login Facebook di sebelah kiri dapat mengenali aplikasi Anda.
                    </p>
                </div>

                <form action="{{ route('meta.updateCredentials') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider text-[10px] mb-1">
                            Meta App ID <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="app_id" value="{{ $credential->app_id }}" required placeholder="Contoh: 123456789012345"
                               class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition font-mono">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-gray-300 uppercase tracking-wider text-[10px] mb-1">
                            Meta App Secret <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" name="app_secret" placeholder="{{ $credential->app_secret ? '•••••••••••••••••••••••• (Tersimpan)' : 'Masukkan App Secret' }}"
                               class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition font-mono">
                    </div>

                    <!-- Auto-Generated Redirect URI -->
                    <div class="bg-slate-50 dark:bg-gray-900/90 p-3 rounded-lg border border-slate-200 dark:border-gray-800 space-y-1">
                        <label class="block text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                            Salin URL Ini ke Meta App (Valid OAuth Redirect URIs):
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

                    <input type="hidden" name="graph_version" value="{{ $credential->graph_version ?: 'v22.0' }}">

                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition shadow-md flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Kredensial App</span>
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
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white">Metode Alternatif: Input Manual System User Token</h3>
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

    <!-- ==================== TAB 2: DAFTAR AKUN TERHUBUNG ==================== -->
    <!-- ==================== TAB 2: DAFTAR AKUN TERHUBUNG ==================== -->
    <div x-show="currentTab === 'accounts'" class="space-y-6">
        <div class="card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-gray-800 pb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <i class="fa-solid fa-users text-indigo-600 dark:text-indigo-400"></i>
                        <span>Daftar Halaman Facebook & Akun Instagram</span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-gray-400 mt-0.5">
                        Akun yang terdaftar di bawah ini siap dipilih saat Anda membuat Project Campaign.
                    </p>
                </div>

                <button onclick="triggerSyncNow()" 
                        class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg transition flex items-center space-x-1.5 shadow-md">
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
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Belum Ada Akun Terdeteksi</h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400 max-w-md mx-auto">
                            Silakan klik tab <strong>Koneksi & Kredensial</strong> dan lakukan login via Facebook untuk memuat daftar akun.
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

    <!-- ==================== TAB 3: RIWAYAT & LANJUTAN ==================== -->
    <div x-show="currentTab === 'advanced'" class="space-y-6">
        
        <!-- Refresh & Info Token -->
        <div class="card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-4">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                <i class="fa-solid fa-arrows-rotate text-indigo-600 dark:text-indigo-400"></i>
                <span>Manajemen Refresh Token</span>
            </h2>
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
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg transition flex items-center space-x-1.5 shadow-md">
                        <i class="fa-solid fa-rotate"></i>
                        <span>Perpanjang Token Sekarang (+60 Hari)</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Webhook Token Field (Opsional) -->
        <div class="card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-3 text-xs">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                <i class="fa-solid fa-tower-broadcast text-purple-600 dark:text-purple-400"></i>
                <span>Meta Webhook Verify Token (Fitur Lanjutan Opsional)</span>
            </h2>
            <p class="text-slate-500 dark:text-gray-400">
                Digunakan jika Anda ingin menerima event/notifikasi real-time langsung dari server Meta.
            </p>
            <div class="font-mono text-slate-700 dark:text-gray-300 p-2.5 bg-slate-50 dark:bg-gray-900 rounded-lg border border-slate-200 dark:border-gray-800">
                Verify Token: <span class="text-indigo-600 dark:text-indigo-400">{{ $credential->webhook_verify_token ?: 'Belum diset' }}</span>
            </div>
        </div>

        <!-- Log Aktivitas -->
        <div class="card-dark rounded-xl p-6 border border-slate-200 dark:border-gray-800 space-y-4">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                <i class="fa-solid fa-clock-rotate-left text-indigo-600 dark:text-indigo-400"></i>
                <span>Catatan Aktivitas Token & Sinkronisasi</span>
            </h2>

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

    </div>

</div>
@endsection

@section('scripts')
<script>
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
</script>
@endsection
