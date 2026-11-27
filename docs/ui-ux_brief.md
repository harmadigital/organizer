# UI/UX Brief — Clean Corporate (Laravel + Metronic)

> Panduan desain untuk mereplikasi tampilan **clean, professional, corporate** dari project ini ke project lain dengan boilerplate sama (Laravel 12 + Livewire + Jetstream + Metronic 8).
>
> **Filosofi inti:** Metronic dipakai sebagai *base component library* (Bootstrap 5), lalu di atasnya ditumpuk satu lapisan sistem desain kustom `ui-*` bergaya **admin-panel bersih (mirip WordPress Admin)** — flat, banyak whitespace, border tipis, tanpa shadow berlebihan, palet netral abu-abu dengan aksen biru. Hasilnya rapi, tenang, dan terlihat mahal.

---

## 1. Tech Stack & Fondasi

| Lapisan | Teknologi |
|---|---|
| Framework | Laravel 12, Livewire 3, Jetstream, Sanctum |
| Template engine | Blade (`@extends` / `@section` / `@yield` / `@stack`) |
| UI theme | **Metronic 8** (Keenthemes) — Bootstrap 5, layout *dark-sidebar* |
| CSS bundle | `public/assets/css/style.bundle.css` + `assets/plugins/global/plugins.bundle.css` |
| Utility CSS | Tailwind v3 (hanya untuk halaman `guest`/auth Jetstream, **bukan** untuk app utama) |
| Ikon | **KTIcons** `ki-duotone` (utama) + **Line Awesome** `las la-*` (aksi tabel) |
| Font | **Inter** (app utama), **Figtree** (halaman guest/Jetstream) |
| JS libs | jQuery, DataTables (server-side), Select2, Flatpickr (locale ID), SweetAlert2 (`Swal`), moment.js |
| Build | Vite (hanya `resources/css/app.css` + `resources/js/app.js`) |

> **Penting:** App utama TIDAK memakai Tailwind untuk styling. Semua styling app memakai Metronic/Bootstrap utility classes + lapisan `ui-*` kustom. Tailwind hanya menempel di layout `guest`.

---

## 2. Sistem Warna

Ada **dua palet yang hidup berdampingan**. Pahami peran masing-masing — ini kunci "clean corporate look".

### 2A. Palet Struktural `ui-*` (netral, mirip WordPress Admin)
Dipakai untuk **kerangka halaman**: header, panel, border, teks, label. Inilah yang memberi kesan bersih & korporat.

| Token | Hex | Pemakaian |
|---|---|---|
| Surface / kartu | `#ffffff` | Background semua panel & card |
| Border utama | `#dcdcde` | Border tipis semua kartu/panel/input container |
| Divider halus | `#f0f0f1` / `#eff2f5` | Garis pemisah header panel, separator |
| Heading | `#1d2327` | Judul halaman, judul panel, nilai penting |
| Teks muted/label | `#646970` | Subtitle, label, deskripsi, teks sekunder |
| Aksen link | `#2271b1` | Border tab aktif |
| Aksen teks aktif | `#135e96` | Teks nav/tab aktif |
| Aksen bg aktif | `#f0f6fc` | Background tab/nav aktif (biru sangat muda) |

### 2B. Palet Metronic (brand / status)
Dipakai untuk **elemen interaktif & status**: tombol, badge, alert, progress. Ini token Bootstrap (`--bs-*`).

| Token | Hex | Hover |
|---|---|---|
| `primary` | `#1B84FF` | `#006AE6` |
| `success` | `#17C653` | |
| `warning` | `#F6C000` | |
| `danger` | `#F8285A` | |
| `info` | `#7239EA` | |

### 2C. Tint ikon statistik (soft, 10% opacity bg)
Khusus untuk `ui-stat-icon` di kartu dashboard — kotak ikon lembut:

```css
.ui-stat-icon--primary { background: rgba(0,158,247,.1);  color:#009EF7; }
.ui-stat-icon--success { background: rgba(80,200,120,.1); color:#50C878; }
.ui-stat-icon--info    { background: rgba(136,108,255,.1);color:#886CFF; }
.ui-stat-icon--danger  { background: rgba(240,61,90,.1);  color:#F13D5A; }
.ui-stat-icon--warning { background: rgba(255,184,34,.1); color:#FFB822; }
```

