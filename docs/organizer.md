# ORGANIZER — Church Event & Committee Management System

**Document:** Master Product Requirements & Software Development Blueprint  
**Version:** 1.0 — Proposed baseline  
**Target environment:** Existing Laravel boilerplate in VS Code  
**Platforms:** Web Dashboard + Mobile PWA  
**Language:** Indonesian UI; English technical identifiers; configurable organization terminology

> Dokumen ini adalah sumber acuan kebutuhan produk, business logic, integrasi data, dan acceptance criteria. Seluruh keputusan teknis yang belum ditetapkan harus mengikuti hasil pemeriksaan boilerplate dan kebijakan organisasi. Jangan menganggap contoh nominal, tanggal, struktur seksi, atau alur persetujuan sebagai data produksi atau aturan gereja yang telah disahkan.

## 0. Instructions for AI coding agent

1. Baca seluruh dokumen sebelum melakukan implementasi. Periksa repository yang ada: `composer.json`, `package.json`, struktur aplikasi, routes, migrations, authentication, authorization, layout, UI components, testing, konfigurasi database, build pipeline, dan pola coding.
2. Jangan membuat project Laravel baru, mengganti boilerplate, atau mengubah stack tanpa alasan dan persetujuan. Gunakan komponen existing dan versi dependency yang benar-benar terpasang. Jangan membaca atau menampilkan secret `.env` dalam laporan; gunakan konfigurasi yang sudah tersedia secara aman.
3. Pada tahap pertama, hasilkan `docs/IMPLEMENTATION_PLAN.md`, `docs/DATA_MODEL.md`, dan `docs/DEVELOPMENT_TRACKER.md`: analisis existing code, gap, usulan model data, dependencies, urutan implementasi, dan acceptance criteria. Catat asumsi dan keputusan yang memerlukan konfirmasi. Jangan langsung membangun seluruh modul dalam satu iterasi.
4. Gunakan pendekatan event-centric, role-based, domain-oriented dan incremental. Worksheet adalah tampilan serta alur kerja per peran atas data domain bersama, bukan salinan tabel yang berdiri sendiri.
5. Setiap fitur harus memiliki persistence, server-side validation, authorization, audit jika relevan, empty/loading/error states, UI responsif, serta test sesuai risiko. Jangan menyatakan fitur selesai hanya karena halaman atau dummy data sudah tersedia.
6. Hindari hardcode role ID, jenis seksi, nominal approval, organisasi, event, mata uang, atau workflow. Default boleh disediakan sebagai seed/template, bukan aturan universal.
7. Jangan menjalankan migration destruktif, `migrate:fresh`, menghapus data, atau menimpa pekerjaan existing tanpa persetujuan. Perubahan schema harus reversible jika layak dan dites di database terpisah.
8. Pada akhir tiap fase, dokumentasikan perubahan, migration, test yang benar-benar dijalankan, hasil browser testing jika tersedia, blocker dan known issues. Update tracker dan gunakan dokumen ini sebagai sumber requirement.
9. Utamakan business logic dan data integrity, bukan sekadar membuat banyak halaman. Semua placeholder/mock harus ditandai dan tidak dianggap fitur produksi.

# 1. Product vision

Organizer adalah kapal induk data kegiatan gereja, bukan sekadar task manager. Sistem mencakup pembentukan kepanitiaan, perencanaan, administrasi, keuangan, koordinasi, operasional, pengawasan, evaluasi, laporan pertanggungjawaban (LPJ), dan arsip. Informasi yang dimasukkan satu kali digunakan oleh semua pihak sesuai hak akses.

## 1.1 Goals
- Satu sumber data resmi untuk semua kegiatan dan dokumen.
- Mengelola beberapa event aktif secara bersamaan.
- Menghasilkan worksheet khusus setiap jabatan dan seksi.
- Mengurangi input ulang serta ketergantungan pada spreadsheet dan chat sebagai sumber data resmi.
- Menyediakan monitoring progres, kesiapan, keuangan, masalah dan approval.
- Menghasilkan proposal serta LPJ berdasarkan data operasional.
- Menyediakan histori yang dapat digunakan untuk event berikutnya.

## 1.2 Product boundaries
- Fokus awal pada manajemen kepanitiaan, bukan sistem administrasi jemaat atau ERP gereja penuh.
- Modul keuangan event adalah subledger operasional dan pertanggungjawaban kas; general ledger, neraca organisasi, pajak, dan integrasi akuntansi penuh tidak menjadi baseline tanpa requirement terpisah.
- Payment gateway, WhatsApp API, email provider, cloud drive, AI reporting, public registration, dan integrasi eksternal bersifat modular; jangan mengasumsikan credential tersedia.
- Baseline satu organisasi dengan multi-event; tetap gunakan organization scope agar kelak dapat dikembangkan. Multi-tenant SaaS/billing bukan scope awal.

# 2. Platform & UX architecture

## 2.1 Web Dashboard
Pusat administrasi, konfigurasi, perencanaan, pengelolaan worksheet, data tabular, approval, analisis, dan pelaporan. Navigasi memisahkan organization context dari active event context. Semua role memperoleh akses menu sesuai policy.

## 2.2 Mobile PWA
Action-oriented workspace bagi anggota panitia, bukan dashboard desktop yang diperkecil. Home menampilkan event aktif, role, countdown, tugas jatuh tempo, agenda, approval, koordinasi, dan quick actions. Mendukung input cepat, kamera, foto nota, dokumen, check-in jika diaktifkan, live rundown, dan notifikasi.

Gunakan backend, identity, policy, dan data yang sama. PWA boleh menggunakan route/layout khusus atau pendekatan responsive existing setelah audit. Manifest, icons, service worker, HTTPS, installability, cache versioning, dan update strategy harus diuji. Offline baseline hanya draft dan cache data non-sensitif yang diperlukan; tampilkan status sinkronisasi, retry dan konflik. Approval dan posting keuangan wajib online dan divalidasi server. Bersihkan cache privat saat logout; jangan menyimpan respons sensitif secara indiscriminatif. Push notification bersifat progressive enhancement sesuai browser/perangkat.

