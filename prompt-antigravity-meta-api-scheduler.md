# PROMPT UNTUK ANTIGRAVITY — Meta Content Scheduler (IG & FB) via Official Meta Graph API

## KONTEKS

Saya sudah punya sistem serupa bernama **Meta Story Auto Scheduler (IGBot/MetaBot)** yang berjalan dengan pendekatan **browser automation (Python + Playwright + Chromium)** untuk menjadwalkan dan mengunggah Story ke Instagram & Facebook Page via Meta Business Suite.

Saya ingin membangun **versi baru** dari sistem ini dengan **arsitektur, konsep, dan fitur yang sama persis**, TAPI mengganti seluruh mekanisme automation engine (Playwright/Chromium) dengan **Meta Graph API resmi** (Instagram Content Publishing API & Facebook Pages API). Selain itu, tambahkan **halaman baru** di dashboard untuk menginput seluruh data pendukung yang dibutuhkan API Meta (App ID, App Secret, Access Token, Page ID, IG Business Account ID, dsb).

Gunakan spesifikasi di bawah ini sebagai acuan pembangunan aplikasi.

---

## 1. RINGKASAN SISTEM (TARGET BARU)

Bangun **Meta Content Scheduler** — aplikasi automasi & manajemen campaign terpadu untuk menjadwalkan, mengelola, dan mengunggah konten **Story maupun Post** (Instagram & Facebook Page) secara otomatis, menggunakan:

- **Dashboard Web berbasis Laravel 11** — manajemen pengguna, media pool, jadwal, kredensial API, dan monitoring status publish.
- **Automation Engine berbasis Meta Graph API** (menggantikan Python+Playwright) — dieksekusi via Laravel Queue/Job (Horizon) yang memanggil endpoint resmi Meta Graph API, TANPA browser automation, TANPA emulasi UI.

Pertahankan seluruh konsep bisnis dari sistem lama (dijelaskan di bawah), hanya lapisan "cara publish ke Meta" yang diganti total dari simulasi klik UI menjadi panggilan API resmi.

---

## 2. KONSEP UTAMA (DIPERTAHANKAN): "Every Post as a Project Campaign"

Setiap unit jadwal tetap diperlakukan sebagai satu **Project Campaign** berulang yang fleksibel, terdiri dari:

- **Media Pool (Multi-File Assets):** kumpulan gambar (.jpg, .png) atau video (.mp4, .mov) yang dirotasi otomatis. Media harus disimpan di storage dengan **URL publik yang bisa diakses Meta** (syarat wajib Graph API — Meta mengambil media via URL, bukan upload file langsung untuk sebagian endpoint).
- **Target Akun & Aset Bisnis Meta (multi-select, dengan kontrol platform per target):** lihat Bagian 2.1 di bawah.
- **Konfigurasi Jam & Hari Tayang:** jam tayang harian (misal 07:30 WIB) dan Exclude Days.
- **Tipe Konten:** Story atau Feed Post (tambahkan pilihan ini karena API mendukung keduanya, tidak seperti versi lama yang fokus Story saja).
- **Moda Pengulangan (Repeat Mode)** — sama seperti sebelumnya.

### 2.1 Pemilihan Akun & Kontrol Platform (FITUR BARU — tidak ada di versi lama)

Sistem lama hanya mendukung 1 pasangan Page+IG per Project Campaign, dan selalu publish ke keduanya. Versi baru harus mendukung:

1. **Multi-account selection** — satu Project Campaign bisa menargetkan **lebih dari satu akun** (misal: campaign yang sama jalan ke Page/IG "Sevencols" DAN Page/IG "Arema Style" sekaligus). UI-nya berupa multi-select/checklist daftar akun yang sudah terhubung (hasil Full Sync, Bagian 5).
2. **Per-target platform control** — untuk **setiap akun yang dipilih**, user bisa menentukan platform mana yang dituju:
   - `both` — publish ke Facebook Page **dan** Instagram (default, sama seperti perilaku sistem lama).
   - `instagram_only` — publish **hanya ke Instagram**, Facebook Page dari pasangan itu dikecualikan/skip.
   - `facebook_only` — publish **hanya ke Facebook Page**, Instagram dari pasangan itu dikecualikan/skip.

   Ini memungkinkan skenario seperti: "posting ini tayang di IG Sevencols saja, tapi di Arema Style tayang di keduanya" — dikontrol per baris target, bukan satu setting global untuk seluruh campaign.