**Aturan pakai warna:**
- Struktur (border, judul, background, label) → **selalu** palet `ui-*` netral.
- Aksi & status (tombol, badge, alert) → palet Metronic (`btn-primary`, `badge-light-success`, dst).
- Jangan mewarnai panel/kartu dengan warna brand penuh. Kesan bersih datang dari **dominan putih + border abu-abu tipis + aksen warna sangat terbatas**.

---

## 3. Tipografi

- **Font app utama:** `Inter` (weight 300, 400, 500, 600, 700), dimuat via Google Fonts di `<head>`.
- **Font guest/Jetstream:** `Figtree` (via `tailwind.config.js` → `fontFamily.sans`).
- Skala ukuran mengikuti Metronic (`fs-1`…`fs-8`, makin besar angka makin kecil). Nilai kunci `ui-*`:

| Elemen | Size | Weight | Catatan |
|---|---|---|---|
| Judul halaman `.ui-page-title` | `1.35rem` | 700 | `letter-spacing:-0.01em`, warna `#1d2327` |
| Subtitle `.ui-page-subtitle` | `0.925rem` | 400 | warna `#646970` |
| Judul panel `.ui-panel-title` | `1.05rem` | 700 | `#1d2327` |
| Label info `.ui-info-label` | `0.8rem` | 600 | **UPPERCASE**, `letter-spacing:0.04em`, `#646970` |
| Nilai info `.ui-info-value` | `0.975rem` | 600 | `#1d2327` |
| Nilai statistik `.ui-stat-value` | `2rem` | 700 | `line-height:1.1` |
| Label statistik `.ui-stat-label` | `0.875rem` | 600 | `#646970` |

**Konvensi weight Metronic:** `fw-bold` (700) untuk judul, `fw-semibold` (600) untuk body penting, `text-muted`/`text-gray-500` untuk sekunder, `text-gray-400 fw-bold fs-7 text-uppercase` untuk header tabel.

---

## 4. Layout Shell (`layouts/main_layout.blade.php`)

Body di-config lewat atribut `data-kt-app-*`:

```html
<body id="kt_app_body"
      data-kt-app-layout="dark-sidebar"
      data-kt-app-header-fixed="true"
      data-kt-app-sidebar-enabled="true"
      data-kt-app-sidebar-fixed="true"
      data-kt-app-sidebar-hoverable="true"
      data-kt-app-sidebar-push-header="true"
      data-kt-app-sidebar-push-toolbar="true"
      data-kt-app-sidebar-push-footer="true"
      data-kt-app-toolbar-enabled="true"
      class="app-default">
```

**Struktur DOM utama:**
```
page-loader (spinner "Loading...")
app-root #kt_app_root
└─ app-page #kt_app_page
   ├─ app-header #kt_app_header (sticky, minimize saat scroll)
   │   ├─ mobile: sidebar toggle + logo + reminder bubble
   │   └─ app-navbar: subscription badge · shift indicator · user menu
   └─ wrapper
      ├─ app-sidebar #kt_app_sidebar  (dark, lebar 225px, bisa minimize jadi ikon)
      │   ├─ logo + nama tenant (atas)
      │   ├─ menu accordion (scroll-y)
      │   └─ footer: tombol Logout
      └─ app-main #kt_app_main
         ├─ content #kt_app_content → container-fluid → @yield('content')
         └─ app-footer (logo platform + tagline)
```

**Karakter sidebar:**
- Kelas menu: `menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6`, `data-kt-menu="true"`.
- Item: `menu-item` → `menu-link` (+ `active` jika `request()->routeIs(...)`) → `menu-icon` (ki-duotone) + `menu-title`.
- Submenu accordion: `menu-item menu-accordion` + `menu-sub menu-sub-accordion`.
- Setiap item dibungkus `@can('permission.name')` (Spatie Permission).
- Logo dinamis per-tenant (`tenant()->logo`) dengan fallback default.

**Head — urutan wajib:**
```html
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
<link href="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" ... /> {{-- opsional per halaman --}}
<link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" ... /> {{-- opsional --}}
@stack('vendor_css')
<link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" />
@yield('css')
```