## 2.3 UI/UX direction
- Professional, clean, warm-neutral, modern, accessible; cocok untuk pengguna gereja dengan tingkat literasi teknologi beragam.
- Gunakan design system/layout boilerplate, konsisten dalam spacing, warna, typography, form, table, badge, filter dan dialog.
- Desktop: sidebar, event switcher, breadcrumb, context title, dashboard cards, drill-down, table dengan filter/pagination.
- Mobile: bottom navigation Home / Worksheet atau Tugas / Agenda / Inbox / Profil; tombol mudah disentuh, form pendek, camera upload, konfirmasi aksi berisiko.
- Bahasa Indonesia yang natural; istilah seperti RAB, LPJ, Pengajuan Dana, Pertanggungjawaban, Rapat, Seksi dan Pengawas konsisten.
- Status tidak hanya dibedakan dengan warna; label jelas, keyboard navigation, focus state, kontras dan error yang dapat ditindaklanjuti.
- Desain untuk kondisi tanpa data, koneksi buruk, upload gagal, akses ditolak, event selesai dan user dengan banyak role.

# 3. Core domain and organization structure

Organization > Events > Committee > Event Memberships / Role Assignments / Divisions > Work Programs > Tasks / Activities.

Role baku dalam event: Ketua (wajib), Wakil Ketua (opsional), Sekretaris (wajib), Bendahara (wajib), Seksi (minimal sesuai kebutuhan yang dikonfirmasi; koordinator dan anggota), Komite/Pengawas (wajib, dapat lebih dari satu). Administrator organisasi dan administrator teknis bukan jabatan panitia. Ketua, sekretaris, dan bendahara utama masing-masing default satu per event; asisten dapat diberikan role tambahan jika kebijakan mengizinkan.

Satu user dapat mengikuti banyak event dan memiliki role berbeda atau beberapa assignment pada event yang sama jika kebijakan mengizinkan. Hak akses efektif ditentukan oleh assignment, event, seksi dan izin spesifik. Pisahkan komite/pengawas dari pelaksana dan cegah self-approval untuk transaksi yang membutuhkan pemisahan kewenangan.

## MOD-01 — Master Data Management
- Organization profile, unit/bidang pelayanan, kontak dan identitas dokumen.
- Member directory: profil minimal, kontak, status, invitation, histori kepanitiaan; akun login dapat dikaitkan setelah undangan diterima. Jangan mewajibkan seluruh jemaat memiliki akun.
- Role & permission master, division templates, event category, financial category, satuan, lokasi/venue, vendor, aset/inventaris.
- Template event, worksheet, checklist, surat, proposal, notulen, LPJ.
- Pencarian, filter, validasi duplikasi, active/inactive, import/export yang diotorisasi. Master yang sudah direferensikan tidak dihapus secara destruktif.

## MOD-02 — Event & Committee Management
- Event: nama, tema/subtema, deskripsi, latar belakang, tujuan, sasaran, indikator keberhasilan, target peserta, tanggal kegiatan (multi-day bila perlu), periode kepanitiaan, tempat, kategori, target anggaran, sumber dana, identitas visual, penanggung jawab organisasi.
- Committee builder: role baku, seksi/subseksi dinamis, koordinator, anggota, job description, undangan/acceptance, histori perubahan, bagan organisasi, surat pembentukan berbasis template.
- Event switcher, daftar event per user, filter tahun/status/kategori, overview, kalender lintas event.
- Clone event/template: salin struktur jabatan tanpa anggota secara default, seksi, program kerja, checklist, kategori dan template anggaran; assignment anggota opsional dengan konfirmasi. Jangan salin transaksi, approval, dokumen pribadi, peserta aktual, realisasi atau LPJ. Tanggal relatif dapat digeser dengan preview.
- Lifecycle: Draft → Initiation/Authorization → Committee Formation → Planning & Budgeting → Preparation → Final Readiness → Execution → Evaluation & Settlement → Reporting & Archiving. Gunakan status sederhana dan phase yang jelas; jangan memaksakan proses linear jika aktivitas paralel diperlukan. Cancellation dan reopen melalui policy serta audit.
- Gate: sebelum aktivasi, role wajib terisi; sebelum pelaksanaan, critical blockers harus ditangani atau dicatat pengecualian resmi; sebelum final close, laporan seksi, rekonsiliasi, uang muka, kewajiban, pengembalian aset, dan pengesahan LPJ memenuhi aturan. Pengecualian hanya sesuai kebijakan terdokumentasi.

# 4. Role-based worksheets

Setiap worksheet terdiri dari summary, data kerja, quick actions, task, agenda, approval jika relevan, dokumen, activity log dan report. Semua data terkait event yang sama. Personal worksheet menampilkan assignment pribadi; role worksheet menampilkan lingkup tugas jabatan; division workspace menampilkan data seksi sesuai permission.

## MOD-03 — Ketua / Event Control Center
- Executive dashboard: progres kerja, readiness, countdown, milestone, status seksi, budget vs actual, komitmen, pendanaan, pengajuan pending, masalah kritis, agenda dan laporan tertunda.
- Event objectives, master planning, target/indikator, review struktur kepanitiaan.
- Review program kerja, delegasi task, monitoring PIC dan deadline, penanganan masalah lintas seksi.
- Persetujuan proposal, RAB, pencairan/revisi/pengadaan sesuai policy; bukan otomatis akses mengedit ledger.
- Decision log, announcement, follow-up, readiness review, evaluasi, final LPJ review.
- Output: executive summary, progress report, readiness report, daftar keputusan dan evaluasi ketua.

## MOD-04 — Wakil Ketua (optional)
- Delegated authority, seksi yang menjadi lingkup supervisi, operational coordination, follow-up, issue resolution, readiness verification dan assignment sesuai delegasi.
- Delegasi eksplisit: pemberi, penerima, permission scope, event, mulai/berakhir, pencabutan dan audit. Tidak boleh melebihi kewenangan pemberi atau melewati aturan pemisahan tugas.
- Jika tidak ada wakil, workflow fallback kepada ketua atau role berwenang yang dikonfigurasi, bukan approval otomatis.