**Struktur data (tambahan tabel pivot):**
```
campaign_targets
 ├── id
 ├── project_campaign_id
 ├── connected_account_id   (referensi ke pasangan Page+IG hasil Full Sync)
 └── platform_target        ENUM('both', 'instagram_only', 'facebook_only')  -- default 'both'
```

**Dampak ke Publish Engine (Bagian 4):** saat job publish berjalan, engine melakukan loop per `campaign_targets`, lalu mengecek `platform_target` sebelum memanggil API — jika `instagram_only`, lewati seluruh langkah publish ke Facebook Page (Bagian 4.2 bagian Facebook) untuk target tersebut, dan sebaliknya untuk `facebook_only`. Status publish (Bagian 5/monitoring) dicatat **per target per platform**, bukan hanya per campaign, agar terlihat jelas mana yang sukses/gagal/di-skip secara sengaja.

---

## 3. MODA PENGULANGAN (DIPERTAHANKAN PERSIS)

| Mode | Simbol | Deskripsi | Contoh Kasus |
|---|---|---|---|
| ♾️ Kontinu Selamanya | `continuous` | Tayang setiap hari tanpa batas. Rolling buffer 29 hari, media diambil rotasi/acak dari Media Pool. | Promo harian, quote pagi |
| 🎯 Hanya 1x Post | `once` | Tayang 1x pada tanggal & jam tertentu, validasi minimal +30 menit dari waktu pembuatan. | Flash sale, event khusus |
| 📅 Sampai Tanggal Tertentu | `until_date` | Tayang berulang tiap hari dari `start_date` sampai `end_date`. | Campaign bulanan |

Logic penjadwalan (cron/queue worker, rolling buffer, exclude days, validasi waktu) **dipertahankan sama**, hanya eksekusi akhirnya diarahkan ke Graph API call, bukan Playwright script.

---

## 4. PERUBAHAN UTAMA: ARSITEKTUR PUBLISH VIA META GRAPH API (Ganti Total Bagian Playwright)

Hapus seluruh konsep "2-Stage Business Suite Navigation" (klik sidebar, klik aset, dsb). Ganti dengan struktur berbasis ID resmi:

### 4.1 Struktur Data Aset (Baru)
```
Meta Business Account (Business Manager)
 └── Facebook Page (page_id, page_access_token)
      └── Instagram Business/Creator Account (ig_user_id) — linked via Page
```

Simpan di database: `page_id`, `page_name`, `page_access_token` (encrypted), `ig_user_id`, `ig_username`, `is_active`, `last_synced_at`.

### 4.2 Alur Publish via API (menggantikan flow "Buat cerita" manual)

**Instagram (Post & Story) — Content Publishing API:**
1. `POST https://graph.facebook.com/{VERSION}/{IG_USER_ID}/media` — buat container. Parameter utama:
   - `image_url` atau `video_url` — media harus sudah ada di server publik (Meta melakukan cURL terhadap URL ini).
   - `caption` — teks caption.
   - `media_type` — `STORIES` untuk Story, `REELS` untuk video feed (single video otomatis jadi reels), `CAROUSEL` untuk multi-media, kosongkan untuk foto feed biasa.
   - `is_carousel_item: true` jika media ini bagian dari carousel.
   - `access_token` — Page Access Token (untuk jalur Facebook Login) atau IG User Access Token (untuk jalur Instagram Login).
   
   Respons sukses: `{"id": "<IG_CONTAINER_ID>"}`
2. **Khusus video/Story video** — poll status container: `GET /{IG_CONTAINER_ID}?fields=status_code` sampai `status_code = FINISHED` (lihat Bagian 4.3 untuk detail nilai status & rekomendasi polling).
3. `POST /{IG_USER_ID}/media_publish` dengan body `{"creation_id": "<IG_CONTAINER_ID>"}`. Respons sukses: `{"id": "<IG_MEDIA_ID>"}`.
4. Untuk carousel: buat container tiap item dengan `is_carousel_item=true`, lalu buat container induk dengan `media_type=CAROUSEL` dan `children` berisi daftar container ID (maks 10 item), baru publish container induk.