**Footer — urutan JS wajib:**
```html
<script>var hostUrl = "{{ asset('assets') }}"; var csrf_token = ...; var base_url = ...;</script>
<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
<script src="{{ asset('assets/js/flatpickr-id.js') }}"></script> {{-- locale ID --}}
@stack('vendor_js')
@yield('script')
```
> Toggle per-halaman via `@section('disable_global_datatables')` / `disable_global_fullcalendar` / `disable_global_flatpickr_locale` untuk skip aset global.

---

## 5. Lapisan Kelas `ui-*` (Copy ke project baru)

Ini **jantung** tampilan clean. Taruh blok `<style>` ini di `<head>` layout (atau file CSS terpisah). Persis seperti di project sumber:

```css
.ui-page            { display:flex; flex-direction:column; gap:1.5rem; }

.ui-page-header     { display:flex; flex-direction:column; gap:1rem;
                      padding:1.25rem 1.5rem; background:#fff;
                      border:1px solid #dcdcde; border-radius:.475rem; }
.ui-page-heading    { display:flex; align-items:flex-start; justify-content:space-between;
                      gap:1rem; flex-wrap:wrap; }
.ui-page-title      { margin:0; color:#1d2327; font-size:1.35rem; font-weight:700;
                      letter-spacing:-.01em; }
.ui-page-subtitle   { margin:.35rem 0 0; color:#646970; font-size:.925rem; }

/* Sub-navigasi tab (kotak, bukan underline) */
.ui-subnav          { display:flex; flex-wrap:wrap; gap:.75rem; }
.ui-subnav .nav-link{ margin:0; padding:.75rem 1rem; background:#fff;
                      border:1px solid #dcdcde; border-radius:.475rem;
                      color:#1d2327; font-size:.95rem; font-weight:600; }
.ui-subnav .nav-link.active { color:#135e96; background:#f0f6fc; border-color:#2271b1; }

/* Panel/kartu — FLAT, tanpa shadow */
.ui-panel           { background:#fff; border:1px solid #dcdcde;
                      border-radius:.475rem; box-shadow:none; }
.ui-panel.card      { box-shadow:none; }
.ui-panel.card > .card-header,
.ui-panel > .ui-panel-header { min-height:auto; padding:1.25rem 1.5rem;
                      background:transparent; border-bottom:1px solid #f0f0f1; }
.ui-panel.card > .card-body,
.ui-panel > .ui-panel-body   { padding:1.5rem; }
.ui-panel.card .card-title   { display:flex; align-items:center; gap:.75rem; }
.ui-panel.card .card-title h3,
.ui-panel-title     { margin:0; color:#1d2327; font-size:1.05rem; font-weight:700; }
.ui-panel-description{ margin-top:.35rem; color:#646970; font-size:.875rem; }

/* Toolbar: filter kiri, aksi kanan */
.ui-toolbar         { display:flex; align-items:flex-start; justify-content:space-between;
                      gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.ui-toolbar-filters { display:flex; align-items:center; gap:.75rem;
                      flex:1 1 480px; flex-wrap:wrap; min-width:0; }
.ui-toolbar-actions { display:flex; align-items:center; gap:.75rem;
                      flex:0 0 auto; margin-left:auto; }

/* Search box dengan ikon di dalam */
.ui-search-box              { position:relative; min-width:min(100%,280px); }
.ui-search-box .ki-duotone  { position:absolute; inset-block-start:50%;
                              transform:translateY(-50%); inset-inline-start:1.1rem; z-index:1; }
.ui-search-box .form-control{ width:min(100%,320px); padding-left:3rem; }

/* Tabel */
.ui-table-shell        { overflow-x:auto; }
.ui-table-shell .table { margin-bottom:0; }

/* Detail: sidebar + konten */
.ui-detail-grid  { display:grid; grid-template-columns:minmax(280px,340px) minmax(0,1fr); gap:1.5rem; }
.ui-detail-stack { display:flex; flex-direction:column; gap:1.5rem; }

/* Daftar info key-value */
.ui-info-list  { display:flex; flex-direction:column; gap:1rem; }
.ui-info-item  { display:flex; flex-direction:column; gap:.25rem; }
.ui-info-label { color:#646970; font-size:.8rem; font-weight:600;
                 text-transform:uppercase; letter-spacing:.04em; }
.ui-info-value { color:#1d2327; font-size:.975rem; font-weight:600; }

/* Kartu statistik */
.ui-stat-card  { height:100%; padding:1.5rem; background:#fff;
                 border:1px solid #dcdcde; border-radius:.475rem; }
.ui-stat-label { color:#646970; font-size:.875rem; font-weight:600; }
.ui-stat-value { color:#1d2327; font-size:2rem; font-weight:700; line-height:1.1; }
.ui-stat-icon  { width:2.5rem; height:2.5rem; border-radius:.5rem;
                 display:flex; align-items:center; justify-content:center; }
.ui-stat-footer{ margin-top:.5rem; font-size:.8rem; color:#646970; line-height:1.4; }

/* Hero banner (mis. sambutan dashboard) */
.ui-hero-card  { padding:2rem; background:#fff; border:1px solid #dcdcde; border-radius:.475rem; }

/* Responsive */
@media (max-width:991.98px){ .ui-detail-grid{ grid-template-columns:1fr; } }
@media (max-width:767.98px){
  .ui-panel.card > .card-header, .ui-panel.card > .card-body, .ui-page-header{ padding:1rem; }
  .ui-toolbar-actions{ width:100%; margin-left:0; }
  .ui-toolbar-actions .btn{ width:100%; }
  .ui-search-box, .ui-search-box .form-control{ width:100%; }
}
```

