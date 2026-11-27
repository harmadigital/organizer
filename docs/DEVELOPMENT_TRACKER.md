# Organizer — Development Tracker

**Terakhir diperbarui:** 27 November 2026
**Fase saat ini:** Phase 1, Batch 1.4 menunggu review (belum di-commit)
**Dokumen terkait:** [organizer.md](organizer.md) (master spec), [ui-ux_brief.md](ui-ux_brief.md) (acuan UI/UX), [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md), [DATA_MODEL.md](DATA_MODEL.md)

Dokumen ini diperbarui di akhir setiap batch: status, migration, test yang benar-benar dijalankan beserta hasilnya, blocker, dan known issues.

Status yang dipakai: **Belum mulai**, **Dikerjakan**, **Menunggu review**, **Selesai**, **Terblokir**.

---

## 1. Status fase

| Fase | Status | Catatan |
|---|---|---|
| Phase 0 — Audit dan perencanaan | Selesai | Disetujui 27 Nov 2026 bersama K-01 sampai K-04 |
| Phase 1 — Core committee management | Dikerjakan | Batch 1.0–1.3 selesai; Batch 1.4 menunggu review; Batch 1.5 berikutnya |
| Phase 2 — Finance, governance, reporting | Belum mulai | – |
| Phase 3 — Advanced operational workflows | Belum mulai | Belum dirinci |
| Phase 4 — Organizational intelligence | Belum mulai | Belum dirinci |

---

## 2. Status batch

| Batch | Lingkup | Status | Skenario AC | Blocker |
|---|---|---|---|---|
| 1.0 | Fondasi: locale, audit log, helper AJAX, lapisan `ui-*`, seeder aman, baseline test | Selesai (disetujui 27 Nov 2026) | – | – |
| 1.1 | Organisasi dan master data, ditambah penggabungan Anggota ke User (bagian 11) | Selesai (di-push ke `main` 27 Nov 2026) | – | – |
| 1.2 | Event, kepanitiaan, kontrol akses | Selesai (di-merge ke `main` 27 Nov 2026) | AC-01, AC-02, AC-03 | – |
| 1.3 | Program kerja, tugas, aktivitas, inti lampiran | Selesai (di `main`, commit 32e574a) | AC-01 (diperluas) | – |
| 1.4 | Rapat, notulen, keputusan | Menunggu review (di `main`, belum di-commit) | AC-05 | – |
| 1.5 | Kalender terpusat | Belum mulai | AC-20 (sebagian) | – |
| 1.6 | Koordinasi, isu, risiko | Belum mulai | AC-06 | – |
| 1.7 | Repositori dokumen | Belum mulai | AC-18 (sebagian) | – |
| 1.8 | Notifikasi, pengumuman, komentar | Belum mulai | – | – |
| 1.9 | Worksheet per peran, shell PWA | Belum mulai | AC-14 (sebagian), AC-22 | – |
| 2.1 | Approval engine, delegasi, surat | Belum mulai | AC-12 | K-09, K-12 |
| 2.2 | Anggaran (RAB) | Belum mulai | AC-07 | K-11 |
| 2.3 | Ledger, akun kas/bank, penerimaan | Belum mulai | AC-11, AC-21 | K-10 |
| 2.4 | Pengajuan dana, pencairan, settlement | Belum mulai | AC-08, AC-09 | K-05 |
| 2.5 | Pengadaan dasar, komitmen, kewajiban | Belum mulai | AC-10, AC-19 | – |
| 2.6 | Rekonsiliasi, closing keuangan | Belum mulai | AC-16 (keuangan) | K-10 |
| 2.7 | Temuan pengawas, readiness | Belum mulai | AC-13, AC-17 | – |
| 2.8 | Laporan seksi, evaluasi, report engine | Belum mulai | AC-18 | Persetujuan paket PDF/Excel |
| 2.9 | Proposal, LPJ, closing event, arsip, clone | Belum mulai | AC-04, AC-15, AC-16 | K-12 |

---

## 3. Skenario acceptance (spec bagian 13)

| ID | Skenario | Batch | Status |
|---|---|---|---|
| AC-01 | Akses lintas event lewat URL, API, file, export | 1.2, diperluas tiap batch | Lulus (test otomatis, Batch 1.2; Batch 1.3 menambah program, tugas, catatan, dan unduhan lampiran) |
| AC-02 | User dengan jabatan berbeda di dua event | 1.2 | Lulus (test otomatis, Batch 1.2) |
| AC-03 | Aktivasi tanpa jabatan wajib; event tanpa wakil | 1.2 | Lulus (test otomatis, Batch 1.2) |
| AC-04 | Clone event | 2.9 | Belum |
| AC-05 | Notulen dipublikasikan, action item menjadi tugas | 1.4 | Lulus (test otomatis dan uji browser, Batch 1.4) |
| AC-06 | Permintaan mikrofon antar-seksi | 1.6 | Belum |
| AC-07 | RAB diajukan, direview, disahkan | 2.2 | Belum |
| AC-08 | Dua pengajuan bersamaan melebihi sisa anggaran | 2.4 | Belum |
| AC-09 | Uang muka Rp10 jt, biaya Rp8 jt, refund Rp2 jt | 2.4 | Belum |
| AC-10 | Pesanan, invoice, pembayaran vendor | 2.5 | Belum |
| AC-11 | Transfer internal antar-akun | 2.3 | Belum |
| AC-12 | Self-approval, tanpa wewenang, delegasi kedaluwarsa | 2.1 | Belum |
| AC-13 | 95% tugas selesai, checklist venue kritis belum | 2.7 | Belum |
| AC-14 | Unggah dari mobile, koneksi putus, coba ulang | 1.9, diperluas Phase 3 | Belum |
| AC-15 | LPJ disahkan lalu master data diedit | 2.9 | Belum |
| AC-16 | Closing dengan uang muka atau laporan belum beres | 2.6, 2.9 | Belum |
| AC-17 | Pengawas mencatat temuan | 2.7 | Belum |
| AC-18 | Export data donatur/insiden/lampiran privat | 1.7, 2.8 | Belum |
| AC-19 | Bentrok booking venue/aset | 2.5 | Belum |
| AC-20 | Kalender mencerminkan tugas, rapat, rundown | 1.5; rundown di Phase 3 | Belum |
| AC-21 | Koreksi transaksi terposting | 2.3 | Belum |
| AC-22 | Logout PWA / jabatan dicabut | 1.9 | Belum |

---

## 4. Keputusan yang menunggu konfirmasi

Rincian pertanyaan dan asumsi kerja ada di [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) bagian 10.

| ID | Topik | Blokir | Status | Jawaban |
|---|---|---|---|---|
| K-01 | Pola frontend modul baru | 1.0 | Disetujui 27 Nov 2026 | Sesuai asumsi kerja |
| K-02 | Form Request, kode HTTP, tanpa escape saat input | 1.0 | Disetujui 27 Nov 2026 | Sesuai asumsi kerja |
| K-03 | Tabel otorisasi event sendiri vs teams spatie | 1.2 | Disetujui 27 Nov 2026 | Sesuai asumsi kerja |
| K-04 | Mengubah `UserSeeder` dan test terkait | 1.0 | Disetujui 27 Nov 2026 | Sesuai asumsi kerja |
| K-05 | Database MySQL untuk test konkurensi | 2.1, 2.4 | Menunggu | |
| K-06 | Jabatan wajib dan jumlah pemegang | 1.2 | Asumsi kerja dipakai (27 Nov 2026) | Dapat diubah; jabatan wajib adalah konfigurasi per jabatan |
| K-07 | Jumlah seksi minimum | 1.2 | Asumsi kerja dipakai (27 Nov 2026) | Dapat diubah; jabatan wajib adalah konfigurasi per jabatan |
| K-08 | Cara pembuatan akun dan undangan | 1.1, 1.2 | Asumsi kerja dipakai (27 Nov 2026) | Dapat diubah; jabatan wajib adalah konfigurasi per jabatan |
| K-09 | Ambang approval, rantai, quorum | 2.1 | Menunggu | |
| K-10 | Kepemilikan akun kas/bank, basis pengakuan | 2.3–2.6 | Menunggu | |
| K-11 | Uang sebagai integer rupiah utuh | 2.2 | Menunggu | |
| K-12 | Penomoran surat, template, pengesahan | 2.1, 2.9 | Menunggu | |
| K-13 | Prioritas seksi khusus, registrasi, QR | Phase 3 | Menunggu | |
| K-14 | Kapasitas, perangkat, hosting, backup | Deploy | Menunggu | |
| K-15 | Anggota dan User digabung atau tetap terpisah (pertanyaan pemilik produk 27 Nov 2026) | 1.2 | Diputuskan 27 Nov 2026 | Opsi B: digabung ke `users`, akses login opsional. Rincian di bagian 11 |

---

## 5. Temuan audit pada boilerplate

Kolom terakhir menunjukkan status tiap temuan setelah Batch 1.0.

