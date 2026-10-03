@extends('layouts.app')

@section('title', 'Syarat dan Ketentuan Layanan (Terms of Service) - Damaijaya Auto')

@section('content')
<div class="w-full max-w-4xl mx-auto py-4 sm:py-8 space-y-8">

    <!-- Header Section -->
    <div class="card-dark rounded-2xl p-6 sm:p-8 border border-slate-200/90 dark:border-gray-800 shadow-xl backdrop-blur-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 dark:border-gray-800 pb-6">
            <div class="space-y-1">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <span>Dokumen Legal Resmi</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Syarat dan Ketentuan Layanan
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-gray-400">
                    Terms of Service Damaijaya Auto (SosmedAuto)
                </p>
            </div>
            <div class="text-left sm:text-right text-xs text-slate-500 dark:text-gray-400">
                <span class="block font-semibold text-slate-700 dark:text-gray-300">Pembaruan Terakhir:</span>
                <time datetime="2026-10-03">3 Oktober 2026</time>
            </div>
        </div>

        <div class="pt-6 prose dark:prose-invert max-w-none text-xs sm:text-sm text-slate-600 dark:text-gray-300 leading-relaxed space-y-6">
            <p>
                Selamat datang di <strong>Damaijaya Auto</strong> (<a href="https://autopost.damaijaya.my.id" class="text-indigo-600 dark:text-indigo-400 underline font-medium">https://autopost.damaijaya.my.id</a>), sistem otomasi, penjadwalan, dan publikasi konten media sosial internal untuk TikTok, Meta (Facebook Page &amp; Instagram Business), dan Threads.
            </p>
            <p>
                Dengan mengakses atau menggunakan platform Damaijaya Auto, Anda menyetujui untuk terikat oleh Syarat dan Ketentuan Layanan ini. Jika Anda tidak menyetujui salah satu ketentuan, mohon untuk tidak menggunakan layanan ini.
            </p>

            <!-- Bagian 1: Definisi dan Ruang Lingkup -->
            <div class="space-y-3 pt-2">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 inline-flex items-center justify-center text-xs font-bold">1</span>
                    <span>Definisi dan Ruang Lingkup Layanan</span>
                </h2>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>Layanan Damaijaya Auto:</strong> Aplikasi berbasis web yang memfasilitasi pembuatan kampanye konten, penjadwalan penerbitan waktu tayang, dan pengunggahan aset media secara terpusat ke akun sosial media yang sah.</li>
                    <li><strong>Platform Pihak Ketiga:</strong> Meliputi TikTok Inc., Meta Platforms Inc. (Facebook, Instagram, Threads), serta penyedia penyimpanan media Cloudflare R2.</li>
                    <li><strong>Pengguna:</strong> Anggota tim, staf konten, atau pengelola resmi yang memiliki otorisasi internal untuk mengoperasikan sistem Damaijaya Auto.</li>
                </ul>
            </div>

            <!-- Bagian 2: Akun dan Otorisasi API -->
            <div class="space-y-3 pt-2">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 inline-flex items-center justify-center text-xs font-bold">2</span>
                    <span>Otorisasi Akun dan Kredensial API</span>
                </h2>
                <p>
                    Aplikasi ini menggunakan protokol resmi OAuth 2.0 yang disediakan oleh platform mitra (TikTok Open API dan Meta Graph API). Saat menghubungkan akun:
                </p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Pengguna hanya diperbolehkan menghubungkan akun media sosial bisnis, kreator, atau profil publik yang secara hukum sah dimiliki atau dikelola oleh pengguna atau entitas Damai Jaya.</li>
                    <li>Token otorisasi disimpan dalam basis data terenkripsi dan hanya digunakan untuk mengeksekusi aksi penerbitan yang secara eksplisit dibuat dan dijadwalkan oleh pengguna.</li>
                    <li>Pengguna bertanggung jawab penuh atas kerahasiaan kredensial login akun dan segala aktivitas yang dilakukan melalui akun tersebut.</li>
                </ul>
            </div>

            <!-- Bagian 3: Hak Kekayaan Intelektual dan Konten -->
            <div class="space-y-3 pt-2">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 inline-flex items-center justify-center text-xs font-bold">3</span>
                    <span>Hak Kekayaan Intelektual atas Konten</span>
                </h2>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Seluruh materi, gambar, audio, dan rekaman video yang diunggah ke Damaijaya Auto merupakan hak milik penuh pengguna atau pemegang lisensi yang sah.</li>
                    <li>Damaijaya Auto tidak mengklaim kepemilikan apa pun atas konten yang Anda jadwalkan atau publikasikan melalui layanan ini.</li>
                    <li>Pengguna menjamin bahwa konten yang diunggah tidak melanggar hak cipta, merek dagang, hak privasi, atau hak kekayaan intelektual pihak ketiga mana pun.</li>
                </ul>
            </div>

            <!-- Bagian 4: Larangan dan Kepatuhan Konten -->
            <div class="space-y-3 pt-2">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 inline-flex items-center justify-center text-xs font-bold">4</span>
                    <span>Batasan dan Larangan Penggunaan</span>
                </h2>
                <p>Pengguna dilarang keras menggunakan sistem Damaijaya Auto untuk:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Menyebarkan konten yang melanggar hukum, penipuan, fitnah, ujaran kebencian, diskriminasi SARA, pornografi, atau kekerasan.</li>
                    <li>Melakukan spamming massal yang melanggar batas kuota (Rate Limit) dan kebijakan penggunaan wajar (Fair Use) dari TikTok Developer Terms maupun Meta Platform Terms.</li>
                    <li>Mencoba merusak, memanipulasi, menyusupi, atau melakukan rekayasa balik (reverse engineering) pada infrastruktur sistem.</li>
                </ul>
            </div>

            <!-- Bagian 5: Ketergantungan Layanan Pihak Ketiga -->
            <div class="space-y-3 pt-2">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 inline-flex items-center justify-center text-xs font-bold">5</span>
                    <span>Ketergantungan terhadap API Pihak Ketiga</span>
                </h2>
                <p>
                    Layanan publikasi bergantung langsung pada ketersediaan, stabilitas, dan aturan teknis dari TikTok Open API serta Meta Graph API. Kami tidak bertanggung jawab atas keterlambatan atau kegagalan tayang konten yang disebabkan oleh gangguan server pihak ketiga, pencabutan izin oleh platform sosial, atau perubahan kebijakan API mendadak dari TikTok maupun Meta.
                </p>
            </div>

            <!-- Bagian 6: Pemutusan dan Penghentian Layanan -->
            <div class="space-y-3 pt-2">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 inline-flex items-center justify-center text-xs font-bold">6</span>
                    <span>Pemutusan Akses dan Penghentian Layanan</span>
                </h2>
                <p>
                    Pengguna berhak memutuskan tautan akun media sosial kapan saja melalui menu <em>Pengaturan &gt; Integrasi Akun</em> atau langsung mencabut izin aplikasi Damaijaya Auto melalui pengaturan privasi akun TikTok dan Facebook/Instagram masing-masing.
                </p>
                <p>
                    Pengelola Damaijaya Auto berhak menangguhkan atau menghentikan akses pengguna jika ditemukan indikasi pelanggaran terhadap syarat dan ketentuan ini.
                </p>
            </div>

            <!-- Bagian 7: Kontak Dukungan -->
            <div class="space-y-3 pt-2 border-t border-slate-200/80 dark:border-gray-800 pt-6">
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 inline-flex items-center justify-center text-xs font-bold">7</span>
                    <span>Kontak dan Bantuan</span>
                </h2>
                <p>
                    Jika Anda memiliki pertanyaan mengenai Syarat dan Ketentuan Layanan ini, silakan hubungi tim administrasi kami:
                </p>
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-800 space-y-1 text-xs sm:text-sm">
                    <div class="font-bold text-slate-900 dark:text-white">Tim Pengembang Damaijaya Auto</div>
                    <div class="text-slate-600 dark:text-gray-400">Email Dukungan: <a href="mailto:support@damaijaya.my.id" class="text-indigo-600 dark:text-indigo-400 font-semibold underline">support@damaijaya.my.id</a></div>
                    <div class="text-slate-600 dark:text-gray-400">Website: <a href="https://autopost.damaijaya.my.id" class="text-indigo-600 dark:text-indigo-400 font-semibold underline">https://autopost.damaijaya.my.id</a></div>
                </div>
            </div>

        </div>

        <!-- Back to Home / Login Button -->
        <div class="mt-8 pt-6 border-t border-slate-200/80 dark:border-gray-800 flex items-center justify-between">
            <a href="{{ route('privacy') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center space-x-1.5">
                <span>Baca Kebijakan Privasi</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <a href="{{ url('/') }}" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-slate-800 dark:text-gray-200 transition">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Beranda</span>
            </a>
        </div>
    </div>

</div>
@endsection