**Prinsip yang dikodekan di CSS ini:**
- **Radius konsisten `0.475rem`** di semua kartu/panel/tombol/input (radius default Metronic).
- **Flat**: `box-shadow:none` di mana-mana. Kedalaman datang dari border `1px #dcdcde`, bukan bayangan.
- **Gap konsisten `1.5rem`** antar blok vertikal, `0.75rem`–`1rem` antar elemen dalam blok.
- **Padding panel `1.25rem 1.5rem`** (header) dan `1.5rem` (body).

---

## 6. Pola Halaman: LIST / INDEX

Template baku untuk halaman daftar (tabel + toolbar + modal CRUD). Ini pola paling sering dipakai.

```blade
@extends('layouts.main_layout', ['title' => 'Kategori Jasa', 'breadcrum' => ['Master', 'Jasa Tindakan', 'Kategori Jasa']])

@section('content')
<div class="ui-page">

    {{-- HEADER: judul + subtitle + tombol panduan --}}
    <div class="ui-page-header">
        <div class="ui-page-heading d-flex justify-content-between align-items-start w-100">
            <div>
                <h2 class="ui-page-title">Jasa Tindakan</h2>
                <p class="ui-page-subtitle">Kelola kategori dan daftar jasa tindakan klinik.</p>
            </div>
            <button type="button" class="btn btn-icon btn-outline btn-outline-primary"
                    data-bs-toggle="modal" data-bs-target="#kt_modal_guide" title="Panduan">
                <i class="ki-duotone ki-information-4 fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            </button>
        </div>

        {{-- SUB-NAV opsional (tab antar halaman terkait) --}}
        <div class="ui-subnav nav">
            <a class="nav-link active" href="#">Kategori Jasa</a>
            <a class="nav-link" href="#">Daftar Jasa</a>
            <a class="nav-link" href="#">Template</a>
        </div>
    </div>

    {{-- PANEL: toolbar + tabel --}}
    <div class="card ui-panel">
        <div class="card-body">
            <div class="ui-toolbar">
                <div class="ui-toolbar-filters">
                    <div class="ui-search-box">
                        <i class="ki-duotone ki-magnifier fs-3 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                        <input type="text" id="search_category" class="form-control form-control-solid" placeholder="Cari kategori...">
                    </div>
                </div>
                <div class="ui-toolbar-actions">
                    @can('service-category.index')
                    <button type="button" class="btn btn-light-success ...">
                        <i class="ki-duotone ki-file-up fs-2"></i> Import Excel
                    </button>
                    @endcan
                    @can('service-category.create')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add">
                        <i class="ki-duotone ki-plus fs-2"></i> Tambah Kategori
                    </button>
                    @endcan
                </div>
            </div>

            <div class="ui-table-shell">
                <table id="kt_datatable_category" class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                            <th>#</th><th>Nama Kategori</th><th>Status</th>
                            <th class="text-end min-w-100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modals di sini (add / edit / view / guide) --}}
@endsection

@section('script')
    <script src="{{ asset('assets/js/master/service-category.js') }}"></script>
@endsection
```