| No | Temuan | Lokasi | Dampak | Status |
|---|---|---|---|---|
| T-01 | Satu test gagal karena `public/build/manifest.json` tidak ada | `tests/Feature/PasswordConfirmationTest.php` | Baseline test tidak hijau | **Selesai (1.0):** `withoutVite()` di `tests/TestCase.php` |
| T-02 | Password dan konfirmasinya ikut tertulis ke log saat profil diperbarui (`Log::info(..., $request->all())`) | `app/Http/Controllers/ProfileController.php` | Kebocoran rahasia lewat log | **Selesai (1.0):** log hanya memuat ID user dan nama kolom yang berubah |
| T-03 | Password difilter `trim` dan `escape` (`strip_tags` + `htmlspecialchars`) sebelum di-hash, sehingga password berisi karakter seperti `<` atau `&` tersimpan berbeda dari yang diketik | `app/Http/Controllers/Master/UserController.php` | User bisa gagal login dengan password yang benar | **Selesai (1.1):** password di-hash persis seperti diketik, pada tambah dan ubah user |
| T-04 | `php artisan db:refresh` menjalankan `migrate:refresh` | `app/Console/Commands/RefreshDatabaseCommand.php` | Menghapus seluruh data jika dijalankan | **Selesai (1.0):** hanya berjalan di `local`/`testing`; `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe` ditolak di production |
| T-05 | Seeder menghapus semua permission sebelum membuat ulang | `database/seeders/UserSeeder.php` | Menghapus pemetaan permission role lain | **Selesai (1.0):** `PermissionSeeder` idempoten; hanya empat nama legacy yang dihapus |
| T-06 | Seeder membuat akun admin dengan kredensial tetap | `database/seeders/UserSeeder.php` | Tidak aman bila dijalankan di produksi | **Selesai (1.0):** dari `SEED_ADMIN_*`; password default hanya di `local`/`testing` |
| T-07 | Route `GET /` didefinisikan dua kali | `routes/web.php` | Definisi pertama tidak pernah terpakai | **Selesai (1.0)** |
| T-08 | `DashboardController` memiliki method yang merujuk view yang tidak ada (`mfa`, `auth.verify-2fa`) dan tidak punya route | `app/Http/Controllers/DashboardController.php` | Kode mati | **Terbuka**, dibiarkan |
| T-09 | Error validasi dan kegagalan proses dikembalikan dengan HTTP 200 | Controller master | Sulit dibedakan oleh klien dan test | Halaman lama dibiarkan; modul baru mengikuti K-02 |
| T-10 | Timezone `UTC` tertulis langsung di `config/app.php`, locale `en`, folder `lang/` tidak ada | `config/app.php` | Tanggal dan pesan belum sesuai kebutuhan | **Selesai (1.0):** locale `id`, `lang/id`, `DisplayTime` (Asia/Jakarta). Penyimpanan tetap UTC |
| T-11 | Paket Excel disebut di commit `db883a4` tetapi tidak ada di `composer.json`; paket PDF juga tidak ada | `composer.json` | Export belum memungkinkan | 2.8 |
| T-12 | Sisa scaffolding Jetstream yang tidak dipakai (view teams dan API token, `navigation-menu`, `login_old`, kolom `users.current_team_id`) | `resources/views/**` | Kebingungan, tidak berbahaya | Dibiarkan |
| T-13 | Kickoff prompt di spec bagian 15 merujuk `docs/ORGANIZER_MASTER_SPEC.md`, sedangkan filenya `docs/organizer.md` | `docs/organizer.md` | Rujukan tidak cocok | Ganti nama file atau perbarui rujukan |
| T-14 | Registrasi, reset password, dan verifikasi email nonaktif | `config/fortify.php` | Alur undangan butuh setidaknya reset password | 1.1 (K-08) |
| T-16 | Mengubah user tanpa mengirim field `permission` menghasilkan error 500 (`Undefined array key`). Halaman yang ada selalu mengirim field itu, jadi tidak terpicu dari UI | `app/Http/Controllers/Master/UserController.php` | Rapuh bila dipanggil klien lain | **Selesai:** controller User ditulis ulang |
| T-17 | Halaman login memuat reCAPTCHA dengan site key kosong dan memunculkan error JavaScript (`Invalid reCAPTCHA client id`). Login tetap berfungsi | `resources/views/auth/login.blade.php` | Error di konsol; reCAPTCHA tidak benar-benar aktif | **Terbuka** |
| T-19 | Halaman Role lama: role bisa dihapus walau masih dipegang user, role Super Admin bisa diganti nama atau dihapus, dan pengubah role bisa menambahkan permission yang tidak ia miliki | `app/Http/Controllers/Master/RoleController.php` | Kehilangan akses diam-diam; seeder gagal menemukan role; eskalasi hak | **Selesai:** halaman Role dibangun ulang (bagian 11) |
| T-18 | Logo sidebar merujuk `assets/media/logo-long-black.png` yang tidak ada, sehingga tampil sebagai gambar rusak | `resources/views/layouts/main_layout.blade.php` | Tampilan | **Terbuka**, menunggu file logo |
| T-15 | Direktori `.ai/rules` belum ada | – | Konvensi modul baru belum terekam untuk agen lain | **Selesai (1.0):** 8 aturan dicatat lewat `record-rule` |

---

## 6. Baseline test

| Tanggal | Perintah | Hasil |
|---|---|---|
| 27 Nov 2026 (Phase 0) | `php artisan test --compact` | 36 test: 23 lulus, 12 dilewati, 1 gagal (T-01) |
| 27 Nov 2026 (Batch 1.0) | `php artisan test --compact` | 82 test: 70 lulus, 12 dilewati, 0 gagal (200 asersi) |
| 27 Nov 2026 (Batch 1.0) | `vendor/bin/pint --dirty --format agent` | Bersih |
| 27 Nov 2026 (Batch 1.1) | `php artisan test --compact` | 275 test: 263 lulus, 12 dilewati, 0 gagal (1.044 asersi) |
| 27 Nov 2026 (Batch 1.1) | `vendor/bin/pint --dirty --format agent` | Bersih |
| 27 Nov 2026 (Batch 1.1) | Chrome headless terhadap database SQLite sementara, 39 pemeriksaan | Lulus penuh pada 3 run terakhir berturut-turut; rincian di bagian 10 |
| 27 Nov 2026 (K-15 + Role) | `php artisan test --compact` | 279 test: 267 lulus, 12 dilewati, 0 gagal (1.171 asersi) |
| 27 Nov 2026 (K-15 + Role) | Chrome headless, 57 pemeriksaan termasuk login akun baru dan pencabutan akses di sesi browser kedua | Lulus penuh |
| 27 Nov 2026 (Demo) | `php artisan test --compact` | 286 test: 274 lulus, 12 dilewati, 0 gagal (1.201 asersi) |
| 27 Nov 2026 (Batch 1.2) | `php artisan test --compact` | 345 test: 333 lulus, 12 dilewati, 0 gagal |
| 27 Nov 2026 (Batch 1.2) | Chrome headless, 36 pemeriksaan alur event dan kepanitiaan, dua akun | Lulus penuh |

12 test yang dilewati adalah test bawaan Jetstream untuk fitur yang dinonaktifkan (registrasi, reset password, verifikasi email, API token).

Pengecekan browser dilakukan mulai Batch 1.1 (bagian 10). Skrip uji browser bersifat sementara dan tidak disimpan di repo.

---

## 7. Migration

| Batch | Migration | Dijalankan di dev | Catatan rollback |
|---|---|---|---|
| Boilerplate | 6 migration bawaan (users, cache, jobs, two factor, personal access tokens, permission) | Ya, batch 1 | – |
| 1.0 | `2026_11_27_080401_create_audit_logs_table` | Ya, batch 2 (dijalankan pemilik repo) | `down()` menghapus tabel `audit_logs`. Tabel baru, tidak menyentuh tabel lain |
| K-15 | `2026_11_27_085355_drop_members_table`, `2026_11_27_085356_add_directory_columns_to_users_table` | Ya, batch 3 (dijalankan agen atas keputusan opsi B; `members` kosong saat itu) | Drop menolak jalan bila `members` berisi. Rollback `users` menolak jalan selama ada user tanpa username, email, atau password |
| 1.2 | `create_events_table`, `create_event_objectives_table`, `create_event_status_histories_table`, `create_event_role_rules_table`, `create_divisions_table`, `create_event_memberships_table`, `create_event_role_assignments_table` | Ya, batch 4 (dijalankan pemilik repo) | Tujuh tabel baru; `migrate:rollback --step=7` sudah diuji |
| 1.1 | `create_organizations_table`, `create_organization_units_table`, `create_members_table`, `create_committee_roles_table`, `create_division_templates_table`, `create_event_categories_table`, `create_venues_table`, `create_committee_role_abilities_table` (27 Nov 2026) | Ya, batch 2 (dijalankan pemilik repo) | Delapan tabel baru. Rollback harus berurutan terbalik karena foreign key; `migrate:rollback --step=8` sudah diuji |