## MOD-05 — Sekretaris / Administration & Information Center
- Data administrasi panitia, job description, agenda, kalender, surat pembentukan, penomoran dan arsip.
- Meeting management: jenis rapat, judul, waktu/tempat, agenda, peserta diundang, RSVP, attendance (hadir/izin/alpa), materi, notulen, keputusan dan action items.
- Action item memiliki PIC dan deadline; dapat dikonversi menjadi task dengan relasi ke rapat/notulen. Cegah duplikasi ketika konversi diulang.
- Notulen: draft, review, publish, revisi versi; distribusi dan histori pembacaan bila diperlukan.
- Correspondence: surat masuk/keluar, jenis, nomor, tanggal, perihal, tujuan, template, attachment, approval, final PDF, pencarian, histori revisi. Penomoran aman terhadap concurrency dan mengikuti konfigurasi organisasi.
- Proposal: tarik tujuan dari event/ketua, struktur dari committee, program kerja dari seksi, jadwal/rundown dari acara, RAB dari bendahara. Sekretaris menyusun narasi dan finalisasi versi.
- LPJ assembly: konsolidasi, cek kelengkapan, editorial narasi, review, versi, lampiran, pengesahan dan arsip.
- Output: laporan administrasi, rekap rapat/kehadiran, notulen, keputusan, surat, proposal dan LPJ.

## MOD-06 — Bendahara / Financial Management Center
- RAB per seksi, konsolidasi event, estimasi pendapatan, kategori, qty × harga satuan, contingency, review, revisi dan approval versioning.
- Penerimaan dana: kas gereja, donasi, persembahan, sponsor, dana usaha, registrasi, lainnya. Pisahkan pledge/komitmen, penerimaan aktual dan bantuan nonkas. Privasi identitas donatur dapat dibatasi.
- Fund request: uang muka, reimbursement, direct vendor payment, kas kecil; pencairan parsial, attachment, verifikasi, approval, bukti pembayaran, settlement, refund/pengembalian sisa.
- Cash & bank accounts, cash book, internal transfer, transaction ledger, payable/vendor obligations, reconciliation, financial closing, pengembalian saldo ke gereja.
- Dashboard: approved budget, commitments, actual expense, available budget, cash outflow, income realized, saldo kas/bank, uang muka outstanding, kewajiban, pendanaan vs kebutuhan.
- Output: RAB, budget variance, income, expense, cash book, settlement uang muka, payable, rekap sponsor/donasi sesuai izin, surplus/defisit, bukti transaksi, laporan penutupan.
- Financial invariants dan workflow rinci ada pada bagian 6. Semua perhitungan otoritatif di server.

## MOD-07 — Dynamic Division Workspace (semua seksi)
Seksi memiliki nama, tipe/template opsional, parent division opsional, koordinator, anggota, deskripsi, tanggung jawab, status dan periode. Jangan hardcode daftar seksi pada authorization.

Fitur standar setiap seksi:
- Division overview: anggota, progres, readiness, anggaran, agenda, approval, kendala.
- Program kerja: tujuan, output, indikator, PIC, timeline, target, budget links, status.
- Task/subtask, milestone, dependency, assignment, activity log, evidence, review.
- Budget planning dan request perubahan, fund request, expense/settlement submission.
- Procurement/resource request, koordinasi lintas seksi, issue/risk, dokumen.
- Checklist kesiapan, evaluasi target vs hasil, kendala/rekomendasi, laporan seksi.
- Koordinator mengelola data seksi; anggota mengelola assignment sendiri dan berkontribusi sesuai policy. Ketua/pengawas melakukan monitoring tanpa otomatis mengubah data yang menjadi tanggung jawab seksi.

### 4.1 Specialized division worksheets (advanced modules)

| Tipe seksi | Data & business features | Output |
|---|---|---|
| Acara | Konsep, rundown, sesi, MC, liturgi, pengisi acara, musik, durasi, latihan/gladi, cue, kebutuhan teknis, aktual sesi | Master rundown, cue sheet, laporan acara |
| Konsumsi | Menu, porsi berdasarkan kategori peserta, kebutuhan khusus dengan akses terbatas, catering, delivery, distribusi, selisih porsi | Food requirement, distribution & actual report |
| Perlengkapan | Kebutuhan aset, borrow/rent/buy, lokasi, qty, penyerahan, kondisi awal/akhir, pengembalian | Equipment list dan asset movement |
| Dekorasi | Konsep, moodboard, layout, material, vendor, instalasi, pembongkaran, evidence | Design reference dan installation checklist |
| Multimedia & Sound | Audio visual, mikrofon, materi playback, lampu, operator, soundcheck, technical rider/cue list | Technical checklist dan operator schedule |
| Dokumentasi & Publikasi | Content calendar, materi artwork, approval, publikasi, shot list, PIC foto/video, galeri | Publication report dan galeri |
| Dana Usaha & Sponsorship | Campaign, target, calon sponsor, proposal, pledge, hasil usaha, kewajiban sponsor | Fundraising report dan rekap sponsor |
| Penerima Tamu & Registrasi | Peserta, undangan, RSVP, kategori, registrasi online (opsional), QR check-in, seating, tamu khusus | Guest list, attendance statistics |
| Transportasi & Akomodasi | Kendaraan, pengemudi, jadwal, penumpang, kapasitas, penginapan, room assignment | Manifest, jadwal, rooming list |
| Keamanan & Kesehatan | Petugas, titik pengamanan, shift, kontak darurat, P3K, prosedur, insiden terbatas | Safety checklist dan incident report |

Relasi utama: peserta → konsumsi/transportasi; rundown → teknis/koordinasi; procurement → komitmen anggaran; sponsorship → finance receipts setelah verifikasi; dokumentasi → repository/LPJ. Data specialized tidak boleh membentuk ledger keuangan paralel.

### 4.2 Custom Worksheet Builder (advanced)
Administrator membentuk template worksheet/form/table dengan field text, number, currency, date/time, select, boolean, attachment, member/master relation, required, validation, permissions, status/checklist template. Definisi schema memiliki version; perubahan template tidak merusak data historis. Custom records tetap event/division scoped. Relasi ke finance, procurement dan approval menggunakan domain service resmi; jangan menggunakan JSON/EAV untuk menggantikan tabel inti keuangan. Modul yang memerlukan constraint kompleks (QR, inventory, room allocation) dibuat sebagai modul khusus.

