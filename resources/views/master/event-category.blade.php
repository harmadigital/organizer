@extends('layouts.main_layout', ['title' => 'Kategori Event', 'breadcrum' => ['Event', 'Kategori Event']])

@section('content')
    <div class="ui-page">
        <x-ui.page-header title="Kategori Event" subtitle="Pengelompokan event sejenis untuk penyaringan dan perbandingan dari tahun ke tahun." guide="#kt_modal_guide" />

        <div class="card ui-panel">
            <div class="card-body">
                <x-ui.list-toolbar search-placeholder="Cari kategori event..." add-label="Tambah Kategori"
                    :can-create="auth()->user()->can('event-categories.create')" />

                <x-ui.master-table :columns="['Nama Kategori', 'Deskripsi']" permission="event-categories" />
            </div>
        </div>
    </div>

    <x-ui.form-modal>
        <div class="fv-row mb-7">
            <label class="required fw-semibold fs-6 mb-2" for="master_name">Nama Kategori</label>
            <input type="text" name="name" id="master_name" class="form-control form-control-solid" placeholder="Contoh: Perayaan Hari Raya" maxlength="150">
        </div>
        <div class="fv-row mb-7">
            <label class="fw-semibold fs-6 mb-2" for="master_description">Deskripsi</label>
            <textarea name="description" id="master_description" class="form-control form-control-solid" rows="3" maxlength="1000" placeholder="Jenis kegiatan yang termasuk kategori ini"></textarea>
        </div>
        <x-ui.active-switch />
    </x-ui.form-modal>

    <x-ui.guide-modal title="Panduan Kategori Event">
        Kategori mengelompokkan event sejenis, misalnya perayaan hari raya, retret, atau bakti sosial, sehingga mudah disaring dan dibandingkan dari tahun ke tahun.
        <x-slot:steps>
            <li>Klik <strong>Tambah Kategori</strong>, lalu isi nama dan deskripsinya.</li>
            <li>Pilih kategori saat membuat event.</li>
        </x-slot:steps>
        <x-slot:notes>
            <li>Nama kategori tidak boleh sama dengan kategori lain.</li>
            <li>Kategori yang sudah dipakai event tidak dapat dihapus. Nonaktifkan bila tidak digunakan lagi.</li>
        </x-slot:notes>
    </x-ui.guide-modal>
@endsection

@section('script')
    <script src="{{ asset('assets/js/organizer/master-crud.js') }}"></script>
    <script src="{{ asset('assets/js/master/event-category.js') }}"></script>
@endsection
