@extends('layouts.main_layout', ['title' => 'Unit Pelayanan', 'breadcrum' => ['Master Data', 'Unit Pelayanan']])

@section('content')
    <div class="ui-page">
        <x-ui.page-header title="Master Data" subtitle="Data acuan yang dipakai saat membentuk event dan kepanitiaan." guide="#kt_modal_guide">
            @include('master.partials.subnav')
        </x-ui.page-header>

        <div class="card ui-panel">
            <div class="card-body">
                <x-ui.list-toolbar search-placeholder="Cari unit pelayanan..." add-label="Tambah Unit"
                    :can-create="auth()->user()->can('organization-units.create')" />

                <x-ui.master-table :columns="['Nama Unit', 'Unit Induk', 'Jumlah User']" permission="organization-units" />
            </div>
        </div>
    </div>

    <x-ui.form-modal>
        <div class="fv-row mb-7">
            <label class="required fw-semibold fs-6 mb-2" for="master_name">Nama Unit</label>
            <input type="text" name="name" id="master_name" class="form-control form-control-solid" placeholder="Contoh: Komisi Pemuda" maxlength="150">
        </div>
        <div class="fv-row mb-7">
            <label class="fw-semibold fs-6 mb-2" for="master_parent_id">Unit Induk</label>
            <select name="parent_id" id="master_parent_id" class="form-select form-select-solid"
                data-select-remote="{{ route('master.organization_unit.options') }}" data-placeholder="Tidak ada (unit tingkat atas)" data-allow-clear="true">
                <option></option>
            </select>
            <div class="fs-7 text-muted mt-2">Kosongkan bila unit ini berdiri langsung di bawah organisasi.</div>
        </div>
        <div class="fv-row mb-7">
            <label class="fw-semibold fs-6 mb-2" for="master_description">Deskripsi</label>
            <textarea name="description" id="master_description" class="form-control form-control-solid" rows="3" maxlength="1000" placeholder="Bidang pelayanan yang ditangani unit ini"></textarea>
        </div>
        <x-ui.active-switch />
    </x-ui.form-modal>

    <x-ui.guide-modal title="Panduan Unit Pelayanan">
        Unit pelayanan adalah komisi, bidang, atau kelompok pelayanan di dalam organisasi. Unit dipakai untuk mengelompokkan user dan menandai unit penanggung jawab sebuah event.
        <x-slot:steps>
            <li>Klik <strong>Tambah Unit</strong>, lalu isi namanya.</li>
            <li>Pilih unit induk bila unit ini berada di bawah unit lain.</li>
            <li>Pilih unit untuk setiap orang dari halaman User.</li>
        </x-slot:steps>
        <x-slot:notes>
            <li>Unit yang masih memiliki user atau unit bawahan tidak dapat dihapus. Nonaktifkan bila tidak digunakan lagi.</li>
            <li>Sebuah unit tidak dapat menjadi bawahan dari unit di bawahnya sendiri.</li>
        </x-slot:notes>
    </x-ui.guide-modal>
@endsection

@section('script')
    <script src="{{ asset('assets/js/organizer/master-crud.js') }}"></script>
    <script src="{{ asset('assets/js/master/organization-unit.js') }}"></script>
@endsection