## MOD-08 — Komite / Pengawas
- Supervisory dashboard: status event, struktur, progres, readiness, RAB/realisasi, approval, risiko, issue, tindak lanjut.
- Review proposal, otorisasi event, budget/revision authorization sesuai kebijakan, review LPJ dan closing approval.
- Findings: kategori, severity, deskripsi, bukti, owner, deadline, rekomendasi, respons, follow-up, verifikasi penyelesaian, reopen.
- Audit trail dan version history sesuai izin; pengawas tidak mengedit langsung transaksi atau worksheet seksi, melainkan memberikan catatan, request revisi, atau keputusan.
- Jika komite beranggotakan beberapa orang, aturan quorum/individual approval perlu dikonfirmasi; desain approval policy tidak mengasumsikan satu orang pasti cukup.

# 5. Shared operational modules

## MOD-09 — Work Program, Tasks & Activity
- Struktur event > division > work program > task > subtask (parent optional). Task juga dapat dibuat langsung pada event atau dari meeting/coordination/issue.
- Field: title, description, owner, collaborators, priority, start/due date, status, estimate/weight, dependency, evidence, review requirement, source relation.
- Status: Draft, To Do, In Progress, Blocked, Pending Review, Completed, Cancelled. Valid transition; completed jika review wajib telah disahkan; reopen dengan alasan.
- List, kanban, calendar, timeline/Gantt, milestone, filters, search, overdue, personal workload. Dependency harus satu event, cegah siklus dan tampilkan blocker.
- Activity log bersifat catatan aktual bertanggal: author, aktivitas, hasil, dokumentasi, related records. Jangan samakan log aktivitas dengan audit log.

## MOD-10 — Cross-Division Coordination
- Request dari seksi asal ke seksi tujuan: subject, detail, item/qty opsional, due date, PIC, priority, terkait task/rundown, attachment.
- Submitted → Acknowledged → In Progress → Fulfilled → Verified/Closed; Rejected, Revision, Cancelled dengan alasan.
- Seksi penerima dapat membuat task/pengadaan/permintaan dana dari request; pertahankan relasi origin. Seksi asal mengonfirmasi pemenuhan jika diperlukan. Ketua memonitor blocked/overdue.
- Contoh: seksi acara meminta 8 mikrofon dari multimedia; multimedia konfirmasi, buat task pemenuhan, update status; acara memverifikasi kesiapan, ketua melihat blocker jika belum tersedia.

## MOD-11 — Procurement & Resource Management
- Need/request, resource availability, opsi borrow/rent/purchase, vendor quotation, comparison, approval, order, receiving, completion/return.
- Purchase commitment dibuat ketika pesanan sah; gunakan budget line yang relevan. Pembayaran dan realisasi berasal dari financial domain, tidak diinput dua kali.
- Penerimaan sebagian, selisih qty/kondisi, retur, bukti penerimaan. Peminjaman aset dengan periode, qty, lokasi dan PIC; cek benturan/ketersediaan, serah terima, pengembalian.
- Master vendor dan asset histori lintas event. Tidak perlu ERP purchasing atau warehouse penuh di baseline.

## MOD-12 — Central Calendar & Schedule
- Kalender personal, seksi, event, organization: meeting, due task, latihan, milestone, vendor, resource reservation, rundown, LPJ.
- Data kalender diturunkan dari sumber asli atau agenda manual; jangan membuat event kalender duplikat untuk record yang sama.
- Day/week/month/list, filter, detail/source link, reminder, timezone organisasi (default Asia/Jakarta), event multi-day, konflik booking resource/venue, recurring meeting jika disetujui.

## MOD-13 — Event Rundown & Live Execution
- Planned rundown version: tanggal, start/end, urutan, sesi, lokasi, PIC, pengisi, cue, kebutuhan, division links.
- Publish master rundown, version changes, actual start/end, status waiting/next/running/completed/skipped, delay reason, instruction, incident.
- Hanya operator berizin dapat mengubah live status. Tampilkan update terbaru, timestamps, re-sync/reconnect; polling atau realtime sesuai existing infrastructure. Jangan menganggap realtime service telah tersedia.
- Alert seksi terkait ketika sesi mendekat; tampilkan planned vs actual dan laporan eksekusi.

## MOD-14 — Approval & Authorization Center
- Satu inbox approval lintas tipe: proposal, work program, budget, revision, fund request, procurement, surat, laporan seksi, LPJ, event closing.
- Workflow template by organization/event, request type, amount threshold, role, sequence, optional steps, delegation, due date, quorum jika dibutuhkan. Snapshot aturan dan approver saat submit; perubahan aturan tidak diam-diam mengubah approval berjalan.
- Draft → Submitted → Pending Approval → Approved/Rejected/Revision Requested/Cancelled. Revision membuat version baru dan approval ulang untuk perubahan material. Catat actor, role, comment, timestamp, decision, source version.
- Cegah self-approval pada financial authorization, duplicate approval, unauthorized/expired delegation, dan mutation setelah approval. Jika approver tidak tersedia, reassign dengan alasan dan hak akses.
- Approval decision dan perubahan domain wajib atomik jika terkait; lock row/version untuk race condition. Reminder dan escalation tidak berarti auto approve.

## MOD-15 — Issue, Risk & Decision Management
- Issue: masalah aktual, severity, status, owner, due, relation, escalation, resolution/evidence.
- Risk: kemungkinan, dampak, rating, mitigation, owner, contingency, review date; dapat dikonversi menjadi issue dengan relasi.
- Decision log: sumber rapat/ketua/pengawas, keputusan, konteks, alasan, actor, tanggal berlaku, affected records, action items; versi bila keputusan berubah.
- Corrective action terhubung dengan temuan pengawas, task, owner, due dan verifikasi.

## MOD-16 — Document & Knowledge Repository
- Struktur virtual event/division/category; dokumen surat, notulen, proposal, kontrak, RAB, invoice, nota, LPJ, media dan lampiran.
- Metadata, version, status draft/final, tags, owner, permissions, source links, search, preview, download, upload progress.
- Satu file dapat direferensikan banyak record; tidak membuat salinan fisik tidak perlu. File private storage, authorized download/signed URL terbatas, validation MIME/size, filename sanitization. Hindari public access pada bukti pembayaran/identitas peserta.
- Retention, archive, backup dan restore; soft delete/replace untuk dokumen biasa sesuai policy, immutable final snapshots untuk dokumen resmi.

