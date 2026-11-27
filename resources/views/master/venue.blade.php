@extends('layouts.main_layout', ['title' => 'Venue', 'breadcrum' => ['Master Data', 'Venue']])

@section('content')
    <div class="ui-page">
        <x-ui.page-header title="Master Data" subtitle="Data acuan yang dipakai saat membentuk event dan kepanitiaan." guide="#kt_modal_guide">
            @include('master.partials.subnav')
        </x-ui.page-header>

        <div class="card ui-panel">
            <div class="card-body">
                <x-ui.list-toolbar search-placeholder="Cari nama atau alamat venue..." add-label="Tambah Venue"
                    :can-create="auth()->user()->can('venues.create')" />

                <x-ui.master-table :columns="['Nama Venue', 'Alamat', 'Kapasitas']" permission="venues" />
            </div>
        </div>
    </div>

    <x-ui.form-modal>
        <div class="fv-row mb-7">
            <label class="required fw-semibold fs-6 mb-2" for="master_name">Nama Venue</label>
            <input type="text" name="name" id="master_name" class="form-control form-control-solid" placeholder="Contoh: Aula Serbaguna" maxlength="150">
        </div>
        <div class="fv-row mb-7">
            <label class="fw-semibold fs-6 mb-2" for="master_address">Alamat</label>
            <textarea name="address" id="master_address" class="form-control form-control-solid" rows="3" maxlength="1000" placeholder="Alamat lengkap venue"></textarea>
        </div>
        <div class="fv-row mb-7">
            <label class="fw-semibold fs-6 mb-2" for="master_capacity">Kapasitas</label>
            <input type="number" name="capacity" id="master_capacity" class="form-control form-control-solid" placeholder="Jumlah orang" maxlength="7" min="1" inputmode="numeric">
            <div class="fs-7 text-muted mt-2">Perkiraan jumlah orang yang dapat ditampung. Boleh dikosongkan.</div>
        </div>
        <div class="fv-row mb-7">
            <label class="fw-semibold fs-6 mb-2" for="master_notes">Catatan</label>
            <textarea name="notes" id="master_notes" class="form-control form-control-solid" rows="3" maxlength="2000" placeholder="Fasilitas, akses, atau ketentuan pemakaian"></textarea>
        </div>
        <x-ui.active-switch />
    </x-ui.form-modal>

    <x-ui.guide-modal title="Panduan Venue">
        Venue adalah tempat kegiatan yang dapat dipilih saat membuat event, baik milik gereja maupun tempat di luar.
        <x-slot:steps>
            <li>Klik <strong>Tambah Venue</strong>, lalu isi nama dan alamatnya.</li>
            <li>Isi kapasitas bila diketahui, agar target peserta dapat dibandingkan dengan daya tampung.</li>
            <li>Pilih venue ini saat membuat atau mengubah event.</li>
        </x-slot:steps>
        <x-slot:notes>
            <li>Nama venue tidak boleh sama dengan venue lain.</li>
            <li>Venue yang sudah dipakai event tidak dapat dihapus. Nonaktifkan bila tidak digunakan lagi.</li>
        </x-slot:notes>
    </x-ui.guide-modal>
@endsection

@section('script')
    <script src="{{ asset('assets/js/organizer/master-crud.js') }}"></script>
    <script src="{{ asset('assets/js/master/venue.js') }}"></script>
@endsection