Semua migration sudah diuji naik, turun, dan naik lagi pada berkas SQLite sementara. Pernyataan SQL MySQL untuk Batch 1.0 diperiksa dengan `migrate --pretend`; untuk Batch 1.1 struktur tabel `members` di MySQL dicek langsung setelah migration dijalankan.

Seeder (`php artisan db:seed`) dijalankan di database dev pada 27 Nov 2026 setelah menu baru dilaporkan tidak muncul: permission bertambah dari 14 menjadi 40 dan role `Organization Admin` dibuat. Tidak ada data yang dihapus.

---

## 8. Log perubahan

| Tanggal | Fase/Batch | Perubahan |
|---|---|---|
| 27 Nov 2026 | Phase 0 | Audit repository. Membuat `IMPLEMENTATION_PLAN.md`, `DATA_MODEL.md`, `DEVELOPMENT_TRACKER.md`. Tidak ada perubahan source code, dependency, atau database |
| 27 Nov 2026 | Review | K-01 sampai K-04 disetujui. `ui-ux_brief.md` ditetapkan sebagai acuan UI/UX |
| 27 Nov 2026 | Batch 1.0 | Fondasi dikerjakan, rincian di bagian 9. Tidak ada dependency baru. Database dev tidak disentuh |
| 27 Nov 2026 | Review | Batch 1.0 disetujui. Perbaikan T-03 disetujui. Asumsi kerja K-06 sampai K-08 dipakai |
| 27 Nov 2026 | Batch 1.1 | Organisasi dan master data dikerjakan, rincian di bagian 10. Tidak ada dependency baru |
| 27 Nov 2026 | Batch 1.1 | Atas permintaan pemilik produk: User dan Role & Permission dipindah ke grup menu Master Data |
| 27 Nov 2026 | K-15 | Opsi B dipilih: Anggota digabung ke User, layar dan tabel `members` dibongkar. Rincian di bagian 11 |
| 27 Nov 2026 | Menu | Atas permintaan pemilik produk: grup menu baru "User & Akses" di bawah Dashboard berisi User dan Role & Permission |
| 27 Nov 2026 | Role | Atas permintaan pemilik produk: halaman Role & Permission dibangun ulang dengan pola UI baru |
| 27 Nov 2026 | Demo | `DemoSeeder` dibuat dan dijalankan di database dev atas permintaan pemilik produk untuk trial |
| 27 Nov 2026 | Batch 1.2 | Event, kepanitiaan, dan hak akses per event. Rincian di bagian 13 |
| 27 Nov 2026 | Menu | Atas permintaan pemilik produk: grup Organisasi menjadi Master Data (item datar, termasuk Profil Organisasi); grup Event (Event, Kategori Event) di paling bawah |
| 27 Nov 2026 | UI | Atas permintaan pemilik produk: semua tanggal memakai flatpickr, semua dropdown memakai select2 (autocomplete untuk data dari server), form event menjadi stepper, bagan organisasi di modal layar penuh, susunan panitia sebagai tree view, menu event dihapus dari sidebar. Rincian di bagian 14 |
| 27 Nov 2026 | Batch 1.3 | Program kerja, tugas, catatan kegiatan, dan lampiran bukti. Permission baru `events.manage-work`. Rincian di bagian 15 |
| 27 Nov 2026 | Batch 1.3 | Atas permintaan pemilik produk: alur tugas disederhanakan menjadi "Tandai Selesai" dengan hasil dan foto/dokumen, dan halaman Program & Tugas didesain ulang untuk panitia awam. Rincian di bagian 16 |
| 27 Nov 2026 | Batch 1.3 | Atas permintaan pemilik produk: review/persetujuan dihapus, semua panitia melihat semua tugas, aksi tugas hanya Selesai dan Ubah, ditambah komentar. Perbaikan kalender di dalam modal. Rincian di bagian 17 |
| 27 Nov 2026 | Batch 1.3 | Atas permintaan pemilik produk: halaman detail tugas dibagi ke dalam tab. Rincian di bagian 18 |
| 27 Nov 2026 | Batch 1.3 | Atas permintaan pemilik produk: istilah awam untuk bobot dan milestone. Rincian di bagian 19 |
| 27 Nov 2026 | Batch 1.3 | Program kerja Pengurus Inti dan tugas milik sendiri untuk Bendahara. Rincian di bagian 20 |
| 27 Nov 2026 | Batch 1.3 | Di-commit ke `main` (32e574a). Atas permintaan pemilik produk, commit selanjutnya langsung di `main` tanpa branch per push |
| 27 Nov 2026 | Batch 1.4 | Rapat & Notulen tanpa undangan, RSVP, atau review. Rincian di bagian 21 |
| 27 Nov 2026 | Batch 1.4 | Atas permintaan pemilik produk: editor notulen lengkap, unduh PDF, tab Absensi. Dependency baru `dompdf/dompdf` dan `symfony/html-sanitizer` (disetujui). Rincian di bagian 22 |
| 27 Nov 2026 | Batch 1.4 | Atas permintaan pemilik produk: form notulen bertab, absensi terkunci setelah rapat selesai, tampilan absensi diperbaiki. Rincian di bagian 23 |

---

## 9. Hasil Batch 1.0 — Fondasi

### Yang dibuat

| Area | Perubahan | File utama |
|---|---|---|
| Audit | Tabel `audit_logs` (append-only), model, factory, `AuditLogger` dengan penyamaran field sensitif dan hanya mencatat atribut yang berubah | `app/Services/AuditLogger.php`, `app/Models/AuditLog.php` |
| Correlation ID | Middleware global yang memberi tiap request satu ID, dikirim balik di header `X-Correlation-ID` dan ikut ke log serta job | `app/Http/Middleware/AssignCorrelationId.php` |
| Konvensi respons | Base Form Request untuk AJAX (422 dengan envelope) dan helper `jsonSuccess()` / `jsonFailure()` | `app/Http/Requests/JsonFormRequest.php`, `app/Http/Controllers/Controller.php` |
| Helper AJAX | `OrganizerHttp`: CSRF, error per field, pesan 403/409/419/jaringan dalam bahasa Indonesia, indikator tombol, konfirmasi | `public/assets/js/organizer/http.js` |
| UI | Lapisan `ui-*` dari brief; `@stack('vendor_css')` dan `@stack('vendor_js')` di layout; menu sidebar dipindah ke partial | `public/assets/css/ui.css`, `resources/views/layouts/partials/sidebar-menu.blade.php` |
| Bahasa dan waktu | Locale `id`, pesan validasi/login/reset password berbahasa Indonesia, `DisplayTime` untuk menampilkan waktu di zona organisasi | `lang/id/*`, `app/Services/DisplayTime.php`, `config/organizer.php` |
| Seeder | `PermissionSeeder` idempoten dan tidak destruktif; akun admin dari konfigurasi | `database/seeders/*` |
| Pengaman | `db:refresh` hanya di `local`/`testing`; perintah yang menghapus tabel ditolak di production; morph map dibatasi | `app/Providers/AppServiceProvider.php` |
| Perbaikan | Password tidak lagi masuk log; route `/` ganda dihapus; test tidak lagi bergantung pada build Vite | `app/Http/Controllers/ProfileController.php`, `routes/web.php`, `tests/TestCase.php` |
| Aturan bersama | 8 aturan di `.ai/rules` | `.ai/rules/index.md` |

Perubahan lingkungan lokal: `APP_LOCALE` di `.env` diubah dari `en` ke `id`. Variabel baru yang opsional ada di `.env.example` (`ORGANIZER_TIMEZONE`, `SEED_ADMIN_*`).

### Keterbatasan dan hal yang belum dilakukan

- Migration `audit_logs` belum dijalankan di database dev.
- `OrganizerHttp` dan `ui.css` belum dipakai halaman mana pun dan belum dilihat di browser; pemakai pertamanya adalah halaman master di Batch 1.1.
- `AuditLogger` belum dipanggil dari kode produksi; halaman master user/role lama belum diaudit.
- Halaman yang sudah ada (dashboard, master user, master role) belum diubah ke pola `ui-*`.
- `flatpickr-id.js` dan CSS perbaikan modal yang disebut brief tidak ada di repo ini.
- T-03 (password difilter sebelum di-hash) dan T-08 (kode mati di `DashboardController`) masih terbuka.
- Pint ikut merapikan format method lama di `Controller.php` dan import yang tidak terpakai di `ProfileController.php`; perilakunya tidak berubah.

### Dependency berikutnya

Batch 1.1 (organisasi dan master data) bergantung pada K-08 (cara pembuatan akun dan undangan). Tanpa jawaban, asumsi kerja dipakai.

---

## 10. Hasil Batch 1.1 — Organisasi dan master data

### Yang dibuat