## MOD-17 — Communication & Notification
- Announcement umum/role/division, publish schedule, read acknowledgement jika diperlukan.
- Contextual discussion/comment pada task, dokumen, request, issue dan approval; mention anggota yang berhak akses.
- Personal notification inbox: unread/read, type, source, action link, preferences, reminder, daily digest opsional.
- In-app mandatory; web push/email/WhatsApp optional setelah provider tersedia. Gunakan queue, retries, idempotent dispatch, delivery logs dan jangan sertakan data rahasia dalam push preview.
- Jangan membangun chat real-time penuh sebagai prasyarat. Keputusan resmi tetap disimpan sebagai decision/approval, bukan hanya komentar.

# 6. Financial domain — detailed rules

## 6.1 Financial entities and measurements
- Budget version berisi income plan dan expense lines; approved version efektif satu per scope, histori dipertahankan. Perubahan antar-seksi/kategori memerlukan revision sesuai policy.
- Money disimpan sebagai fixed precision decimal atau integer minor units konsisten; hindari floating point untuk kalkulasi. Default IDR; pembulatan eksplisit. Quantity dan unit price divalidasi positif; reversal menggunakan tipe transaksi yang jelas.
- Approved budget = total approved expense lines; actual expense = biaya yang telah diverifikasi dan diakui dalam subledger event; outstanding commitment = bagian kewajiban/pesanan yang belum menjadi actual expense atau dibatalkan; available budget = approved budget − actual expense − outstanding commitment − active reservation lain yang belum overlap. Jangan kurangi komponen yang sama dua kali.
- Fund reservation: pengajuan dana yang telah disetujui tetapi belum menjadi commitment/expense dapat mereservasi budget. Jika linked purchase sudah mereservasi anggaran, jangan reservasi kedua kali. Definisikan lifecycle reserve/release/convert secara atomik.
- Cash balance = opening balance + posted cash inflows − posted cash outflows; internal transfer mengurangi akun asal dan menambah tujuan, net total event nol. Commitments dan accrual-like actual expense bukan otomatis cash outflow.
- Outstanding advance = uang muka yang dicairkan − expense settlement yang diakui terhadap advance − refund yang diterima. Jika biaya melebihi advance, buat reimbursement/shortfall terpisah sesuai approval; jangan menghasilkan outstanding negatif.
- Pendanaan tersedia tidak sama dengan sisa budget. Tampilkan cash sufficiency dan budget sufficiency secara terpisah.
- Pledge sponsor/donatur tidak menambah saldo kas sampai penerimaan terverifikasi. Bantuan nonkas dicatat terpisah; nilai estimasi dan metode penilaian bila diperlukan, bukan cash receipt.

## 6.2 Budget workflow
1. Koordinator menyusun line item (division, program optional, category, description, qty, unit, price, reason).
2. Bendahara review/return dan konsolidasi income plan/expense plan.
3. Ketua menyetujui proposal budget; pengawas menyetujui jika policy mensyaratkan.
4. Versi disahkan dan dikunci; subsequent revision menggunakan diff dan approval baru.
5. Tampilkan budget variance dan over-budget alert; default blok penggunaan melebihi available budget, kecuali authorized revision/exception yang diaudit.

## 6.3 Fund request / disbursement / settlement
1. User berizin mengajukan nominal, jenis, tujuan, budget line, payee, due, dokumen, related order (opsional).
2. Bendahara memverifikasi budget, kas, kelengkapan, duplikasi dan validitas penerima; approval mengikuti konfigurasi nominal.
3. Setelah approved, bendahara melakukan pencairan aktual (bisa parsial) dan posting kas/bank dengan bukti. Idempotency mencegah double disbursement.
4. Untuk advance: pencairan menambah outstanding advance, bukan actual expense. Panitia submit rincian penggunaan, bukti dan pengembalian sisa. Bendahara verifikasi tiap line; posting settlement mengakui expense tanpa mengurangi kas dua kali.
5. Direct vendor payment: catat payable/expense sesuai kebijakan recognition dan pembayaran, linked procurement; jangan duplicate expense saat invoice dan payment keduanya direkam.
6. Reimbursement: panitia melaporkan biaya yang telah dikeluarkan, diverifikasi/diapprove, lalu pembayaran mengurangi kas dan menyelesaikan kewajiban; jangan catat biaya dua kali.
7. Settle/refund, release outstanding reservation jika sudah tidak diperlukan; show remaining amount dan status. Simpan semua history.

Contoh uji: budget konsumsi Rp25 juta, advance Rp10 juta, settlement valid Rp8 juta, refund Rp2 juta. Kas keluar Rp10 juta, kas masuk refund Rp2 juta, net outflow Rp8 juta; actual expense Rp8 juta; outstanding advance Rp0; sisa budget Rp17 juta jika tidak ada commitment/reservation lain. Jangan menjumlahkan advance Rp10 juta dan settlement Rp8 juta sebagai biaya Rp18 juta.

## 6.4 Cashbook, ledger and closing
- Posted transaction immutable; koreksi melalui reversal/adjustment linked original dan permission. Draft dapat diedit. Lampiran, actor, event, account, source reference, transaction date, posting timestamp wajib sesuai tipe.
- Use database transaction, row locks/versioning, unique idempotency/reference constraints pada posting/transfer; no double spending dari concurrent approvals.
- Reconciliation per account: saldo sistem vs aktual kas/rekening, selisih, catatan, evidence, approver dan tanggal. Opening balance/carry-over dikonfirmasi.
- Closing checklist: advances settled, payable resolved atau pengecualian sah terdokumentasi, unposted transactions resolved, reconciliation passed, cash balance handling/refund recorded, final report generated dan approval.
- Laporan surplus/defisit event membedakan penerimaan aktual dan biaya; laporan kas menunjukkan arus kas. Basis pelaporan resmi dan perlakuan donasi nonkas perlu dikonfirmasi dengan gereja.

## 6.5 Approval policy baseline (configurable)
| Request | Proposed route |
|---|---|
| Program kerja | Koordinator → Ketua |
| Budget seksi | Bendahara review → Ketua |
| RAB keseluruhan | Ketua → Pengawas |
| Fund request | Koordinator submits → Bendahara verifies → Ketua/authorized approver per threshold |
| Expense settlement | Bendahara verifies; additional approver if policy requires |
| Budget revision | Bendahara → Ketua → Pengawas if threshold/materiality requires |
| Procurement | Koordinator → Bendahara if cost → Ketua if required |
| Surat keluar | Sekretaris → Ketua |
| Laporan seksi | Koordinator → Sekretaris completeness review / Ketua |
| LPJ final | Sekretaris & Bendahara prepare → Ketua → Pengawas |