**Aturan tabel:**
- Class tabel: `table align-middle table-row-dashed fs-6 gy-5` (baris dipisah garis putus-putus, spasi vertikal lega).
- Header: `<tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">`.
- Body: `<tbody class="text-gray-600 fw-semibold">`, diisi via DataTables server-side (kosong di Blade).
- Kolom aksi: `class="text-end min-w-100px"`, tombol ikon Line Awesome kecil `btn p-1` dengan `las la-edit` / `las la-trash`.

---

## 7. Pola Halaman: DETAIL / SHOW

Dua kolom: kartu profil kiri + konten kanan. Pakai `row g-5 g-xl-8` + `col-xl-4`/`col-xl-8` **atau** `.ui-detail-grid`.

```blade
<div class="ui-page">
    <div class="ui-page-header">
        <div class="ui-page-heading">
            <div>
                <h2 class="ui-page-title">Detail Staff</h2>
                <p class="ui-page-subtitle">Informasi profil dan konfigurasi insentif.</p>
            </div>
        </div>
        <div class="ui-toolbar-actions">
            <a href="#" class="btn btn-light"><i class="ki-duotone ki-arrow-left fs-2"></i> Kembali</a>
        </div>
    </div>

    <div class="row g-5 g-xl-8">
        {{-- KIRI: kartu profil --}}
        <div class="col-xl-4">
            <div class="card ui-panel">
                <div class="card-body">
                    <div class="text-center mb-5">
                        <div class="symbol symbol-80px symbol-circle mb-3">
                            <span class="symbol-label bg-light-primary text-primary fs-2 fw-bold">A</span>
                        </div>
                        <h4 class="fw-bold mb-1">Nama Staff</h4>
                        <span class="badge badge-light-success">Aktif</span>
                    </div>
                    {{-- baris key-value --}}
                    <div class="fs-6">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-500">No. Pegawai</span>
                            <span class="fw-semibold">EMP-001</span>
                        </div>
                        {{-- ...ulangi... --}}
                    </div>
                    <div class="separator my-5"></div>
                    <h6 class="fw-bold mb-3">Informasi Bank</h6>
                    {{-- ... --}}
                </div>
            </div>
        </div>

        {{-- KANAN: panel konten (tabel/relasi) --}}
        <div class="col-xl-8">
            <div class="card ui-panel">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Konfigurasi Insentif</h3>
                    <div class="card-toolbar">
                        <button class="btn btn-primary btn-sm"><i class="ki-duotone ki-plus fs-3"></i> Tambah</button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">...</table>
                </div>
            </div>
        </div>
    </div>
</div>
```

**Pola key-value:** `d-flex justify-content-between mb-3` → label `text-gray-500` + value `fw-semibold`. Pemisah antar-seksi: `<div class="separator my-5"></div>` lalu `<h6 class="fw-bold mb-3">`.

---

## 8. Pola Halaman: DASHBOARD

