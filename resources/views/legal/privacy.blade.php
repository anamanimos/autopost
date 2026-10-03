@extends('layouts.app')

@section('title', 'Kebijakan Privasi (Privacy Policy) - Damaijaya Auto')

@section('content')
<div class="w-full max-w-4xl mx-auto py-4 sm:py-8 space-y-8">

    <!-- Header Section -->
    <div class="card-dark rounded-2xl p-6 sm:p-8 border border-slate-200/90 dark:border-gray-800 shadow-xl backdrop-blur-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 dark:border-gray-800 pb-6">
            <div class="space-y-1">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Kebijakan Privasi dan Perlindungan Data</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Kebijakan Privasi
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-gray-400">
                    Privacy Policy Damaijaya Auto (SosmedAuto)
                </p>
            </div>
            <div class="text-left sm:text-right text-xs text-slate-500 dark:text-gray-400">
                <span class="block font-semibold text-slate-700 dark:text-gray-300">Pembaruan Terakhir:</span>
                <time datetime="2026-10-03">3 Oktober 2026</time>
            </div>
        </div>

        <div class="pt-6 prose dark:prose-invert max-w-none text-xs sm:text-sm text-slate-600 dark:text-gray-300 leading-relaxed space-y-6">
            <p>
                Di <strong>Damaijaya Auto</strong> (<a href="https://autopost.damaijaya.my.id" class="text-indigo-600 dark:text-indigo-400 underline font-medium">https://autopost.damaijaya.my.id</a>), privasi dan keamanan data Anda adalah prioritas utama kami. Dokumen Kebijakan Privasi ini menjelaskan jenis informasi yang kami kumpulkan, bagaimana informasi tersebut digunakan, disimpan, dan dilindungi saat Anda menggunakan layanan otomasi publikasi konten media sosial kami.
            </p>

            <!-- Bagian 1: Data yang Kami Kumpulkan -->
            <div class="space-y-3 pt-2">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 inline-flex items-center justify-center text-xs font-bold">1</span>
                    <span>Informasi yang Kami Kumpulkan</span>
                </h2>
                <p>
                    Kami hanya mengumpulkan data yang benar-benar esensial untuk menjalankan fungsi penjadwalan dan publikasi konten ke platform pihak ketiga (TikTok, Facebook, Instagram, Threads):
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-800">
                        <div class="font-bold text-slate-900 dark:text-white text-xs mb-1 flex items-center space-x-1.5">
                            <i class="fa-solid fa-id-badge text-indigo-500"></i>
                            <span>Profil Media Sosial Publik</span>
                        </div>
                        <p class="text-[11px] text-slate-600 dark:text-gray-400">
                            Username, Display Name, Foto Profil, serta pengenal unik akun seperti Open ID TikTok, Page ID Facebook, IG User ID, atau Threads ID yang diberikan melalui otorisasi OAuth resmi.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-800">
                        <div class="font-bold text-slate-900 dark:text-white text-xs mb-1 flex items-center space-x-1.5">
                            <i class="fa-solid fa-key text-amber-500"></i>
                            <span>Token Akses Otentikasi</span>
                        </div>
                        <p class="text-[11px] text-slate-600 dark:text-gray-400">
                            OAuth Access Token dan Refresh Token yang digunakan sistem untuk berkomunikasi secara aman dengan server TikTok for Developers dan Meta Graph API atas nama pengguna.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-800">
                        <div class="font-bold text-slate-900 dark:text-white text-xs mb-1 flex items-center space-x-1.5">
                            <i class="fa-solid fa-photo-film text-pink-500"></i>
                            <span>Aset Media &amp; Konten Kampanye</span>
                        </div>
                        <p class="text-[11px] text-slate-600 dark:text-gray-400">
                            File foto, video, teks caption, dan jadwal penayangan yang secara sukarela diunggah oleh pengguna untuk diterbitkan ke akun sosial yang dipilih.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-800">
                        <div class="font-bold text-slate-900 dark:text-white text-xs mb-1 flex items-center space-x-1.5">
                            <i class="fa-solid fa-clock-rotate-left text-sky-500"></i>
                            <span>Log Transaksi Penerbitan</span>
                        </div>
                        <p class="text-[11px] text-slate-600 dark:text-gray-400">
                            Catatan audit teknis seperti stempel waktu eksekusi jadwal, status postingan (sukses atau gagal), ID media dari platform mitra, dan pesan respons error jika terjadi kendala jaringan.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Cara Penggunaan Data -->
            <div class="space-y-3 pt-2">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 inline-flex items-center justify-center text-xs font-bold">2</span>
                    <span>Tujuan Penggunaan Informasi</span>
                </h2>
                <p>Informasi yang dikumpulkan digunakan semata-mata untuk keperluan operasional sistem berikut:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Mengotentikasi koneksi akun Anda ke TikTok Open API, Meta Graph API, dan Threads API.</li>
                    <li>Mengunggah dan menerbitkan materi foto atau video sesuai waktu yang telah Anda jadwalkan dalam kampanye.</li>
                    <li>Memperbarui token akses secara berkala (Auto-Refresh Token) agar proses auto-posting tidak terputus karena masa aktif token kedaluwarsa.</li>
                    <li>Memeriksa status batasan kuota penerbitan harian (*Publishing Rate Limit*) agar akun Anda terhindar dari pemblokiran oleh platform sosial.</li>
                    <li>Menampilkan laporan riwayat publikasi di dashboard aplikasi.</li>
                </ul>
            </div>

            <!-- Bagian 3: Keamanan dan Penyimpanan Data -->
            <div class="space-y-3 pt-2">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 inline-flex items-center justify-center text-xs font-bold">3</span>
                    <span>Keamanan dan Penyimpanan Data</span>
                </h2>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>Enkripsi Data Sensitif:</strong> Seluruh Access Token, Refresh Token, dan Secret Key disimpan dalam database menggunakan algoritma enkripsi standar industri (AES-256-CBC) dengan kunci enkripsi aplikasi yang terlindungi.</li>
                    <li><strong>Transmisi Data Terenkripsi:</strong> Seluruh komunikasi antar-browser, server aplikasi, dan endpoint API TikTok/Meta selalu menggunakan protokol HTTPS (TLS 1.3).</li>
                    <li><strong>Penyimpanan Media Terisolasi:</strong> File gambar dan video disimpan pada penyimpanan terkelola Cloudflare R2 dengan hak akses terkontrol.</li>
                    <li><strong>Tanpa Penjualan Data:</strong> Kami <strong>tidak pernah dan tidak akan pernah</strong> menjual, menyewakan, memperdagangkan, atau membagikan data pribadi maupun token otentikasi Anda kepada pihak ketiga mana pun untuk tujuan periklanan atau komersial.</li>
                </ul>
            </div>

            <!-- Bagian 4: Pembagian Data ke Platform Pihak Ketiga -->
            <div class="space-y-3 pt-2">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 inline-flex items-center justify-center text-xs font-bold">4</span>
                    <span>Interaksi dengan Platform Pihak Ketiga</span>
                </h2>
                <p>
                    Saat Anda mengaktifkan fungsi penerbitan, data media dan caption dikirimkan langsung ke platform sosial yang Anda targetkan sesuai izin yang Anda berikan:
                </p>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>TikTok:</strong> Menggunakan TikTok Content Posting API v2. Penggunaan tunduk pada <a href="https://www.tiktok.com/legal/privacy-policy" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 underline">Kebijakan Privasi TikTok</a>.</li>
                    <li><strong>Meta (Facebook, Instagram, Threads):</strong> Menggunakan Meta Graph API dan Threads API. Penggunaan tunduk pada <a href="https://www.facebook.com/privacy/policy" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 underline">Kebijakan Privasi Meta</a>.</li>
                </ul>
            </div>

            <!-- Bagian 5: Penghapusan Data (Data Deletion Instructions) -->
            <div class="space-y-3 pt-2 border-t border-slate-200/80 dark:border-gray-800 pt-6">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 inline-flex items-center justify-center text-xs font-bold">5</span>
                    <span>Hak Pengguna dan Instruksi Penghapusan Data (Data Deletion)</span>
                </h2>
                <p>
                    Anda memiliki kendali penuh atas data dan akun sosial Anda. Anda berhak memutuskan koneksi akun dan meminta penghapusan seluruh data yang tersimpan di Damaijaya Auto kapan saja:
                </p>
                <div class="space-y-3 bg-slate-50 dark:bg-gray-900/60 p-4 rounded-xl border border-slate-200 dark:border-gray-800 text-xs sm:text-sm">
                    <h3 class="font-bold text-slate-900 dark:text-white">Langkah Pemutusan Koneksi dan Penghapusan Data:</h3>
                    <ol class="list-decimal pl-5 space-y-2 text-slate-600 dark:text-gray-300">
                        <li><strong>Melalui Aplikasi Damaijaya Auto:</strong> Buka menu <em>Pengaturan &gt; Integrasi TikTok API</em> (atau Integrasi Meta/Threads), cari akun yang ingin diputuskan, lalu klik tombol <strong>Putuskan Koneksi</strong>. Seluruh token otorisasi akan langsung dihapus permanen dari basis data kami.</li>
                        <li><strong>Melalui Portal TikTok:</strong> Masuk ke aplikasi TikTok Anda &gt; buka <em>Profil &gt; Pengaturan dan Privasi &gt; Keamanan &gt; Kelola Izin Aplikasi</em>, pilih <strong>Damaijaya Auto</strong>, lalu klik <strong>Hapus Akses</strong>.</li>
                        <li><strong>Permintaan Penghapusan Total:</strong> Jika Anda menghendaki seluruh riwayat kampanye, jadwal, dan file media Anda dihapus secara menyeluruh dari server dan cloud storage, kirimkan email permintaan penghapusan data ke <a href="mailto:support@damaijaya.my.id" class="text-indigo-600 dark:text-indigo-400 font-bold underline">support@damaijaya.my.id</a> dengan subjek <em>"Permintaan Penghapusan Data Akun"</em>. Permintaan Anda akan diproses maksimal dalam 2x24 jam kerja.</li>
                    </ol>
                </div>
            </div>

            <!-- Bagian 6: Perubahan Kebijakan Privasi -->
            <div class="space-y-3 pt-2">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 inline-flex items-center justify-center text-xs font-bold">6</span>
                    <span>Pembaruan Kebijakan Privasi</span>
                </h2>
                <p>
                    Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu untuk menyesuaikan dengan perubahan fitur sistem atau kebijakan pengembang dari TikTok dan Meta. Tanggal pembaruan terakhir akan selalu tertera di bagian atas halaman ini.
                </p>
            </div>

            <!-- Bagian 7: Kontak Privasi -->
            <div class="space-y-3 pt-2 border-t border-slate-200/80 dark:border-gray-800 pt-6">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 inline-flex items-center justify-center text-xs font-bold">7</span>
                    <span>Kontak Resmi Petugas Perlindungan Data</span>
                </h2>
                <p>
                    Untuk pertanyaan, klarifikasi, atau permintaan hak privasi, Anda dapat menghubungi tim kami melalui:
                </p>
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-800 space-y-1 text-xs sm:text-sm">
                    <div class="font-bold text-slate-900 dark:text-white">Damaijaya Auto Privacy Team</div>
                    <div class="text-slate-600 dark:text-gray-400">Email: <a href="mailto:support@damaijaya.my.id" class="text-indigo-600 dark:text-indigo-400 font-semibold underline">support@damaijaya.my.id</a></div>
                    <div class="text-slate-600 dark:text-gray-400">Alamat Layanan: <a href="https://autopost.damaijaya.my.id" class="text-indigo-600 dark:text-indigo-400 font-semibold underline">https://autopost.damaijaya.my.id</a></div>
                </div>
            </div>

        </div>

        <!-- Back to Home / Terms Button -->
        <div class="mt-8 pt-6 border-t border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
            <a href="{{ route('terms') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center space-x-1.5">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Baca Syarat dan Ketentuan</span>
            </a>
            <a href="{{ url('/') }}" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-slate-800 dark:text-gray-200 transition">
                <span>Kembali ke Beranda</span>
            </a>
        </div>
    </div>

</div>
@endsection