# 7. Analytics, reporting and archive

## MOD-18 — Progress & Readiness
- Work progress: default completed eligible task weight / total eligible task weight; cancelled excluded, no double count parent/subtasks; choose leaf tasks or explicitly defined program milestone weights. Empty denominator = N/A, not 100%.
- Readiness: verified checklist weights / applicable checklist weights; priority low/medium/high/critical, verification owner, evidence, due date, status. Critical unmet item flags event at risk regardless of score. Define weights and roll-up configurable; record version if changed.
- Drill-down from event → division → program/task/checklist. Distinguish progress vs readiness. Dashboard flags overdue, blocked, pending approval, unverified evidence and missing reports.

## MOD-19 — Organization Dashboard
- Year/period filter, active events, completed/cancelled, upcoming agenda, total distinct participants/committee members, budget vs actual, donations and funding gaps, unsettled advances, overdue LPJs.
- Compare event categories and historical performance, participation, vendor history, resource conflicts. Cross-event totals only authorized scope; prevent counting a user twice for unique count and avoid treating transfer as income.
- Executive dashboard event specific for Ketua; supervisory dashboard for Pengawas; personal dashboard for each user.

## MOD-20 — Report Engine
Reports with relevant event, division, date, category, state filters; server-side authorization and data aggregation. PDF and Excel/CSV where relevant, print preview, export timestamp, status/version, pagination/large export queue. Draft vs approved numbers labeled clearly. Required outputs:
- Committee structure, member participation, attendance, event overview and readiness.
- Work program, task, activity, milestone, overdue, coordination, issue, decision, risk.
- Meeting minutes, correspondence, administration, proposal.
- Budget plan/revision, income, expense, budget vs actual, commitment, cashbook, bank, advance, reimbursement, payable, reconciliation, surplus/deficit, financial closing.
- Procurement, asset usage/return, vendor, participant/attendance, rundown actual, specialized division reports.
- Supervisory findings and corrective action, evaluation, report per division, final LPJ, annual organization report.

## MOD-21 — Automated Proposal & LPJ Generator
Proposal and LPJ generated from source records, not duplicate manual entry. Narrative fields editable by authorized role, but computed tables remain linked to source and cannot be silently overridden. Document templates configurable.

LPJ default structure:
1. Cover, document number, version, identity and approval page.
2. Pendahuluan/latar belakang; tujuan, sasaran dan target.
3. Susunan panitia (snapshot).
4. Perencanaan dan program kerja.
5. Pelaksanaan kegiatan dan rundown aktual.
6. Laporan setiap seksi: rencana vs hasil, task summary, activity, kendala, realisasi anggaran, evaluasi, rekomendasi.
7. Laporan keuangan: RAB approved vs actual, penerimaan, pengeluaran, kas/bank, advance/payable, saldo akhir.
8. Evaluasi dan hasil pencapaian indikator.
9. Catatan pengawas dan tindak lanjut.
10. Penutup dan pengesahan.
11. Lampiran: notulen, dokumentasi terpilih, bukti transaksi/pendukung sesuai izin.

Workflow: generate draft → completeness/validation → review narrative and data → submit version → approval → freeze source snapshot & rendered PDF → archive. Daftar isi, page numbering dan template header/footer. Missing data ditandai, jangan diisi otomatis dengan angka rekaan. Approved LPJ tidak berubah saat master data/transaksi dikoreksi kemudian; revisi melalui versi baru. Pengesahan internal sistem tidak otomatis merupakan tanda tangan elektronik tersertifikasi.

## MOD-22 — Evaluation & Knowledge Retention
- Evaluasi event/seksi: indikator dan target vs aktual, kendala, feedback, vendor evaluation, lessons learned, recommendations, owner/follow-up.
- Archive read-only setelah closed; reopen terbatas dengan approval/audit atau correction workflow. Search event history dan reuse template. Batasi histori sensitif bagi anggota event baru.

# 8. Authorization and data governance

## MOD-23 — Access control
Gunakan Laravel authorization policy/gate atau mechanism existing yang kompatibel, server-side pada setiap action, query, export, attachment dan API. UI hiding bukan security control. Deny by default. Scope organization/event/division; route model binding perlu nested scoping atau policy yang sesuai. Test akses lintas event termasuk IDOR.

| Role | Default scope and authority |
|---|---|
| Technical admin | Konfigurasi teknis, health, user support terbatas; bukan approver bisnis otomatis |
| Organization admin | Master data, event formation, authorized cross-event overview, role setup; finance detail hanya bila diberi izin |
| Pengawas | Event yang ditugaskan, read/review/approval, temuan; tidak mengedit worksheet operasional |
| Ketua | Whole assigned event, program/work coordination, monitoring, decision/approval sesuai policy |
| Wakil Ketua | Explicit delegated scope |
| Sekretaris | Event administration, meetings, correspondence, proposal/LPJ assembly, relevant report reads |
| Bendahara | Finance event, budget verification, transaction/settlement/reconciliation posting, relevant approvals |
| Koordinator seksi | Own division and permitted shared event data, manage tasks/program, request/submit reports |
| Anggota seksi | Assigned tasks, permitted division data, update/evidence, request if authorized |

Separate permissions: view/create/update/delete-draft/submit/review/approve/reject/post/reverse/export/archive/reopen/assign. Define data access per report, finance document, personal info, donation and incident. Staff with multiple roles cannot use role switching to bypass self-approval. Membership revocation immediately prevents access, including attachment routes. Archive access explicit.

## MOD-24 — Audit, integrity and security
- Audit: actor, effective role, event, action, object type/id, before/after safe diff, timestamp, reason and correlation ID. Sensitive fields redacted; not normal user-editable. Track membership changes, approvals, posted transaction/reversal, configuration, document version, export where appropriate.
- Auth, session/CSRF, XSS protections, validation, throttling, password reset/secure invitation, optional MFA for privileged roles based on existing stack. Protect private storage and avoid leaking secrets/PII via logs.
- Data minimization: collect member/participant contact only as needed. Sensitive child/health data not mandatory; if used, restricted and retention defined. Donor anonymity/privacy configurable. Confirm applicable privacy obligations separately.
- Backup database/files, restore drill, queue/worker scheduler observability, error monitoring, access logs, failed jobs, recovery procedures. Define RPO/RTO, retention and expected usage with stakeholders before production.
- Transactions approved/posted and final snapshots cannot be hard deleted. Use FKs, uniqueness and domain validation; soft delete only where appropriate.