| Layar | Isi | Route |
|---|---|---|
| Profil Organisasi | Identitas, kontak, zona waktu. Simpan pertama membuat organisasi beserta tujuh jabatan panitia bawaan | `organization/profile` |
| Anggota | Direktori orang yang dapat ditugaskan; akun login opsional dan dapat ditautkan; kartu ringkasan; filter unit, status, akun | `members/*` |
| Unit Pelayanan | Komisi/bidang, bisa bertingkat, tanpa siklus | `master/organization-unit/*` |
| Jabatan Panitia | Jabatan bawaan dan tambahan, lingkup event/seksi, aturan bawaan, pemetaan hak akses event | `master/committee-role/*` |
| Template Seksi | Nama, deskripsi, tanggung jawab bawaan | `master/division-template/*` |
| Kategori Event | Nama dan deskripsi | `master/event-category/*` |
| Venue | Nama, alamat, kapasitas, catatan | `master/venue/*` |

Hal yang berlaku di semua layar:

- **Hak akses:** 26 permission tingkat organisasi baru dan role `Organization Admin`. Setiap route memakai middleware `can:`; tombol tambah, ubah, dan hapus hanya tampil bagi yang berhak.
- **Isolasi:** data difilter per organisasi, dan ID milik organisasi lain menjawab 404.
- **Hapus aman:** data yang masih dirujuk data lain tidak bisa dihapus (jawaban 409 dengan anjuran menonaktifkan); jabatan bawaan tidak bisa dihapus.
- **Audit:** tambah, ubah, dan hapus tercatat di `audit_logs` dengan nilai sebelum dan sesudah.
- **Tampilan:** mengikuti pola daftar di `ui-ux_brief.md` (header, sub-navigasi, toolbar, tabel, modal, modal panduan).
- **Pemisahan kewenangan:** jabatan Pengawas tidak bisa diberi hak yang mengubah pekerjaan panitia; server menolaknya, bukan hanya form.

Selain itu: T-03 diperbaiki, `DisplayTime` kini mengikuti zona waktu organisasi, dan pencarian memperlakukan `%` dan `_` sebagai karakter biasa di MySQL maupun SQLite.

### Hasil uji browser

Chrome headless dijalankan terhadap server lokal dengan database SQLite sementara. Alur yang diperiksa: login, pengalihan ke profil saat organisasi belum ada, validasi dan simpan profil, daftar jabatan bawaan, modal jabatan Pengawas (hak terlarang terkunci), tambah jabatan, siklus penuh venue (kosong, validasi, tambah, ubah, nonaktif, filter, cari, batal hapus, hapus), unit bertingkat dan penolakan hapus, anggota dengan unit dan akun, lebar layar ponsel, dan ketiadaan error JavaScript.

- Tiga run terakhir lulus penuh (39 pemeriksaan).
- Satu run sebelumnya berhenti karena batas waktu tunggu 8 detik pada halaman jabatan; tidak terulang dan penyebabnya tidak dipastikan.
- Uji ini menemukan dua hal yang lalu diperbaiki: pesan tabel kosong tidak membedakan "belum ada data" dari "tidak ada yang cocok", dan modal tidak terbuka bila diminta saat animasi menutup masih berjalan.
- **Insiden:** run pertama tanpa sengaja memakai database dev, karena `php artisan serve` membuang variabel `DB_*` dari shell kecuali diberi `--no-reload`. Yang terjadi hanya login sebagai admin lalu skrip berhenti; jumlah baris organisasi, anggota, venue, jabatan, dan audit diperiksa tetap nol sesudahnya. Jebakan ini dicatat di `.ai/rules/tests.md`.

### Menyimpang dari rencana awal

| Rencana | Yang dibuat | Alasan |
|---|---|---|
| `organizations.locale` dan `document_identity` | Belum dibuat | Belum ada pemakainya. Identitas dokumen (kop, logo) ditambahkan bersama surat di Batch 2.1 |
| `division_templates.type_key` | Belum dibuat | Hanya dipakai worksheet seksi khusus di Phase 3 |
| Soft delete pada `members` dan `venues` | Tidak dipakai | Diganti nonaktif + hapus hanya bila tidak dirujuk, agar indeks unik tetap sederhana |
| Ability hanya dipetakan | Katalog `EventAbility` untuk Phase 1 (25 ability) dibuat sekarang | Layar jabatan butuh daftar ability. Penegakannya baru mulai Batch 1.2 |

### Keterbatasan

- Pemetaan hak akses jabatan sudah tersimpan tetapi belum berpengaruh sampai `EventAccess` dibuat di Batch 1.2.
- Undangan anggota lewat email belum ada (Batch 1.2); saat ini akun ditautkan dari akun yang sudah dibuat di layar User.
- Riwayat kepanitiaan anggota, import/export, serta master vendor, aset, satuan, dan kategori keuangan belum ada (batch terkait).
- Layar User dan Role lama belum memakai gaya `ui-*` dan belum diaudit.
- `flatpickr-id.js` dan CSS perbaikan modal dari brief belum ada; modal `modal-lg` di Batch 1.1 tampil baik di desktop dan ponsel tanpa itu.

### K-15: Anggota dan User

Diputuskan 27 Nov 2026: opsi B (digabung). Lihat bagian 11.

---

## 11. Penggabungan Anggota ke User (K-15) dan halaman Role

### Keputusan

Pemilik produk memilih opsi B: satu tabel `users` untuk semua orang. Karena spec MOD-01 melarang mewajibkan akun bagi semua orang, varian yang dipakai adalah **akses login opsional**: seseorang bisa dicatat tanpa username dan password, lalu diberi akses login kapan saja.

### Yang berubah

| Area | Perubahan |
|---|---|
| Data | Tabel `members` dihapus. `users` mendapat `phone`, `organization_unit_id`, `status`, `notes`; `username`, `email`, `password` menjadi opsional. `users` tidak diberi `organization_id` (satu organisasi) |
| Login | Hanya user aktif yang punya username dan password yang bisa login (`Fortify::authenticateUsing`). Middleware `EnsureUserCanSignIn` mengeluarkan sesi yang sedang berjalan bila user dinonaktifkan atau aksesnya dicabut |
| Layar User | Satu layar: data orang (nama, kontak, unit, status) ditambah bagian Akses Login (username, kata sandi, role, reset 2FA). Kartu ringkasan, filter unit/role/status/akses |
| Hak akses | Permission baru `users.manage-access` untuk bagian Akses Login; `members.*` dihapus. Role `Organization Admin` kini boleh mengelola user dan aksesnya |
| Keamanan | `AccountAuthority`: siapa pun tidak bisa memberi role, atau mengatur akun, yang hak aksesnya melebihi hak aksesnya sendiri. Tidak ada yang bisa menonaktifkan, mencabut akses, mengganti role, atau menghapus akunnya sendiri |
| Menu | Grup baru "User & Akses" tepat di bawah Dashboard berisi User dan Role & Permission. Grup "Organisasi" kini berisi Profil Organisasi dan Master Data. Menu Anggota dihapus |
| Halaman Role | Dibangun ulang dengan pola brief (header, kartu, toolbar, tabel, satu modal). Picker permission dikelompokkan per area dan mengunci permission yang tidak dimiliki pengguna. Backend: Form Request, kode HTTP yang semestinya, audit `roles.*` |
| Aturan role | Role yang masih dipegang user tidak bisa dihapus. Role bawaan (Super Admin, Organization Admin) tidak bisa diganti nama atau dihapus; Super Admin tidak bisa diubah sama sekali. Permission yang diberikan harus dimiliki pemberinya |

Fitur halaman User lama yang tidak dibawa: permission langsung per user ("permission tambahan"). Akses kini hanya lewat role. Data permission langsung yang sudah ada di database tidak dihapus, tetapi tidak lagi bisa diatur dari layar.

### Dampak ke rencana

- Batch 1.2 merujuk `user_id`, bukan `member_id`: `event_memberships` menjadi `(event_id, user_id)`.
- Undangan (Batch 1.2) cukup mengaktifkan akses login untuk user yang sudah tercatat.

### Keterbatasan

- `PermissionController` dan halamannya masih memakai pola lama; menunya tetap tersembunyi seperti sebelumnya.
- Mengubah permission role `Organization Admin` dari layar tetap dipertahankan, tetapi seeder akan menambahkan kembali permission bawaannya bila dijalankan ulang.

---

## 12. Data demo untuk trial

`php artisan db:seed --class=DemoSeeder` mengisi semua modul yang sudah ada dengan data fiktif. Seeder ini tidak dipanggil oleh `DatabaseSeeder`, aman dijalankan ulang (hanya menambah yang belum ada), tidak mengubah profil organisasi yang sudah diisi, dan menolak jalan di luar lingkungan `local`/`testing`.

| Modul | Isi |
|---|---|
| Profil Organisasi | Dipakai yang sudah ada; bila belum ada, dibuat "Gereja Kasih Karunia (Demo)" |
| Jabatan Panitia | Tujuh jabatan bawaan |
| Unit Pelayanan | 7 unit, bertingkat di bawah Bidang Pembinaan, 1 nonaktif |
| User | 12 orang dengan email `@demo.organizer.test`: 4 punya akses login, 1 nonaktif, sisanya tanpa login |
| Role | Role contoh "Admin Sekretariat" (lihat data, kelola user dan venue) |
| Template Seksi | 10 seksi dari spec bagian 4.1 dengan tanggung jawab bawaan |
| Kategori Event | 6 kategori, 1 nonaktif |
| Venue | 6 venue dengan kapasitas, 1 nonaktif |