```blade
<div class="ui-page">
    {{-- Hero sambutan --}}
    <div class="ui-hero-card">
        <div class="ui-page-heading">
            <div>
                <h2 class="ui-page-title">Dashboard</h2>
                <p class="ui-page-subtitle">Selamat datang, {{ Auth::user()->name }}. {{ now()->translatedFormat('l, d F Y') }}.</p>
            </div>
        </div>
    </div>

    {{-- Quick Actions: tombol full-width berwarna --}}
    <div class="row g-3 mt-0 mb-5">
        <div class="col">
            <a href="#" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-3">
                <i class="ki-duotone ki-clipboard fs-2"><span class="path1"></span><span class="path2"></span></i>
                <span>Pemeriksaan Baru</span>
            </a>
        </div>
        {{-- btn-success, btn-warning, btn-info, btn-light untuk aksi lain --}}
    </div>

    {{-- Kartu statistik --}}
    <div class="row g-5 mt-0">
        <div class="col-sm-6 col-xl-3">
            <div class="ui-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="ui-stat-label">Kunjungan Hari Ini</span>
                    <div class="ui-stat-icon ui-stat-icon--primary">
                        <i class="ki-duotone ki-clipboard fs-2"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="ui-stat-value">128</div>
                <div class="ui-stat-footer">
                    <span class="text-success">90 selesai</span>
                    <span class="text-muted mx-1">&middot;</span>
                    <span class="text-info">38 aktif</span>
                </div>
            </div>
        </div>
        {{-- ulangi dengan --success / --warning / --danger --}}
    </div>

    {{-- Chart dalam panel --}}
    <div class="row g-5 mt-0">
        <div class="col-xl-6">
            <div class="card ui-panel">
                <div class="card-header align-items-center border-0 pt-5 pb-0">...</div>
                <div class="card-body">{{-- canvas chart --}}</div>
            </div>
        </div>
    </div>
</div>
```

---

## 9. Komponen & Pola Berulang

### 9.1 Modal (CRUD)
```blade
<div class="modal fade" id="kt_modal_add" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">   {{-- +modal-lg / modal-xl bila perlu --}}
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Tambah Kategori</h3>
        <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal">
          <i class="las la-times fs-2"></i>
        </div>
      </div>
      <form id="kt_form_add" class="form" autocomplete="off">
        @csrf
        <div class="modal-body">
          <div class="fv-row mb-7">
            <label class="required fw-semibold fs-6 mb-2">Nama Kategori</label>
            <input type="text" name="name" class="form-control form-control-solid" placeholder="Nama kategori">
          </div>
          {{-- ... --}}
        </div>
        <div class="modal-footer flex-center">
          <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <span class="indicator-label">Simpan</span>
            <span class="indicator-progress">Please wait...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
```
> Layout sudah punya fix CSS agar modal `lg`/`xl` bisa scroll di desktop & tablet (footer sticky, body scroll). Bawa serta blok CSS modal dari `main_layout` bila mereplikasi.

### 9.2 Form fields
- Wrapper tiap field: `<div class="fv-row mb-7">`.
- Label: `<label class="required fw-semibold fs-6 mb-2">` (`required` menampilkan asterisk).
- Input teks: `form-control form-control-solid` (varian *solid* = background abu-abu lembut, andalan Metronic).
- Select: `form-select form-select-solid` + `data-control="select2"` (+ `data-dropdown-parent="#modal_id"` bila di dalam modal, `data-hide-search="true"` bila opsi sedikit).
- Toggle: `form-check form-switch form-check-custom form-check-solid`.
- Input mata uang: `input-group` dengan `<span class="input-group-text">Rp</span>` + `class="... currency-input"`.
- Hint di bawah field: `<span class="fs-7 text-muted">` atau `fs-8`.

### 9.3 Tombol (konvensi)
| Kelas | Pakai untuk |
|---|---|
| `btn btn-primary` | Aksi utama (Simpan, Tambah) |
| `btn btn-light` | Aksi netral (Batal, Kembali) |
| `btn btn-light-success` / `btn-light-primary` | Aksi sekunder bernuansa (Import) |
| `btn btn-outline btn-outline-primary` | Tombol ikon halus (Panduan) |
| `btn btn-icon` | Tombol khusus ikon (tanpa teks) |
| `btn btn-sm` | Di dalam card-toolbar / header |
| Loading state | `data-kt-indicator="on"` + pasangan `.indicator-label` / `.indicator-progress` |
- Selalu ikon `ki-duotone` di kiri + teks, dipisah spasi. Icon size umumnya `fs-2`/`fs-3`.

### 9.4 Badge / status
`badge badge-light-{success|danger|warning|info|primary}` — mis. `badge-light-success` (Aktif), `badge-light-danger` (Nonaktif). Tambah `fs-7` untuk badge kecil. Dot indikator: `bullet bullet-dot bg-success`.

### 9.5 Alert / Notice
```blade
{{-- Notice bergaris putus (info kontekstual) --}}
<div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5 mb-7">
    <i class="ki-duotone ki-information-2 fs-2tx text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
    <div class="fw-semibold text-gray-700 fs-6">Teks penjelasan...</div>
</div>

{{-- Alert standar --}}
<div class="alert alert-warning d-flex align-items-center">...</div>
```

