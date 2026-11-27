@extends('layouts.main_layout', ['title' => 'Jabatan Panitia', 'breadcrum' => ['Master Data', 'Jabatan Panitia']])

@section('content')
    <div class="ui-page">
        <x-ui.page-header title="Master Data" subtitle="Data acuan yang dipakai saat membentuk event dan kepanitiaan." guide="#kt_modal_guide">
            @include('master.partials.subnav')
        </x-ui.page-header>

        <div class="card ui-panel">
            <div class="card-body">
                <x-ui.list-toolbar search-placeholder="Cari jabatan panitia..." add-label="Tambah Jabatan"
                    :can-create="auth()->user()->can('committee-roles.create')" />

                <x-ui.master-table :columns="['Jabatan', 'Jenis', 'Lingkup', 'Aturan Bawaan', 'Hak Akses']" permission="committee-roles" />
            </div>
        </div>
    </div>

    <x-ui.form-modal size="modal-lg">
        <div class="row g-7 mb-7">
            <div class="col-md-7 fv-row">
                <label class="required fw-semibold fs-6 mb-2" for="master_name">Nama Jabatan</label>
                <input type="text" name="name" id="master_name" class="form-control form-control-solid" placeholder="Contoh: Asisten Bendahara" maxlength="100">
            </div>
            <div class="col-md-5 fv-row">
                <label class="required fw-semibold fs-6 mb-2" for="master_scope">Lingkup</label>
                <select name="scope" id="master_scope" class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                    data-dropdown-parent="#kt_modal_master">
                    @foreach ($scopes as $scope)
                        <option value="{{ $scope->value }}">{{ $scope->label() }}</option>
                    @endforeach
                </select>
                <div class="fs-7 text-muted mt-2" data-role-scope-hint>Menentukan apakah hak akses berlaku untuk seluruh event atau hanya seksi yang ditugaskan.</div>
            </div>
        </div>

        <div class="fv-row mb-7">
            <label class="fw-semibold fs-6 mb-2" for="master_description">Deskripsi</label>
            <textarea name="description" id="master_description" class="form-control form-control-solid" rows="2" maxlength="1000" placeholder="Tugas pokok jabatan ini"></textarea>
        </div>

        <div class="row g-7 mb-7">
            <div class="col-md-4 fv-row">
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="is_required_default" value="1">
                    <span class="form-check-label fw-semibold text-gray-800">Wajib ada</span>
                </label>
                <div class="fs-7 text-muted mt-2">Event baru tidak dapat diaktifkan sebelum jabatan ini terisi.</div>
            </div>
            <div class="col-md-4 fv-row">
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="is_singleton_default" value="1">
                    <span class="form-check-label fw-semibold text-gray-800">Satu pemegang utama</span>
                </label>
                <div class="fs-7 text-muted mt-2">Hanya satu orang yang menjadi pemegang utama jabatan ini per event atau per seksi.</div>
            </div>
            <div class="col-md-4 fv-row">
                <label class="fw-semibold fs-6 mb-2" for="master_sort_order">Urutan Tampil</label>
                <input type="number" name="sort_order" id="master_sort_order" class="form-control form-control-solid" min="0" max="999" placeholder="0" inputmode="numeric">
            </div>
        </div>

        <div class="separator my-7"></div>

        <div class="fv-row mb-7" data-role-abilities>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
                <label class="fw-semibold fs-6 mb-0">Hak Akses dalam Event</label>
                <span class="badge badge-light-primary" data-role-ability-count>0 dipilih</span>
            </div>
            <div class="fs-7 text-muted mb-5">Berlaku pada event tempat seseorang memegang jabatan ini. Dapat diubah kapan saja.</div>
            <div class="notice d-none bg-light-warning rounded border-warning border border-dashed p-4 mb-5" data-role-supervisor-notice>
                <div class="fw-semibold text-gray-700 fs-7">Jabatan pengawas hanya dapat melihat data dan mencatat keputusan. Hak untuk mengubah pekerjaan panitia tidak dapat diberikan.</div>
            </div>
            @foreach ($abilityGroups as $group => $abilities)
                <div class="border border-gray-300 rounded p-5 mb-4">
                    <div class="ui-info-label mb-4">{{ $group }}</div>
                    <div class="row g-4">
                        @foreach ($abilities as $ability)
                            <div class="col-md-6">
                                <label class="form-check form-check-custom form-check-solid align-items-start">
                                    <input class="form-check-input mt-1" type="checkbox" name="abilities[]" value="{{ $ability->value }}"
                                        data-supervisor-allowed="{{ $ability->isAllowedForSupervisor() ? '1' : '0' }}">
                                    <span class="form-check-label text-gray-700">{{ $ability->label() }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <x-ui.active-switch hint="Jabatan nonaktif tidak dapat dipilih saat menyusun kepanitiaan baru." />
    </x-ui.form-modal>

    <x-ui.guide-modal title="Panduan Jabatan Panitia">
        Jabatan panitia menentukan posisi seseorang dalam kepanitiaan event dan apa saja yang boleh ia lakukan. Jabatan bawaan sudah disediakan dan dapat disesuaikan.
        <x-slot:steps>
            <li>Tinjau jabatan bawaan: Ketua, Wakil Ketua, Sekretaris, Bendahara, Pengawas, Koordinator Seksi, dan Anggota Seksi.</li>
            <li>Ubah nama, aturan bawaan, atau hak akses bila kebiasaan organisasi berbeda.</li>
            <li>Tambahkan jabatan lain bila diperlukan, misalnya asisten bendahara.</li>
        </x-slot:steps>
        <x-slot:notes>
            <li>Jabatan bawaan tidak dapat dihapus, hanya diubah atau dinonaktifkan.</li>
            <li>Jabatan berlingkup seksi hanya berlaku pada seksi tempat orang itu ditugaskan.</li>
            <li>Pengawas tidak dapat diberi hak mengubah pekerjaan panitia.</li>
            <li>Perubahan aturan bawaan berlaku untuk event yang dibuat setelahnya, bukan event yang sudah berjalan.</li>
        </x-slot:notes>
    </x-ui.guide-modal>
@endsection

@section('script')
    <script src="{{ asset('assets/js/organizer/master-crud.js') }}"></script>
    <script src="{{ asset('assets/js/master/committee-role.js') }}"></script>
@endsection