Akun demo (kata sandi `Demo-2026!`, khusus lingkungan lokal):

| Username | Role | Untuk mencoba |
|---|---|---|
| `demo.admin` | Organization Admin | Kelola organisasi, master data, dan user; tidak bisa mengubah Role |
| `demo.sekretariat` | Admin Sekretariat | Hak terbatas: venue bisa diubah, master lain hanya dilihat |
| `demo.bendahara`, `demo.pemuda` | Tanpa role organisasi | Login tanpa menu admin; nantinya mendapat hak lewat jabatan panitia (Batch 1.2) |

Dijalankan di database dev pada 27 Nov 2026: organisasi yang sudah ada dipakai, sehingga database kini berisi 13 user (12 demo + admin).

---

## 13. Hasil Batch 1.2 — Event, kepanitiaan, dan hak akses

### Yang dibuat

| Area | Isi |
|---|---|
| Event | Buat dan ubah event: identitas, tanggal, masa panitia, venue/lokasi, unit dan penanggung jawab, target peserta dan anggaran, tujuan/sasaran/indikator. Kode otomatis `EVT-<tahun>-<urut>` bila dikosongkan |
| Status dan fase | Draf → Aktif → Selesai → Arsip, Batal dari Draf/Aktif, buka kembali dengan alasan. Fase bebas berpindah. Semua perubahan tercatat di riwayat status dan audit |
| Gate aktivasi (AC-03) | Event tidak bisa diaktifkan selama ada jabatan wajib yang belum dipegang panitia yang sudah bersedia; pesan menyebut jabatannya. Wakil Ketua tidak wajib |
| Aturan jabatan per event | Salinan aturan "wajib" dan "satu pemegang utama" diambil saat event dibuat, sehingga perubahan default di Master Data tidak mengubah event berjalan |
| Kepanitiaan | Seksi dan sub-seksi (manual atau dari template), penugasan jabatan seluruh event atau per seksi, pemegang utama dan pendamping, uraian tugas, mengakhiri jabatan dengan alasan, mencabut keanggotaan |
| Undangan | Orang yang ditugaskan diundang dan baru mendapat akses setelah menerima (halaman Event). Pengelola bisa mengonfirmasi atas nama orang tanpa akses login; konfirmasi tercatat atas nama pengelola |
| Hak akses per event (AC-02) | `EventAccess` menggabungkan semua jabatan aktif seseorang; jabatan seksi hanya berlaku di seksinya dan sub-seksinya. Tidak ada pergantian peran |
| Siapa menetapkan siapa | Ketua dan Pengawas ditetapkan organisasi (`events.manage-committee`); pengelola panitia (Ketua) menetapkan jabatan lain; koordinator seksi menambah anggota di seksinya |
| Pemisahan kewenangan | Pengawas tidak bisa sekaligus menjadi pelaksana, dan sebaliknya. Tidak ada yang bisa mengeluarkan diri sendiri atau mengakhiri jabatannya sendiri |
| Isolasi (AC-01) | Non-anggota mendapat 404 untuk semua route event; seksi, penugasan, dan keanggotaan event lain tidak bisa dicapai lewat URL event sendiri. Test otomatis mencakup setiap route ber-`{event}` |
| Tampilan | Halaman Event (undangan, Event Saya, Semua Event), Ringkasan (kartu, jabatan wajib, profil, tujuan, riwayat), Panitia (bagan organisasi, pengurus inti, seksi), event switcher di header, menu konteks event di sidebar |
| Permission organisasi | `events.view`, `events.create`, `events.update`, `events.manage-committee`, `events.change-status`; dimiliki Super Admin dan Organization Admin |
| Data demo | Dua event: Perayaan Natal 2026 (aktif, panitia lengkap) dan Retret Pemuda 2027 (draf, Bendahara dan Pengawas kosong, undangan `demo.pemuda` menunggu) |

Perubahan lintas modul: semua `ValidationException` dari aksi kini dijawab dalam envelope `{status, data, errors}` untuk AJAX, sehingga pesan aturan domain tampil di dialog.

### Menyimpang dari rencana

| Rencana | Yang dibuat | Alasan |
|---|---|---|
| Tabel `event_required_roles` | `event_role_rules` (kolom `is_required`, `is_singleton`) | Satu tabel untuk kedua aturan; nama lama menyesatkan karena tidak semua barisnya wajib |
| Tabel `invitations` dengan tautan bertoken | Ditunda | Undangan dijawab di aplikasi; orang tanpa login dikonfirmasi pengelola. Tautan undangan lewat email butuh konfigurasi mail produksi |
| Ubah aturan jabatan per event dari layar | Belum ada | Default dari Master Data sudah cukup untuk AC-03; layar pengaturan menyusul bila dibutuhkan |
| Aktivasi oleh Ketua | Hanya organisasi (`events.change-status`) | Otorisasi event adalah keputusan organisasi; Ketua mengatur fase |
| `event_objectives.actual_value` | Belum dibuat | Diisi saat evaluasi (Batch 2.8) |

### Keterbatasan

- Undangan belum memberi notifikasi; penerima melihatnya di halaman Event (notifikasi di Batch 1.8).
- Halaman Panitia memuat ulang setelah setiap perubahan; cukup untuk ukuran panitia biasa.
- Event tidak bisa dihapus, hanya dibatalkan atau diarsipkan.
- Riwayat jabatan yang sudah berakhir tersimpan dan teraudit, tetapi belum ditampilkan sebagai daftar di layar.

---

## 14. Penyesuaian antarmuka (27 Nov 2026)

Diminta pemilik produk saat review Batch 1.2.

| Area | Perubahan |
|---|---|
| Tanggal | Semua input tanggal memakai flatpickr dari bundle Metronic dengan bahasa Indonesia (`public/assets/js/flatpickr-id.js`), termasuk di ponsel. Nilai dikirim `YYYY-MM-DD`, tampil "3 April 2027". Tanggal selesai tidak bisa dipilih sebelum tanggal mulai |
| Dropdown | Semua `<select>` memakai select2 dengan teks bahasa Indonesia. Daftar yang diambil dari server memakai autocomplete dengan pencarian dan paging: orang (penanggung jawab event, tambah panitia) dan unit induk. Daftar orang tidak lagi dimuat penuh ke halaman |
| Form event | Stepper lima langkah: Identitas, Waktu & Tempat, Sasaran, Deskripsi, Tujuan. Field wajib diperiksa sebelum lanjut; error dari server membawa form ke langkah yang bermasalah. Saat mengubah, Simpan tersedia di setiap langkah. Lebar kolom disesuaikan dengan isi |
| Panitia | Susunan panitia berupa tree view yang bisa dibuka/tutup (Pengurus Inti → jabatan → orang; Seksi → sub-seksi → jabatan → orang). Bagan organisasi yang lebih rinci (pengawas, pendamping, anggota seksi, sub-seksi, status undangan) dibuka lewat tombol Lihat Bagan dalam modal layar penuh |
| Sidebar | Menu Ringkasan/Panitia milik event dihapus dari sidebar; halaman event dibuka lewat tab di header dan event switcher |
| Perilaku | Esc saat kalender atau dropdown terbuka hanya menutupnya, tidak menutup modal. Pesan error sebuah field hilang begitu field itu diubah. Pesan aturan domain dari server tampil di dialog (envelope JSON) |

Penjaga otomatis: `FoundationTest` gagal bila ada view yang memakai input tanggal native atau `<select>` tanpa select2.

Uji: `php artisan test --compact` 343 lulus; uji browser alur event 49 pemeriksaan dan stepper 32 pemeriksaan, lulus penuh pada database baru.

---

## 15. Hasil Batch 1.3 — Program kerja, tugas, aktivitas, dan lampiran

### Yang dibuat