# 9. Logical data model (proposed, not final migration)

Audit boilerplate first; reuse users/roles/permissions if present. Names may adapt to conventions. `organization_id`, `event_id`, `division_id` (where relevant) and scope validation required across business tables. Avoid redundant denormalized IDs unless consistency enforced. Use existing ID convention; consider non-guessable public references for invites/check-in, not as replacement for auth.

| Domain | Candidate tables/entities and key relationships |
|---|---|
| Identity | organizations, users (existing), members (optional separate directory), organization_memberships, roles, permissions, role_permissions |
| Events | events (organization), event_categories, venues, event_memberships (event+user/member), event_role_assignments (membership+role+optional division), divisions (event+optional parent+template), delegations, invitations, event_status_history |
| Planning | event_objectives, work_programs (division/event), tasks (program/event, parent), task_assignees, task_dependencies, task_reviews, activity_logs, milestones |
| Readiness | readiness_checklists, readiness_items, readiness_verifications |
| Secretariat | meetings, meeting_attendees, meeting_agendas, meeting_minutes_versions, decisions, action_items, correspondence, document_number_sequences, proposals |
| Finance | financial_categories, budget_versions, budget_lines, funding_pledges, finance_accounts, fund_requests, fund_request_lines, budget_reservations, commitments, commitment_lines, disbursements, expense_claims, expense_lines, settlements, settlement_lines, payables, finance_transactions/entries, transfers, reconciliations, financial_closings |
| Resources | vendors, assets, resource_requests, resource_request_lines, quotations, procurement_orders, procurement_order_lines, receipts, receipt_lines, asset_reservations, asset_movements |
| Coordination | coordination_requests, coordination_request_items, coordination_fulfillments, issues, risks, supervisory_findings, corrective_actions |
| Schedule | calendar_entries (manual only or source-linked), rundown_versions, rundown_items, rundown_execution_logs |
| Specialized | participants, registrations, attendance/checkins, catering_plans, catering_distributions, transport_assignments, room_assignments, sponsorships, publication_items, operational specialty entities as required by module |
| Workflow | approval_policies, approval_policy_steps, approval_requests, approval_steps/decisions, notifications (existing if any), notification_preferences, announcement_reads, announcements, comments |
| Documents | documents, document_versions, document_links, document_templates, worksheet_templates, worksheet_template_versions, custom_worksheet_records |
| Reporting | division_reports, evaluation_records, report_templates, report_runs, lpj_versions, final_report_snapshots, audit_logs |

Key constraints:
- Unique event membership per event/user, role assignments scoped and non-duplicated; ensure division belongs to event. Enforce mandatory role gate in service.
- Divisions parent same event, no cycles. Task links and source records same event; explicitly authorized org-level references are exceptions (vendor/asset etc.).
- Budget/settlement amount validation, account organization/event ownership, no overlapping active reservations for same commitment, unique posting keys, immutable posted rows.
- Approval target/version referential integrity. If polymorphic linking is used, restrict allowed types and explicitly authorize target. Prefer actual FK for core financial relations.
- Index event/status/due, organization/event date, division/status, assignee, pending approvals, finance posting date and source IDs. Ensure query counts bounded; paginate lists. Timestamp business events and approval decisions; use consistent timezone conversion.

# 10. Technical implementation guidance (Laravel)

- Adapt installed Laravel/PHP/frontend versions; do not assume Laravel 12, Blade, Livewire, Vue, React, Tailwind, MySQL or any package before repo audit. Prefer existing frontend/auth/design system unless approved change.
- Modular monolith recommended initially: HTTP controllers/pages, Form Requests or equivalent validation, Policies, domain Actions/Services for transactions/workflows, Eloquent models/relations, Events/Listeners/Jobs, query/read services for reporting. Keep business logic out of view templates; avoid premature microservices and overly generic repositories.
- Routes grouped by organization and event scope (illustrative `/events/{event}/...`), role-specific menu and PWA routes as required. Shared service layer between web/PWA; JSON endpoints only where needed. CSRF/session for same-origin if applicable; don't introduce token auth without need.
- Database transactions for approvals with domain state changes, allocations, posting, transfer, settlement, event close and snapshot publication. Use locking/optimistic versioning for conflicting writes. Queue side effects after commit. Idempotency for retries, upload sync, notifications and finance actions.
- Form validation: required fields, types, length, date/time consistency, currency precision, event scope, business status and permission checks. User feedback friendly and nontechnical.
- Reporting via centralized query/services and templates; reuse verified calculations for dashboard, report, export. PDF/Excel libraries selected after auditing existing dependencies and compatibility; don't invent installed packages. Test render, page breaks, Indonesian locale, long tables, attachments and access.
- Performance: eager loading, indexes, pagination, bounded export, background jobs for large PDF, cache aggregate only with invalidation and scope keys. Instrument queries/slow routes; define load target after user counts known.
- PWA: secure context, manifest, service worker, offline fallback, safe drafts with temp ID/idempotency and conflict handling. No sensitive finance writes offline. Test mobile viewport, install/update behavior and network reconnection on target browsers.
- Testing: use existing framework, isolated DB/factories/seeds, unit tests for calculations/state transitions, feature tests for validation/auth/workflow, browser E2E if available. Seed sample events/users/roles/transactions in development only, labeled demo. No fake production data.

# 11. Menu map

## Organization level (authorized roles)
Dashboard; All Events; Members; Master Data (Roles, Divisions, Categories, Vendors, Assets, Venues, Templates); Calendar; Analytics; Reports; Event Archive; Settings; Audit (privileged).

## Event level
Overview; Committee; My Workspace / Worksheets (Ketua, Wakil, Sekretaris, Bendahara, Seksi, Pengawas according to access); Work Programs & Tasks; Meetings & Administration; Calendar; Finance; Procurement & Resources; Coordination; Issues & Risks; Approvals; Live Event; Participants (when enabled); Documents; Supervision; Evaluation; Reports & LPJ; Event Settings.

