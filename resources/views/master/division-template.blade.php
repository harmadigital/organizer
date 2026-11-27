@extends('layouts.main_layout', ['title' => 'Template Seksi', 'breadcrum' => ['Master Data', 'Template Seksi']])

@section('content')
    <div class="ui-page">
        <x-ui.page-header title="Master Data" subtitle="Data acuan yang dipakai saat membentuk event dan kepanitiaan." guide="#kt_modal_guide">
            @include('master.partials.subnav')
        </x-ui.page-header>

        <div class="card ui-panel">
            <div class="card-body">
                <x-ui.list-toolbar search-placeholder="Cari template seksi..." add-label="Tambah Template Seksi"
                    :can-create="auth()->user()->can('division-templates.create')" />

                <x-ui.master-table :columns="['Nama Template', 'Deskripsi']" permission="division-templates" />
            </div>
        </div>
    </div>

    <x-ui.form-modal size="modal-lg">
        <div class="fv-row mb-7">
            <label class="required fw-semibold fs-6 mb-2" for="master_name">Nama Template</label>
            <input type="text" name="name" id="master_name" class="form-control form-control-solid" placeholder="Contoh: Seksi Acara" maxlength="150">
        </div>
        <div class="fv-row mb-7">
            <label class="fw-semibold fs-6 mb-2" for="master_description">Deskripsi</label>
            <textarea name="description" id="master_description" class="form-control form-control-solid" rows="3" maxlength="1000" placeholder="Gambaran singkat peran seksi ini"></textarea>
        </div>
        <div class="fv-row mb-7">
            <label class="fw-semibold fs-6 mb-2" for="master_default_responsibilities">Tanggung Jawab Bawaan</label>
            <textarea name="default_responsibilities" id="master_default_responsibilities" class="form-control form-control-solid" rows="6" maxlength="5000" placeholder="Tuliskan tanggung jawab seksi, satu per baris"></textarea>
            <div class="fs-7 text-muted mt-2">Disalin ke seksi saat template dipakai, dan masih dapat diubah di tiap event.</div>
        </div>
        <x-ui.active-switch />
    </x-ui.form-modal>

    <x-ui.guide-modal title="Panduan Template Seksi">
        Template seksi adalah daftar seksi yang biasa dibentuk dalam kepanitiaan. Template mempercepat penyusunan panitia; setiap event tetap bebas menambah, mengubah, atau tidak memakai seksi tertentu.
        <x-slot:steps>
            <li>Klik <strong>Tambah Template Seksi</strong>, lalu isi nama dan tanggung jawab bawaannya.</li>
            <li>Saat menyusun kepanitiaan event, pilih template untuk membuat seksi dengan cepat.</li>
            <li>Sesuaikan nama dan tanggung jawab seksi di event bila perlu.</li>
        </x-slot:steps>
        <x-slot:notes>
            <li>Mengubah template tidak mengubah seksi pada event yang sudah berjalan.</li>
            <li>Template tidak menentukan hak akses. Hak akses mengikuti jabatan panitia.</li>
        </x-slot:notes>
    </x-ui.guide-modal>
@endsection

@section('script')
    <script src="{{ asset('assets/js/organizer/master-crud.js') }}"></script>
    <script src="{{ asset('assets/js/master/division-template.js') }}"></script>
@endsection