| Area | Isi |
|---|---|
| Program kerja | Per seksi: tujuan, keluaran, indikator, penanggung jawab, periode, status (Draf, Berjalan, Selesai, Dibatalkan). Disusun koordinator seksi; program yang sudah punya tugas tidak bisa dihapus |
| Tugas | Untuk seluruh event atau satu seksi, boleh terkait program. Penanggung jawab, anggota tugas, prioritas, bobot 1–10, mulai, tenggat, milestone, perlu review. Subtugas satu tingkat, mengikuti seksi dan program induknya. Bisa disimpan sebagai draf |
| Status tugas | (Disederhanakan, lihat bagian 16.) Draf → Belum Selesai → (Menunggu Review →) Selesai; Batal dan Buka Kembali. Kembalikan, batal, dan buka kembali wajib beralasan. Riwayat di `task_status_changes` dan audit |
| Aturan kerja | Tidak bisa selesai selama prasyarat atau subtugas masih terbuka. Subtugas tidak bisa dibuka kembali di bawah induk yang sudah ditutup. Status hanya berubah lewat `ChangeTaskStatus` (baris dikunci) |
| Review | Tugas "perlu review" masuk Menunggu Review; disetujui atau dikembalikan oleh pengelola tugas yang tidak ikut mengerjakannya |
| Prasyarat | Hanya tugas event yang sama; ditolak bila menunggu dirinya sendiri, induk/subtugasnya, atau membentuk siklus |
| Progres | Bobot tugas daun selesai ÷ bobot tugas daun yang berlaku. Draf dan tugas dibatalkan tidak dihitung; induk diwakili subtugasnya; penyebut nol tampil N/A. Per event, per program, dan per tugas induk |
| Catatan kegiatan | (Diganti hasil tugas, lihat bagian 16.) |
| Lampiran bukti | PDF, gambar, dokumen Office, TXT, CSV maksimal 10 MB, jenis diperiksa dari isi file. Disimpan di disk privat dengan sha256; diunduh hanya lewat route yang memeriksa hak lihat tugas. Menghapus melepas tautan dan mengarsipkan dokumen; file versi tetap tersimpan |
| Hak akses | `TaskAuthority`: lihat (ProgramView/TaskView per seksi, atau ikut mengerjakan), kelola (TaskManage/ProgramManage untuk seksinya), kerjakan (TaskWork pemilik/anggota tugas), review (pengelola yang tidak ikut mengerjakan). Permission organisasi baru `events.manage-work` memberi ProgramManage dan TaskManage di semua event |
| Tampilan | Tab **Program & Tugas** di header event: kartu ringkasan, tabel program dengan progres, tugas dalam tiga tampilan (Daftar dengan filter, Kanban geser-lepas, Kalender FullCalendar). Halaman detail tugas: aksi status, subtugas, prasyarat, catatan kegiatan, lampiran, riwayat status. Kartu Progres Kerja di Ringkasan event |
| Data demo | Natal 2026: dua program, 13 tugas dalam semua status, tiga subtugas, satu prasyarat, dua catatan kegiatan |

Perubahan lintas modul: pesan validasi bahasa Indonesia yang diawali nama field kini diawali huruf kapital (`:Attribute`), misalnya "Nama venue sudah digunakan." Lima test master data disesuaikan dengan teks ini. `OrganizerSelect.setValues` ditambahkan untuk dropdown remote yang bisa memilih banyak.

### Menyimpang dari rencana

| Rencana | Yang dibuat | Alasan |
|---|---|---|
| Tabel `milestones` | Kolom `tasks.is_milestone` | Milestone adalah tugas penting bertanggal; tabel terpisah menggandakan status dan progres |
| Tabel `task_reviews` | `task_status_changes` (aksi approve/return beserta catatan dan pelaku) | Keputusan review adalah perpindahan status; satu riwayat untuk semua perubahan |
| `work_programs.division_id` nullable, `weight` | Wajib seksi; tanpa bobot | Program milik seksi; progres program dihitung dari bobot tugasnya |
| `tasks.due_at`, `milestone_id`, `source_*`, soft delete | `due_on` (tanggal), tanpa kolom sumber dan soft delete | Tenggat harian cukup; sumber tugas (action item, koordinasi) ditambah di batch yang membuatnya; tugas dibatalkan, tidak dihapus |
| `activity_logs.division_id`, `subject_*` | `task_id` | Catatan kegiatan melekat pada tugas |
| `documents.status`, `current_version_id`, `division_id`, `document_tags` | Belum dibuat | Bagian repositori dokumen (Batch 1.7); versi terbaru dibaca dari `version_no` tertinggi |

### Keterbatasan

- Kanban digeser dengan mouse (drag & drop HTML5); di ponsel status diubah dari halaman detail tugas.
- Tugas belum bisa dihapus; batalkan bila tidak dipakai.
- Satu file per unggahan, tanpa versi baru untuk dokumen yang sama (Batch 1.7).
- `sensitivity` dokumen tersimpan tetapi belum membatasi unduhan selain hak lihat tugas.
- Pengingat tenggat belum dikirim (notifikasi di Batch 1.8).

### Database dev

Migration Batch 1.3 sudah berjalan di database dev. Migration `document_links` sempat gagal di MySQL karena nama indeks unik otomatis melebihi 64 karakter; tabel setengah jadi yang masih kosong dihapus, nama indeks dipendekkan (`document_links_unique_link`), lalu migration itu dijalankan ulang.

### Uji

- `php artisan test --compact`: 423 lulus, 12 dilewati (bawaan Jetstream), 1894 assertion. Test baru: `tests/Feature/Work/*` (6 file), `tests/Unit/Services/WorkProgressTest`, `tests/Unit/Enums/TaskActionTest`; `EventIsolationTest` dan `DemoSeederTest` diperluas.
- Uji browser `work-check.js` pada database SQLite baru: 40 pemeriksaan lulus (daftar, filter, form tugas dan validasi, kunci seksi untuk subtugas, kanban dan catatan wajib, kalender, program, persetujuan review, unggah dan unduh bukti, tampilan bendahara di ponsel, tanpa error JS atau server).

---

## 16. Penyederhanaan Program & Tugas (27 Nov 2026)

Diminta pemilik produk saat review Batch 1.3: panitia cukup mencatat pekerjaan yang sudah selesai, dan layarnya harus mudah bagi orang yang tidak terbiasa dengan sistem. Fungsinya dipertahankan.

### Alur tugas

| Sebelum | Sesudah |
|---|---|
| Mulai Kerjakan → Dikerjakan, Tandai Terhambat → Terhambat | Dihapus. Tugas hanya **Belum Selesai** sampai ditandai selesai |
| Selesaikan (tanpa isian) | **Tandai Selesai**: isi *Hasil pekerjaan* (wajib) dan unggah foto/dokumen (boleh beberapa, maksimal 10 file × 10 MB) dalam satu langkah. Bila salah satu bagian gagal, tidak ada yang tersimpan, termasuk file |
| Catatan Kegiatan per tanggal | Diganti bagian **Hasil & Dokumentasi** di detail tugas: hasil bisa diubah, file bisa ditambah, foto tampil sebagai pratinjau |
| Kembalikan / Buka Kembali → Dikerjakan | → Belum Selesai, dengan hasil sebelumnya tetap ada untuk diperbaiki |

Tetap ada: draf, review oleh pengelola lain, batal, buka kembali dengan alasan, prasyarat, subtugas, bobot, dan progres.

### Tampilan

| Area | Perubahan |
|---|---|
| Ringkasan | Lima kartu menjadi satu baris progres dengan empat angka: belum selesai, terlambat, menunggu review, selesai |
| Tugas Saya (tab pertama) | Hanya tugas milik orang yang membuka halaman, berupa kartu dengan tenggat dalam bahasa sehari-hari ("Terlambat 2 hari", "Besok") dan satu tombol **Tandai Selesai**. Terbuka otomatis bila ada tugas yang menunggu orang itu; pilihan tab terakhir diingat |
| Per Program | Setiap program menjadi satu kotak berisi progres dan daftar tugasnya sebagai checklist; lingkaran centang membuka form selesai. **Tambah tugas** di dalam program mengisi seksi dan program sendiri. Tugas di luar program dikumpulkan di kotak terakhir. Penyaring: cari, seksi, sembunyikan yang selesai |
| Papan Status | Empat kolom (Draf, Belum Selesai, Menunggu Review, Selesai); menggeser ke Selesai membuka form selesai |
| Form tugas | Terlihat hanya judul, seksi, penanggung jawab, tenggat. Program, subtugas, anggota, mulai, prioritas, bobot, review, milestone, keterangan, dan draf ada di "Pengaturan lainnya" (terbuka sendiri saat mengubah tugas yang memakainya atau saat ada error di sana) |
| Form program | Terlihat nama, seksi, penanggung jawab, periode; tujuan, keluaran, indikator, status, urutan di "Detail lainnya". Program baru langsung berstatus Berjalan |
| Detail tugas | Panel Subtugas dan Prasyarat disembunyikan dari panitia biasa bila kosong |

### Data

- Kolom baru `tasks.result`. Migration `move_retired_task_statuses_to_todo` memindahkan tugas berstatus `in_progress`/`blocked` ke `todo`; riwayat status lama tidak diubah. Kasus enum `InProgress`, `Blocked`, `Start`, dan `Block` tetap ada hanya agar riwayat lama terbaca.
- Tabel `activity_logs` tidak lagi dipakai aplikasi (model, route, dan layar dihapus) tetapi **belum dihapus** dari database; menunggu keputusan pemilik produk.
- Kedua migration sudah dijalankan di database dev.

### Uji

- `php artisan test --compact`: 426 lulus, 12 dilewati (bawaan Jetstream).
- Uji browser `work-check2.js` pada database SQLite baru: 35 pemeriksaan lulus (tampilan awal per orang, progres, grup program, tambah tugas di dalam program dengan seksi terkunci, validasi, centang → form selesai dengan hasil dan dua foto, program baru, papan empat kolom, kalender, pratinjau foto dan ubah hasil di detail, Tugas Saya di ponsel tanpa scroll horizontal, tanpa error JS atau server).

---

## 17. Tanpa review, tugas terbuka untuk semua panitia, dan komentar (27 Nov 2026)