Avoid duplicate UX: menu modules and role worksheets expose the same underlying domain records. Hide inaccessible menu, but enforce policy server-side. Mobile home prioritizes user actions and uses a role-aware worksheet entry. Switching event resets or revalidates all scoped filters and route IDs.

# 12. Development roadmap and definition of done

## Phase 0 — Repository audit & planning
Inspect existing code, identify conventions and risks, map data model, permission matrix, UI pattern and phase plan. Deliver implementation plan, data model, tracker. Confirm uncertain domain rules. No destructive changes.

## Phase 1 — Core committee management
Foundation (identity/master data/organization scope) → event lifecycle & committee builder → roles/policies → role and standard division worksheets → work/task/activity → meeting/notulen/decision → calendar → coordination & issues → document repository → basic notification & PWA. Test cross-event isolation from first iteration.

## Phase 2 — Finance, governance & reporting (initial release target with Phase 1)
Approval engine → budget → funding/transactions → commitments/reservations → disbursement/settlement → reconciliation/closing → basic procurement → supervisory findings → readiness basics → division reports → proposal/LPJ snapshot → archive. Financial invariants and LPJ correctness are release blockers, not enhancements.

## Phase 3 — Advanced operational workflows
Specialized worksheets (prioritize actual event needs) → registration/QR → live rundown → advanced resources and procurement → custom worksheet builder → advanced readiness → enhanced PWA and notifications. Each can be feature-flagged.

## Phase 4 — Organizational intelligence
Cross-event analytics, comparative evaluation, knowledge reuse, optional external integration, AI-assisted narrative drafting only with human validation and no fabricated data.

## Definition of done per feature
- Requirements and acceptance criteria met; UI and backend functional, no unlabeled placeholders.
- Migration/seed/factory where applicable; validation and authorization across web/PWA/API/export.
- Related workflows and reports use consistent source data; money/status integrity preserved.
- Tests executed with actual results; responsive UI and failure cases checked; audit/security addressed.
- Documentation and tracker updated with known limitations, migration/rollback considerations and next dependency.

# 13. Critical end-to-end acceptance scenarios

| ID | Scenario | Expected result |
|---|---|---|
| AC-01 | User in event A tries event B ID through URL, API, file or export | Denied; no data leakage |
| AC-02 | Same user is chair in A and member in B | Separate effective permissions per event; switching context correct |
| AC-03 | Activate event missing required role; create event without deputy | Missing mandatory role blocked; deputy omission accepted with valid fallback |
| AC-04 | Clone prior event | Template copied, no transactions/approvals/actual participants/LPJ carried over |
| AC-05 | Secretariat publishes minutes and creates action item | Task appears in intended worksheet, related to meeting; retry not duplicate |
| AC-06 | Acara requests microphones from Multimedia | Recipient sees request, creates work, fulfillment visible to source and chair |
| AC-07 | Division submits RAB, treasurer reviews, approver approves | Approved version immutable; dashboard and reports match; revision requires approval |
| AC-08 | Two concurrent requests exceed available budget | Atomic reservation prevents overspend and double approval; user receives clear error |
| AC-09 | Advance Rp10 jt, expense Rp8 jt, refund Rp2 jt | Net cash outflow Rp8 jt; actual Rp8 jt; advance zero; no double count |
| AC-10 | Vendor order then invoice then direct payment | Commitment converted/released correctly; one actual expense, cash movement once |
| AC-11 | Internal transfer between event accounts | Source/destination balances change, consolidated income/expense unchanged |
| AC-12 | Unauthorized/self-approval/expired delegation | Rejected server-side and logged as appropriate |
| AC-13 | 95% tasks done but critical venue checklist missing | Readiness flagged critical; task progress remains distinct |
| AC-14 | Mobile user uploads note/photo, loses connection, retries | Draft/sync status clear; authorized submission created at most once |
| AC-15 | Approved LPJ generated then historical master data edited | Archived PDF and snapshot unchanged; revised version explicitly generated |
| AC-16 | Closing with unsettled advance or missing reports | Blocked unless formally permitted exception; reason and actor audited |
| AC-17 | Pengawas records finding | Owner receives follow-up; underlying ledger not directly changed |
| AC-18 | Export restricted donor/incident/private attachment | Only authorized users/fields returned; file access protected |
| AC-19 | Conflict venue/asset booking | Warning or prevention based on configured policy; no silent overbooking |
| AC-20 | Task, meeting, and rundown updates | Calendar and dashboards reflect linked source without duplicate record |
| AC-21 | Posted transaction corrected | Original retained, reversal/adjustment auditable, balances and report reconcile |
| AC-22 | PWA logout/role revoked | Sensitive cache cleared where applicable and server rejects subsequent access |

# 14. Decisions to validate before implementation

- Exact Laravel/PHP/frontend/database versions and existing packages/conventions (repository audit).
- Organization hierarchy, user provisioning, whether assistants/multiple role holders allowed and whether all events require each fixed role; minimum required divisions.
- Approval thresholds, approver chain/quorum, delegation, self-approval prohibition, exception policy, role scope, authority to reopen archived event.
- Church financial reporting basis, account ownership (event vs shared church account), receipt recognition, expense recognition, cash closing, donations and any existing accounting integration. Confirm subledger rules with treasurer.
- Official numbering, proposal/LPJ templates, signatures, archive/retention and privacy policy.
- Prioritized specialized division templates, public registration needs, QR, attendance and offline limitations.
- Expected users/events, media storage, deployment stack, backup/restore, notification providers and target devices/browsers.

# 15. Initial AI agent kickoff prompt

Read `docs/ORGANIZER_MASTER_SPEC.md` fully. Act as Senior Software Architect, Business Analyst and Laravel Full-Stack Engineer. Analyze the existing Laravel boilerplate before changing source code. Treat the master spec as the product baseline and preserve existing conventions. Deliver Phase 0 only: `docs/IMPLEMENTATION_PLAN.md`, `docs/DATA_MODEL.md`, `docs/DEVELOPMENT_TRACKER.md`. Include a module/dependency map, implementation batches, proposed entities and cardinalities, authorization boundaries, existing vs required components, financial invariants, unresolved business decisions, acceptance criteria, and test approach. Do not create a new Laravel project, perform destructive migrations, or implement all modules at once. Report what was inspected and any assumptions or blockers. Await review of Phase 0 before implementation of Phase 1.

---
End of master specification.