**Facebook Page (Post):**
- Foto: `POST /{page_id}/photos` dengan `url` (link gambar publik) atau upload binary, plus `caption`.
- Video: `POST /{page_id}/videos` dengan `file_url` atau upload binary.
- Feed teks/link: `POST /{page_id}/feed` dengan `message` dan/atau `link`.
- Story FB Page: dukungan endpoint Story resmi untuk Facebook Page **lebih terbatas** dibanding Instagram — validasi ketersediaan endpoint terhadap dokumentasi Meta terkini saat implementasi, dan siapkan fallback berupa feed post biasa jika endpoint Story API tidak tersedia untuk kombinasi akun tertentu.

**Dua jalur autentikasi yang tersedia (pilih salah satu sebagai arsitektur utama, sebutkan trade-off di kode):**

| | Instagram API with Instagram Login | Instagram API with Facebook Login |
|---|---|---|
| Host | `graph.instagram.com` | `graph.facebook.com` (+ `rupload.facebook.com` untuk resumable video upload) |
| Access token | Instagram User access token | Facebook Page access token |
| Login flow | Business Login for Instagram | Facebook Login for Business |
| Permission/scope | `instagram_business_basic`, `instagram_business_content_publish` | `instagram_basic`, `instagram_content_publish`, `pages_read_engagement` (+ `ads_management`, `ads_read` jika app user punya role di Page via Business Manager) |

> Rekomendasi: gunakan jalur **Facebook Login for Business** karena satu token (Page Access Token) sekaligus dipakai untuk publish ke Facebook Page maupun Instagram Business Account yang terhubung — cocok dengan struktur "1 Page + 1 IG Account terhubung" yang sudah dipakai di sistem lama.

### 4.3 Rate Limit & Validasi (baru — tidak ada di versi Playwright)
- Instagram Content Publishing API dibatasi **maksimal 100 post ter-publish per IG Professional Account per periode rolling 24 jam** (carousel dihitung 1 post). Cek sisa kuota lewat endpoint `GET /{IG_ID}/content_publishing_limit` dan tampilkan counter-nya di dashboard per akun.
- Container yang dibuat via `POST /{IG_ID}/media` **kadaluarsa dalam 24 jam** jika tidak segera di-publish (`status_code: EXPIRED`) — job publish harus segera memanggil `media_publish` setelah container `FINISHED`, jangan menumpuk container yang belum dipublish.
- Untuk video: setelah membuat container, **poll status** via `GET /{IG_CONTAINER_ID}?fields=status_code` (nilai: `IN_PROGRESS`, `FINISHED`, `ERROR`, `EXPIRED`, `PUBLISHED`) — Meta merekomendasikan polling sekali per menit, maksimal 5 menit, baru panggil `media_publish`.
- Format gambar didukung **hanya JPEG** (MPO/JPS tidak didukung). Story rasio 9:16 disarankan.
- Tangani error code resmi Graph API (token expired, permission error, media format error, dsb — lihat referensi Error Codes di Bagian 10) dan tampilkan pesan jelas di UI, bukan sekadar "gagal".

---

## 5. FULL SYNC & SOFT-DEACTIVATION (DIPERTAHANKAN, SUMBER DATA DIGANTI KE API)

Ganti script `fetch_portfolios.py` (Playwright scraping sidebar) menjadi job sinkronisasi via API:

- Panggil `GET /me/accounts` (dengan token user/system user) untuk ambil semua Page yang bisa dikelola.
- Untuk tiap Page, panggil `GET /{page_id}?fields=instagram_business_account` untuk ambil IG Business Account terhubung.
- **Aset aktif**: update/insert dengan `is_active = true`, `last_synced_at = now()`.
- **Soft-deactivation**: aset yang tidak lagi muncul di hasil sync **TIDAK DIHAPUS** dari database (agar histori Project Campaign tidak rusak), cukup di-set `is_active = false`.
- **Badge peringatan** di UI: tampilkan "⚠ Aset Tidak Ditemukan / Nonaktif" pada Project Campaign yang memakai aset nonaktif — sama seperti versi lama.

---

## 6. OTENTIKASI & KEAMANAN (GANTI TOTAL — INI PERUBAHAN PALING KRUSIAL)

Hapus seluruh mekanisme session-per-akun berbasis cookies/local storage Chromium. Ganti dengan:

