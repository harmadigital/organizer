@extends('layouts.main_layout', ['title' => 'Role & Permission', 'breadcrum' => ['User & Akses', 'Role & Permission']])

@section('content')
    <div class="ui-page">
        <x-ui.page-header title="Role & Permission"
            subtitle="Hak akses tingkat organisasi. Hak dalam event diatur terpisah lewat jabatan panitia."
            guide="#kt_modal_guide">
            <div class="ui-subnav nav">
                @can('users.view')
                    <a class="nav-link" href="{{ route('master.user.index') }}">User</a>
                @endcan
                <a class="nav-link active" href="{{ route('master.role.index') }}">Role & Permission</a>
            </div>
        </x-ui.page-header>

        <div class="row g-5">
            @foreach ([
                ['total', 'Total Role', 'Role yang tersedia untuk diberikan', 'primary', 'ki-security-user', 2],
                ['permissions', 'Total Permission', 'Aksi yang dapat diatur aksesnya', 'success', 'ki-key-square', 2],
                ['used', 'Role Digunakan', 'Dipegang oleh minimal satu user', 'info', 'ki-people', 5],
            ] as [$key, $label, $footer, $tone, $icon, $paths])
                <div class="col-sm-6 col-xl-4">
                    <div class="ui-stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="ui-stat-label">{{ $label }}</span>
                            <div class="ui-stat-icon ui-stat-icon--{{ $tone }}">
                                <i class="ki-duotone {{ $icon }} fs-2">
                                    @for ($path = 1; $path <= $paths; $path++)
                                        <span class="path{{ $path }}"></span>
                                    @endfor
                                </i>
                            </div>
                        </div>
                        <div class="ui-stat-value" data-role-stat="{{ $key }}">{{ number_format($roleSummary[$key], 0, ',', '.') }}</div>
                        <div class="ui-stat-footer">{{ $footer }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card ui-panel">
            <div class="card-body">
                <x-ui.list-toolbar search-placeholder="Cari nama role..." add-label="Tambah Role"
                    :can-create="auth()->user()->can('roles.create')" :status-filter="false" />

                <x-ui.master-table :columns="['Nama Role', 'Permission', 'Dipakai User']" permission="roles" :status-column="false" />
            </div>
        </div>
    </div>

    <x-ui.form-modal size="modal-xl">
        <div class="notice d-none bg-light-warning rounded border-warning border border-dashed p-4 mb-7" data-role-notice>
            <div class="fw-semibold text-gray-700 fs-7" data-role-notice-text></div>
        </div>

        <fieldset data-role-fieldset>
            <div class="fv-row mb-7">
                <label class="required fw-semibold fs-6 mb-2" for="master_name">Nama Role</label>
                <input type="text" name="name" id="master_name" class="form-control form-control-solid" placeholder="Contoh: Admin Sekretariat" maxlength="100">
                <div class="fs-7 text-muted mt-2 d-none" data-role-name-hint>Nama role bawaan sistem tidak dapat diubah.</div>
            </div>

            <div class="fv-row mb-2">
                <label class="required fw-semibold fs-6 mb-2">Permission</label>
                @include('master.partials.permission-picker')
            </div>
        </fieldset>
    </x-ui.form-modal>

    <x-ui.guide-modal title="Panduan Role & Permission">
        Role mengelompokkan permission tingkat organisasi, misalnya siapa yang boleh mengelola master data atau user. Hak seseorang di dalam sebuah event diatur lewat jabatan panitia, bukan di sini.
        <x-slot:steps>
            <li>Klik <strong>Tambah Role</strong>, beri nama, lalu pilih permission yang dibutuhkan.</li>
            <li>Berikan role kepada seseorang dari halaman <strong>User</strong>, pada bagian Akses Login.</li>
        </x-slot:steps>
        <x-slot:notes>
            <li>Anda hanya dapat memberikan permission yang Anda miliki sendiri.</li>
            <li>Role <strong>Super Admin</strong> selalu memiliki semua permission dan tidak dapat diubah.</li>
            <li>Nama role bawaan sistem tidak dapat diubah, dan role bawaan tidak dapat dihapus.</li>
            <li>Role yang masih dipegang user tidak dapat dihapus. Ganti role mereka terlebih dahulu.</li>
        </x-slot:notes>
    </x-ui.guide-modal>
@endsection

@section('script')
    <script src="{{ asset('assets/js/organizer/master-crud.js') }}"></script>
    <script src="{{ asset('assets/js/master/role.js') }}"></script>
@endsection