Diminta pemilik produk: tidak ada lempar-lemparan review dan persetujuan; tugas seksi bisa dilihat semua panitia; aksi tugas hanya **Selesai** dan **Ubah**; ditambah **komentar** dari semua panitia.

### Sebelumnya, siapa yang mereview?

Pengelola tugas (koordinator seksi untuk seksinya; Ketua, Wakil, Sekretaris untuk semua tugas; admin organisasi lewat `events.manage-work`) yang tidak ikut mengerjakan tugas itu. Alur ini sekarang dihapus.

### Yang berubah

| Area | Sekarang |
|---|---|
| Status | Hanya **Belum Selesai** dan **Selesai**. Draf, Menunggu Review, Dibatalkan, Dikerjakan, dan Terhambat tidak bisa dicapai lagi (tetap terbaca di riwayat lama) |
| Aksi | **Tandai Selesai** (hasil wajib + foto/dokumen, langsung Selesai) oleh pemilik, anggota tugas, atau pengelola; **Ubah** data tugas oleh pengelola. Route `tasks/{task}/status` dihapus; tidak ada lagi setujui, kembalikan, batal, buka kembali, atau draf |
| Lihat | Semua panitia aktif melihat semua program dan tugas event, dari seksi mana pun, termasuk unduhan lampiran. Orang yang belum menerima undangan tetap tidak bisa masuk |
| Komentar | Tabel baru `task_comments`. Semua panitia aktif bisa berkomentar di tugas mana pun tanpa persetujuan; komentar dihapus oleh penulisnya atau pengelola tugas. Jumlah komentar tampil di daftar tugas |
| Halaman | Tab Papan Status dihapus (tinggal dua status). Ringkasan: belum selesai, terlambat, selesai. Kartu Ringkasan event menampilkan jumlah selesai |
| Form | Opsi "perlu review" dan "simpan sebagai draf" dihapus dari form tugas |
| Data | Migration `finish_pending_reviews_and_drafts`: tugas Menunggu Review menjadi Selesai, draf menjadi Belum Selesai. Tugas yang sudah dibatalkan tetap tampil sebagai Dibatalkan. Kolom `tasks.requires_review` tidak dipakai lagi (belum dihapus). Sudah dijalankan di database dev |

### Perbaikan pemilih tanggal di modal

Kalender di form tambah/ubah tugas terpotong oleh area form yang bisa di-scroll. Sekarang saat dibuka, kalender tampil di bawah input bila muat, di atas bila di sana muat, dan bila tidak muat keduanya area form diberi ruang sementara (modal memanjang) sampai kalender terlihat utuh; bila melewati tepi kanan, kalender dirapatkan ke kanan. Berlaku untuk semua form dalam modal.

### Belum ditangani

- Tidak ada cara membatalkan "Selesai" yang tertekan tidak sengaja, dan tidak ada hapus/batal tugas, karena aksi dibatasi pada Selesai dan Ubah.
- Komentar belum memberi notifikasi (Batch 1.8).

### Uji

- `php artisan test --compact`: 420 lulus, 12 dilewati (bawaan Jetstream). Test baru `TaskCommentTest`; test status, halaman, lampiran, isolasi, dan data demo disesuaikan dengan aturan baru.
- Uji browser `work-check2.js`: semua pemeriksaan lulus, termasuk kalender tanggal mulai dan tenggat utuh di dalam form, komentar dari anggota seksi lain, dan tidak ada teks review di halaman. `stepper-check.js` dan `events-check.js` (form event) diulang dan tetap lulus.

---

## 18. Halaman detail tugas dengan tab (27 Nov 2026)

Diminta pemilik produk: isi halaman `/events/{id}/tasks/{id}` jangan tampil sekaligus.

| Bagian | Isi |
|---|---|
| Header event | Tidak ditampilkan di halaman tugas; diganti tombol **Kembali ke Program & Tugas**. Peringatan "event tidak dapat diubah" tetap muncul bila event sudah selesai/batal/arsip |
| Kepala (selalu terlihat) | Judul, status, prioritas, milestone/terlambat, tombol **Tandai Selesai** dan **Ubah**, serta seksi, penanggung jawab, dan tenggat dalam satu baris |
| Tab Rincian | Semua data tugas, keterangan, subtugas, prasyarat |
| Tab Hasil & Dokumentasi | Ajakan "Tandai Selesai" bila tugas masih terbuka, hasil, foto, dokumen, unggah. Jumlah file tampil di tab |
| Tab Komentar | Daftar dan form komentar. Jumlah komentar tampil di tab |
| Tab Riwayat | Riwayat status |

Tab pertama: **Rincian** untuk tugas yang belum selesai, **Hasil & Dokumentasi** untuk tugas yang sudah selesai. Tab yang dibuka disimpan di alamat (`#komentar`, `#hasil`, ...), sehingga setelah mengirim komentar atau mengunggah file halaman tetap di tab yang sama. Di ponsel, deretan tab bisa digeser ke samping.

Ditemukan saat uji browser: layout memakai `<base href>`, sehingga alamat `#komentar` saja mengarah ke halaman utama. Skrip memakai alamat lengkap halaman.

Uji: `php artisan test --compact` 421 lulus, 12 dilewati; `work-check2.js` lulus, termasuk tab awal, satu tab terlihat sekaligus, dan tetap di tab Komentar setelah berkomentar.

---

## 19. Istilah awam: besar pekerjaan dan momen penting (27 Nov 2026)

| Sebelum | Sekarang |
|---|---|
| Bobot (angka 1–10) | **Besar pekerjaan**: tiga tombol *Ringan* (sekali kerja beres), *Sedang* (perlu beberapa kali kerja, bawaan), *Berat* (butuh banyak waktu atau persiapan), disimpan sebagai 1/2/3 di `tasks.weight`. Penjelasan di bawahnya: tugas berat dihitung lebih besar dalam progres. Migration `limit_task_weights_to_three_sizes` mengubah bobot lama di atas 3 menjadi 3 (sudah dijalankan di dev) |
| Prioritas | Label "Seberapa mendesak?" |
| Tandai sebagai milestone | **Dihapus** atas keputusan pemilik produk, karena hanya memberi tanda tanpa efek apa pun. Kolom `tasks.is_milestone` tidak dipakai lagi (belum dihapus dari database) |
| "Bobot selesai 4 dari 18" | "x% pekerjaan event selesai (3 dari 12 tugas)" dan "3 dari 12 tugas selesai" di Ringkasan event |

Uji: `php artisan test --compact` 423 lulus, 12 dilewati; `work-check2.js` lulus pada database baru.

---

## 20. Pengurus Inti dalam Program & Tugas (27 Nov 2026)

Pertanyaan pemilik produk: bagaimana dengan pengurus inti (Ketua, Sekretaris, Bendahara) yang punya pekerjaan sendiri?

| Area | Sekarang |
|---|---|
| Program kerja Pengurus Inti | Program boleh tanpa seksi dan tampil sebagai **Pengurus Inti**, paling atas di tab Per Program. Diatur oleh yang memegang TaskManage atau ProgramManage untuk seluruh event (Ketua, Wakil, Sekretaris, admin organisasi). Migration `allow_core_team_work_programs` membuat `work_programs.division_id` boleh kosong (sudah dijalankan di dev) |
| Tugas Pengurus Inti | Tugas tanpa seksi kini berlabel **Pengurus Inti** (sebelumnya "Seluruh event") |
| Tugas milik sendiri | Pengurus inti yang mengerjakan tugas tetapi tidak mengelola tugas (bawaan: Bendahara) bisa menambah dan mengubah tugas Pengurus Inti miliknya sendiri. Form-nya otomatis mengisi penanggung jawab dengan dirinya dan menyembunyikan pilihan seksi. Tidak bisa membuat tugas untuk orang lain atau di seksi |
| Data demo | Program "Koordinasi pengurus inti" (PJ Ketua) dengan tugas undangan (Sekretaris) dan "Susun RAB awal panitia" (Bendahara). Database dev yang sudah berisi data demo tidak ditambahi, karena seeder melewati event yang sudah punya program |

Pekerjaan khas Sekretaris (rapat, notulen) dan Bendahara (RAB, transaksi, pengajuan dana) dikerjakan di modul tersendiri: Batch 1.4 dan Phase 2.

Uji: `php artisan test --compact` lulus penuh; `work-check2.js` lulus, termasuk Bendahara menambah tugas untuk dirinya sendiri.

---

## 21. Hasil Batch 1.4 — Rapat & Notulen

Arahan pemilik produk: hilangkan proses bertahap. Tidak ada undangan atau konfirmasi hadir; rapat yang dibuat otomatis untuk semua panitia. Notulen langsung terbit tanpa review.

### Yang dibuat