### 9.6 Avatar / Symbol
`symbol symbol-80px symbol-circle` → `symbol-label bg-light-primary text-primary fs-2 fw-bold` (inisial), atau `<img>` di dalam.

### 9.7 Modal Panduan (pola khas project)
Hampir setiap halaman index punya tombol **Panduan** (ikon `ki-information-4`, `btn-outline-primary`) di kanan-atas header yang membuka modal berisi: notice pembuka + `<h5>Alur Kerja</h5>` (`<ol>`) + `<h5>Catatan Penting</h5>` (`<ul>`). Pola bagus untuk onboarding & mengurangi tiket support — pertahankan.

### 9.8 Ikon
- **Utama:** KTIcons `ki-duotone ki-{nama}` — WAJIB sertakan `<span class="path1"></span>...` sesuai jumlah path ikon (duotone butuh child spans).
- **Aksi tabel:** Line Awesome `las la-edit`, `las la-trash`, `las la-times`, `las la-eye`.

---

## 10. Konvensi JavaScript (per halaman)

Satu file JS per halaman, pola IIFE `KTxxx`. Contoh baku (DataTables server-side + AJAX + Swal):

```js
"use strict";
var KTServiceCategory = function () {
    var dt;
    var initDatatable = function () {
        dt = $("#kt_datatable_category").DataTable({
            responsive: true, searchDelay: 500, processing: true, serverSide: true,
            info: false, lengthChange: false, order: [[1, 'asc']],
            language: {
                processing: '<span class="spinner-border spinner-border-sm align-middle me-2"></span>Memuat data...',
                emptyTable: 'Tidak ada data', zeroRecords: 'Tidak ditemukan data yang cocok',
            },
            ajax: { url: base_url + "/master/service-category/data", type: "GET",
                    data: function (d) { d.search = $('#search_category').val(); } },
            columns: [ /* ... */ ],
            columnDefs: [
                { targets: 0, orderable: false, render: (d,t,r,m)=> m.row+1 },
                { targets: -1, orderable: false, className:'text-end', render: function (d,t,row){
                    return '<a href="javascript:void(0)" onclick="openEdit('+row.id+')"><button class="btn p-1"><i class="las la-edit fs-2"></i></button></a>'
                         + '<a href="javascript:void(0)" onclick="doDelete('+row.id+')"><button class="btn p-1"><i class="las la-trash fs-2"></i></button></a>';
                }},
            ]
        });
    };
    return { init: function () { initDatatable(); $('#search_category').on('keyup', delay(()=>dt.ajax.reload(), 500)); } };
}();
jQuery(document).ready(() => KTServiceCategory.init());
```

**Pola submit form (AJAX):**
```js
$('#kt_form_add').on('submit', function (e) {
    e.preventDefault();
    var btn = $(this).find('button[type=submit]');
    btn.attr('data-kt-indicator', 'on').prop('disabled', true);   // spinner
    $.ajax({ url: base_url + '/.../insert', type: 'POST', data: $(this).serialize(),
        success: function (res) {
            btn.removeAttr('data-kt-indicator').prop('disabled', false);
            if (res.status) {
                Swal.fire('Berhasil', 'Data ditambahkan', 'success');
                $('#kt_modal_add').modal('hide'); $(this)[0]?.reset?.();
                $('#kt_datatable_category').DataTable().ajax.reload();
            } else { Swal.fire('Gagal', extractErrorMessage(res.data), 'error'); }
        },
        error: () => { btn.removeAttr('data-kt-indicator').prop('disabled', false); Swal.fire('Error','Server error','error'); }
    });
});
```

**Pola konfirmasi hapus (Swal):**
```js
function doDelete(id) {
    Swal.fire({ title: 'Hapus?', text: 'Data yang dihapus tidak dapat dikembalikan.', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal' })
    .then(r => { if (r.isConfirmed) {
        $.post(base_url + '/.../delete/' + id, { _token: $('meta[name="csrf-token"]').attr('content') }, res => {
            if (res.status) { Swal.fire('Berhasil','Data dihapus','success'); dt.ajax.reload(); }
            else Swal.fire('Gagal','Terjadi kesalahan','error');
        });
    }});
}
```