- **Meta App resmi** terdaftar di [developers.facebook.com](https://developers.facebook.com), dengan produk **Facebook Login** dan **Instagram Graph API** diaktifkan.
- **OAuth Flow (Facebook Login for Business)**: user login sekali via popup OAuth resmi Meta untuk memberi izin ke Page & IG Account yang dikelola — bukan login manual per akun di browser headless.
- **Permission/Scopes yang dibutuhkan**: `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`, `instagram_basic`, `instagram_content_publish`, `business_management`.
- **Token Management:**
  - Token dari OAuth flow login biasa berumur **pendek (short-lived, ~1-2 jam)**. Wajib ditukar (exchange) ke **long-lived token (~60 hari)** via server-side call:
    `GET https://graph.facebook.com/{VERSION}/oauth/access_token?grant_type=fb_exchange_token&client_id={APP_ID}&client_secret={APP_SECRET}&fb_exchange_token={SHORT_LIVED_TOKEN}`
    (Panggilan ini WAJIB dari server, App Secret tidak boleh terekspos ke client-side.)
  - Ambil **Page Access Token** per Page menggunakan long-lived User token: `GET /{user_id}/accounts?access_token={LONG_LIVED_USER_TOKEN}` — Page Access Token yang dihasilkan dari long-lived User token ini umumnya **tidak memiliki masa kedaluwarsa** selama app tidak dicabut aksesnya, tapi tetap simpan `last_verified_at` dan lakukan pengecekan berkala.
  - Simpan seluruh token **terenkripsi** di database (bukan plaintext).
  - Buat job terjadwal untuk **verifikasi validitas token secara berkala** (misal via Access Token Debugger endpoint `GET /debug_token`) dan notifikasi/alert bila token invalid (butuh re-auth manual).
  - (Opsional, direkomendasikan untuk skala production/multi-akun banyak) Gunakan **System User Token** dari Meta Business Suite (Business Manager) — token ini tidak expired berdasarkan waktu, cocok untuk automation server-to-server tanpa bergantung sesi user personal.
- **Tidak ada lagi** kebutuhan Windows Hello/Passkey/2FA browser — seluruh auth terjadi di layar consent resmi Meta.

---

## 7. HALAMAN BARU (WAJIB DITAMBAHKAN): Input Data Pendukung API Meta

Tambahkan halaman **"Pengaturan Integrasi Meta API"** di dashboard, berisi form untuk:

1. **Meta App Credentials**
   - App ID
   - App Secret (disimpan terenkripsi, tidak ditampilkan penuh setelah disimpan)
   - Callback/Redirect URL untuk OAuth (auto-generate, tampilkan agar bisa dicopy ke Meta App Dashboard)
2. **Koneksi Akun**
   - Tombol "Hubungkan dengan Facebook" (trigger OAuth flow resmi)
   - Setelah connect, tampilkan list Page + IG Business Account yang berhasil ditemukan, dengan status koneksi (Aktif/Kadaluarsa/Perlu Re-auth)
3. **Manajemen Token**
   - Tanggal token dibuat & estimasi kadaluarsa
   - Tombol "Refresh Token Manual"
   - Log riwayat refresh token (sukses/gagal)
4. **Webhook (opsional, untuk fitur lanjutan)**
   - Field Verify Token untuk Meta Webhook (jika ke depan ingin terima notifikasi status publish/insight secara real-time)
5. **Test Koneksi**
   - Tombol untuk melakukan test call ringan (misal `GET /{page_id}?fields=name`) dan menampilkan hasil sukses/gagal langsung di halaman ini, agar user tahu kredensial valid sebelum membuat Project Campaign.

---

## 8. SPESIFIKASI TEKNOLOGI (TARGET)

**Backend & Dashboard**
- Laravel 11 (PHP 8.2+)
- MySQL 8.0 / MariaDB
- Tailwind CSS, Alpine.js, SweetAlert2 (pertahankan look & feel dari versi lama)
- Queue: Laravel Horizon + Redis untuk job penjadwalan & publish
- HTTP Client: Laravel `Http::` facade untuk semua panggilan ke Graph API (tidak perlu Python terpisah lagi — automation engine sepenuhnya jadi bagian dari Laravel Job, karena tidak ada lagi kebutuhan browser)
- Media Storage: Public disk (atau S3-compatible) dengan URL publik + deduplikasi SHA-256 hash (dipertahankan)

**Integrasi Eksternal**
- Meta Graph API (versi terbaru yang stabil — cek versi API terkini saat implementasi, karena Meta rutin deprecate versi lama)
- Instagram Content Publishing API
- Facebook Pages API

---

## 9. YANG DIHAPUS DARI SISTEM LAMA (jangan diimplementasikan ulang)
- Python + Playwright automation engine
- Chromium Persistent Context (headless/visual)
- `user_data_slug` per-akun session folder
- Deteksi checkpoint UI (login page/2FA modal/overlay)
- Navigasi 2-tingkat sidebar Meta Business Suite
- PHP subprocess runner (`pclose(popen())`, `xvfb-run`)

---

## 10. REFERENSI DOKUMENTASI RESMI (untuk dirujuk selama implementasi)

- **Instagram Platform Overview** — https://developers.facebook.com/documentation/instagram-platform/overview
- **Content Publishing (endpoint publish IG, container, carousel, reels, story)** — https://developers.facebook.com/documentation/instagram-platform/content-publishing
- **IG User `/media` endpoint reference (semua parameter)** — https://developers.facebook.com/documentation/instagram-platform/instagram-graph-api/reference/ig-user/media
- **IG User `/media_publish` endpoint reference** — https://developers.facebook.com/documentation/instagram-platform/instagram-graph-api/reference/ig-user/media_publish
- **IG User `/content_publishing_limit` (cek sisa kuota)** — https://developers.facebook.com/documentation/instagram-platform/instagram-graph-api/reference/ig-user/content_publishing_limit
- **Error Codes reference** — https://developers.facebook.com/documentation/instagram-platform/instagram-graph-api/reference/error-codes
- **Facebook Login for Business (OAuth flow)** — https://developers.facebook.com/docs/facebook-login/facebook-login-for-business
- **Long-Lived Access Tokens (exchange & refresh)** — https://developers.facebook.com/documentation/facebook-login/guides/access-tokens/get-long-lived
- **Page Access Tokens** — https://developers.facebook.com/docs/pages/access-tokens/
- **Permissions Reference (scope lengkap)** — https://developers.facebook.com/docs/permissions/reference
- **Facebook Pages API (feed, photos, videos)** — https://developers.facebook.com/docs/pages-api
- **Webhooks (opsional, notifikasi real-time)** — https://developers.facebook.com/docs/graph-api/webhooks
- **Meta App Dashboard (buat App ID/Secret)** — https://developers.facebook.com/apps/
- **Graph API Explorer (untuk testing manual sebelum coding)** — https://developers.facebook.com/tools/explorer/
- **Access Token Debugger** — https://developers.facebook.com/tools/debug/accesstoken/

> **Catatan penting:** Meta rutin mengganti versi Graph API dan men-deprecate endpoint lama (misalnya migrasi objek legacy ke `IG User`/`IG Media`/`IG Comment` per April 2025). Sebelum/selama implementasi, cek versi API terbaru yang stabil di [Graph API Changelog](https://developers.facebook.com/docs/graph-api/changelog) dan gunakan versi tersebut secara konsisten di seluruh endpoint (`{VERSION}` pada semua URL di atas, contoh: `v22.0`, `v23.0`, dst — jangan hardcode versi lama dari dokumen ini tanpa verifikasi ulang).

---

## 11. INSTRUKSI UNTUK ANTIGRAVITY

Bangun aplikasi ini step-by-step dengan urutan:
1. Setup project Laravel 11 + struktur database (migration untuk: users, meta_apps/credentials, connected_pages, connected_ig_accounts, media_pool, project_campaigns, **campaign_targets** [pivot akun + platform_target, lihat Bagian 2.1], campaign_media, publish_logs [dicatat per target per platform]).
2. Implementasi halaman "Pengaturan Integrasi Meta API" (Bagian 7) lengkap dengan OAuth flow.
3. Implementasi CRUD Project Campaign dengan seluruh field (media pool, **multi-select target akun + pilihan platform per akun sesuai Bagian 2.1**, jadwal, exclude days, repeat mode) — replikasi UI/UX dari deskripsi Bagian 2 & 3.
4. Implementasi Full Sync job (Bagian 5).
5. Implementasi Publish Engine via Graph API (Bagian 4) sebagai Laravel Job terjadwal — loop per `campaign_targets` dan hormati `platform_target` (skip FB atau skip IG sesuai pilihan), termasuk rate limit tracking dan error handling.
6. Implementasi dashboard monitoring (status publish **per target per platform** — sukses/gagal/di-skip, badge aset nonaktif, sisa kuota API per akun).

Jangan buat asumsi versi Graph API atau endpoint tanpa verifikasi — jika ragu terhadap detail endpoint terbaru (misal dukungan Story API untuk FB Page), tandai sebagai TODO untuk divalidasi manual terhadap dokumentasi resmi Meta for Developers saat implementasi.