| Area | Isi |
|---|---|
| Rapat | Nama, tanggal, jam mulai/selesai (pemilih jam flatpickr 24 jam), tempat, untuk Pengurus Inti atau satu seksi, agenda (satu per baris). Langsung terlihat semua panitia. Dijadwalkan oleh Sekretaris (rapat pengurus inti), koordinator seksi (rapat seksinya), atau admin organisasi |
| Notulen | Satu form: isi notulen, yang hadir (opsional), keputusan (baris bisa ditambah), tindak lanjut (apa, penanggung jawab, tenggat). Sekali simpan langsung bisa dibaca semua panitia. Bisa diperbaiki; setiap simpanan tercatat sebagai versi di tab Riwayat |
| Tindak lanjut → tugas (AC-05) | Setiap tindak lanjut baru otomatis menjadi tugas di Program & Tugas (seksi rapat atau Pengurus Inti) dengan penanggung jawab dan tenggatnya, lalu tertaut dari notulen. Menyimpan form yang sama dua kali, atau dua orang menyimpan bersamaan, ditolak dengan pesan, sehingga tidak pernah ada tugas ganda |
| Komentar | Semua panitia bisa berkomentar di rapat; dihapus oleh penulisnya atau pengelola rapat |
| Tampilan | Tab **Rapat & Notulen** di header event: daftar rapat akan datang (dengan "Hari ini") dan rapat sebelumnya (status notulen). Halaman rapat tanpa header event, dengan tab Agenda, Notulen, Komentar, Riwayat; tab pertama Notulen bila sudah ada, Agenda bila belum. Kartu **Rapat Berikutnya** di Ringkasan event |
| Hapus | Rapat tanpa notulen bisa dihapus; yang sudah bernotulen tetap tersimpan |
| Hak akses | `MeetingAuthority`. Permission `events.manage-work` kini juga memberi MeetingManage |
| Data demo | Rapat pleno 1 (sudah lewat, lengkap dengan notulen, 5 peserta, 2 keputusan, 1 tindak lanjut yang menjadi tugas) dan Rapat koordinasi seksi (akan datang). Sudah ditambahkan ke database dev |

### Menyimpang dari rencana

| Rencana | Yang dibuat | Alasan |
|---|---|---|
| Undangan dan RSVP, `meeting_agendas` | Tidak ada undangan; agenda berupa teks satu baris per butir | Keputusan pemilik produk: rapat otomatis untuk semua panitia |
| Notulen berversi dengan draft → review → publish | Simpan = terbit; versi disimpan untuk riwayat | Keputusan pemilik produk: tanpa proses bertahap |
| `decisions`, `action_items` | `meeting_decisions`, `meeting_action_items` (dengan `task_id`) | Nama mengikuti modul; tautan tugas langsung di baris tindak lanjut |
| Kehadiran terpisah dari notulen | "Yang hadir" diisi di form notulen | Satu langkah |

Tabel baru: `meetings`, `meeting_minutes_versions`, `meeting_attendees`, `meeting_decisions`, `meeting_action_items`, `meeting_comments` (semua rujukan dalam event memakai foreign key komposit). Migration sudah dijalankan di database dev.

### Keterbatasan

- Belum ada pengingat rapat atau pemberitahuan notulen baru (Batch 1.8).
- Rapat belum tampil di kalender Program & Tugas (kalender terpadu di Batch 1.5).
- Tindak lanjut yang sudah menjadi tugas tidak ikut berubah bila notulen diperbaiki; tugasnya diubah dari Program & Tugas.

### Uji

- `php artisan test --compact`: 444 lulus, 12 dilewati. Test baru `tests/Feature/Meetings/MeetingTest` dan `MinutesTest`; `EventIsolationTest` dan `DemoSeederTest` diperluas.
- Uji browser `meetings-check.js` pada database SQLite baru: 25 pemeriksaan lulus (tab di header, rapat berikutnya, daftar, form tanpa undangan, pemilih jam, notulen satu form, tindak lanjut menjadi tugas, riwayat, Bendahara di ponsel membaca dan berkomentar, tanpa error JS atau server).

---

## 22. Notulen berformat, PDF, dan Absensi (27 Nov 2026)

Diminta pemilik produk: isi notulen dengan editor lengkap seperti Google Docs, setiap notulen bisa diunduh PDF untuk dibagikan ke pihak luar, dan tab Absensi dengan klik nama untuk pindah ke kolom Hadir.

### Dependency baru (disetujui pemilik produk)

| Paket | Versi | Untuk |
|---|---|---|
| `dompdf/dompdf` | 3.1.6 | Membuat PDF di server (font DejaVu Sans, sumber eksternal, PHP, dan JavaScript dimatikan) |
| `symfony/html-sanitizer` | 8.1.12 | Membersihkan HTML dari editor dengan daftar izin |

`composer audit` melaporkan advisory pada paket yang sudah ada sebelumnya (antara lain `spomky-labs/pki-framework`, `web-auth/webauthn-lib`, `symfony/http-foundation`, `laravel/framework`, `phpoffice/phpspreadsheet`); tidak ada advisory untuk kedua paket baru. Pembaruan paket lama perlu dibahas terpisah.

### Yang dibuat

| Area | Isi |
|---|---|
| Editor notulen | Form notulen layar penuh: editor dokumen TinyMCE 8 (sudah ada di Metronic) dengan menu Edit/View/Insert/Format/Table dan toolbar gaya paragraf, jenis dan ukuran huruf, tebal/miring/garis bawah/coret, warna teks dan latar, perataan, daftar berpoin/bernomor, indentasi, tabel, tautan, garis, hapus format, cari-ganti, layar penuh, hitung kata. Keputusan dan tindak lanjut di panel kanan. Form tidak tertutup oleh Esc atau klik di luar agar tulisan panjang tidak hilang |
| Keamanan isi | `RichText` menyimpan hanya format dokumen (judul, paragraf, penekanan, daftar, kutipan, tabel, tautan http/https/mailto, gaya warna/perataan/ukuran huruf). Script, atribut event, gambar, embed, `javascript:`, dan `url()` dibuang saat disimpan dan sekali lagi saat ditampilkan atau dicetak. Editor yang hanya berisi paragraf kosong dianggap kosong. Notulen lama (teks biasa) tetap tampil sebagai paragraf |
| PDF | Tombol **Unduh PDF** di halaman rapat (untuk semua yang bisa melihat rapat, setelah notulen ada) menghasilkan file `notulen-<nama-rapat>-<tanggal>.pdf` A4: nama organisasi dan event, info rapat, agenda, yang hadir, isi notulen berformat, keputusan, tabel tindak lanjut beserta status tugasnya, penulis dan versi notulen, nomor halaman |
| Absensi | Tab **Absensi** (dengan hitungan hadir/total di tab): semua panitia aktif dan yang diundang tampil di kolom **Tidak hadir**; pengelola rapat mengklik nama untuk memindahkannya ke kolom **Hadir** (klik lagi untuk batal), langsung tersimpan tanpa memuat ulang halaman. Ada pencarian nama. Panitia lain melihatnya tanpa bisa mengubah. Isian "Yang hadir" di form notulen dihapus |

Uji: `php artisan test --compact` 455 lulus, 12 dilewati (test baru `AttendanceTest`, `MinutesPdfTest`, dan kasus sanitasi di `MinutesTest`); `meetings-check.js` lulus, termasuk toolbar editor lengkap, format tersimpan dan tampil, absensi tersimpan setelah halaman dimuat ulang, dan unduhan PDF.

### Keterbatasan

- Tampilan editor (menu, tooltip) berbahasa Inggris karena paket bahasa Indonesia TinyMCE tidak termasuk di Metronic.
- Gambar tidak bisa disisipkan ke notulen; foto atau dokumen pendukung dilampirkan lewat tugas tindak lanjut.

---

## 23. Form notulen bertab dan absensi terkunci (27 Nov 2026)

| Area | Perubahan |
|---|---|
| Form notulen | Tetap layar penuh, kini tiga tab: **Isi Notulen** (editor selebar layar), **Keputusan**, **Tindak Lanjut**, dengan jumlah baris di tab. Satu tombol simpan; bila ada isian salah di tab lain, form pindah ke tab tersebut |
| Kunci absensi | Absensi hanya bisa diisi pada hari rapat dan terkunci saat rapat selesai: pada jam selesai, atau akhir hari rapat bila jam selesai kosong (zona waktu organisasi). Ditegakkan di server (`Meeting::attendanceState`, `MarkAttendance`); sebelum hari rapat muncul "Absensi dibuka pada hari rapat", setelahnya "Absensi dikunci" tanpa tombol |
| Tampilan absensi | Ringkasan "x dari y panitia hadir" dengan bilah progres, kartu ringkas dengan avatar berwarna, tombol **Hadir** di kolom Tidak hadir dan **Batal** di kolom Hadir, petunjuk di kolom kosong, pencarian nama |
| Ponsel | Badge status rapat ("Hari ini", "Notulen tersedia") dipindah ke baris judul; sebelumnya mendorong halaman 5 px melebar di layar 390 px |

Data demo dan test yang mencatat absensi rapat lampau kini menulisnya langsung, karena absensi rapat lampau terkunci.

Uji: `php artisan test --compact` lulus penuh; `meetings-check.js` 42 pemeriksaan lulus dua kali berturut-turut, termasuk absensi terkunci pada rapat lampau dan tidak ada scroll horizontal di ponsel.