**Pola edit (get → isi → show):** `$.get('/show/'+id)` → set nilai field → `$('#modal').modal('show')`. Untuk Select2 gunakan `.val(x).trigger('change')`.

**Konvensi respons server:** JSON `{ status: bool, data: ... }`. Feedback selalu via `Swal.fire(judul, pesan, 'success'|'error'|'warning')`.

**Global tersedia di semua halaman:** `base_url`, `csrf_token`, `hostUrl` (di-set di layout), plus `moment`, `Swal`, `$`, DataTables, Select2, Flatpickr.

**Format tanggal:** `moment(date).format('D MMM YYYY')` (JS); `->translatedFormat('l, d F Y')` / `->format('d M Y')` (Blade). Format mata uang Blade: `number_format($n, 0, ',', '.')` dengan prefix `Rp`.

---

## 11. Responsiveness & Breakpoints

- Breakpoint Bootstrap: `sm 576 · md 768 · lg 992 · xl 1200 · xxl 1400`.
- Sidebar jadi **drawer** (`data-kt-drawer`) di bawah `lg`; toggle via `#kt_app_sidebar_mobile_toggle`.
- Grid kartu: `col-sm-6 col-xl-3` (statistik), `col-xl-6` (chart), `col-xl-4`/`col-xl-8` (detail).
- `ui-detail-grid` kolaps jadi 1 kolom < 992px; `ui-toolbar-actions` jadi full-width < 768px.
- Modal punya fix khusus tablet (< 1200px): margin dikecilkan, body scroll, footer sticky — bawa CSS-nya.

---

## 12. Checklist Replikasi ke Project Baru

1. **Salin aset Metronic** ke `public/assets/` (`css/style.bundle.css`, `plugins/global/*`, `plugins/custom/{datatables,fullcalendar}`, `js/scripts.bundle.js`, `plugins.bundle.js`, `media/`).
2. **Salin `layouts/main_layout.blade.php`** — termasuk blok `<style>` `ui-*` dan fix modal, atribut body `data-kt-app-layout="dark-sidebar"`, urutan CSS/JS head-footer.
3. **Salin `layouts/guest.blade.php`** + `tailwind.config.js` (font Figtree) untuk halaman auth Jetstream.
4. **Pasang font Inter** di `<head>` (Google Fonts) untuk app utama.
5. **Konfigurasi sidebar menu** dengan pola `menu-item` + `@can(...)` (Spatie Permission).
6. **Gunakan pola halaman** dari §6 (index), §7 (detail), §8 (dashboard) sebagai kerangka setiap fitur.
7. **Konsisten**: struktur = palet `ui-*` netral + flat + border `#dcdcde`; aksi/status = palet Metronic (`btn-primary`, `badge-light-*`).
8. **JS**: satu modul `KTxxx` per halaman, DataTables server-side, form AJAX dengan indicator + Swal, respons `{status, data}`.

---

## 13. Ringkasan "DNA" Tampilan (yang bikin terlihat clean & corporate)

- **Dominan putih**, dipisah **border abu tipis `#dcdcde`**, hampir **tanpa shadow** (flat).
- **Whitespace lega** — gap `1.5rem` antar blok, padding panel `1.5rem`.
- **Radius seragam `0.475rem`** di semua elemen.
- **Hierarki teks tenang**: judul gelap `#1d2327`, sekunder abu `#646970`, label UPPERCASE kecil.
- **Warna aksen sangat hemat** — biru primary hanya untuk aksi & link; status pakai badge lembut (`-light-`).
- **Pola konsisten**: setiap list = header + toolbar + tabel; setiap form = modal solid-input; setiap aksi = konfirmasi Swal.
- **Sidebar gelap** sebagai satu-satunya area berwarna kuat → kontras yang menegaskan area kerja putih bersih.

> Pegang prinsip: *"Putih bersih + border tipis + banyak ruang + aksen biru sangat terbatas + pola berulang yang konsisten."* Itulah esensi tampilan yang membuat project ini terasa profesional dan korporat.
