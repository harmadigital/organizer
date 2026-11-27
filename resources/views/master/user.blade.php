@extends('layouts.main_layout', ['title' => 'User', 'breadcrum' => ['User & Akses', 'User']])

@section('content')
    <div class="ui-page">
        <x-ui.page-header title="User"
            subtitle="Daftar orang dalam organisasi: panitia, pengurus, dan administrator. Akses login bersifat opsional."
            guide="#kt_modal_guide">
            <div class="ui-subnav nav">
                <a class="nav-link active" href="{{ route('master.user.index') }}">User</a>
                @can('roles.view')
                    <a class="nav-link" href="{{ route('master.role.index') }}">Role & Permission</a>
                @endcan
            </div>
        </x-ui.page-header>

        <div class="row g-5">
            @foreach ([
                ['total', 'Total User', 'Seluruh orang dalam daftar', 'primary', 'ki-people', 5],
                ['active', 'User Aktif', 'Dapat ditugaskan dalam kepanitiaan', 'success', 'ki-check-circle', 2],
                ['with_login', 'Memiliki Akses Login', 'Dapat masuk ke aplikasi', 'info', 'ki-key', 2],
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
                        <div class="ui-stat-value" data-user-stat="{{ $key }}">{{ number_format($summary[$key], 0, ',', '.') }}</div>
                        <div class="ui-stat-footer">{{ $footer }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card ui-panel">
            <div class="card-body">
                <x-ui.list-toolbar search-placeholder="Cari nama, username, email, atau telepon..." add-label="Tambah User"
                    :can-create="auth()->user()->can('users.create')" :status-filter="false">
                    <x-slot:filters>
                        <div class="w-100 w-md-175px">
                            <select class="form-select form-select-solid" data-control="select2" data-placeholder="Semua Unit"
                                data-allow-clear="true" aria-label="Saring menurut unit pelayanan" data-master-filter="organization_unit_id">
                                <option></option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-100 w-md-175px">
                            <select class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                                data-placeholder="Semua Role" data-allow-clear="true" aria-label="Saring menurut role" data-master-filter="role">
                                <option></option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}">{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-100 w-md-150px">
                            <select class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                                data-placeholder="Semua Status" data-allow-clear="true" aria-label="Saring menurut status" data-master-filter="status">
                                <option></option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-100 w-md-200px">
                            <select class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                                data-placeholder="Semua Akses" data-allow-clear="true" aria-label="Saring menurut akses login" data-master-filter="access">
                                <option></option>
                                <option value="with">Memiliki akses login</option>
                                <option value="without">Tanpa akses login</option>
                            </select>
                        </div>
                    </x-slot:filters>
                </x-ui.list-toolbar>

                <x-ui.master-table :columns="['Nama', 'Kontak', 'Unit Pelayanan', 'Akses Login', 'Role']" permission="users" />
            </div>
        </div>
    </div>

    <x-ui.form-modal size="modal-lg">
        <div class="fv-row mb-7">
            <label class="required fw-semibold fs-6 mb-2" for="master_name">Nama Lengkap</label>
            <input type="text" name="name" id="master_name" class="form-control form-control-solid" placeholder="Nama lengkap" maxlength="150">
        </div>

        <div class="row g-7 mb-7">
            <div class="col-md-6 fv-row">
                <label class="fw-semibold fs-6 mb-2" for="master_email">Email</label>
                <input type="email" name="email" id="master_email" class="form-control form-control-solid" placeholder="nama@contoh.com" maxlength="255">
            </div>
            <div class="col-md-6 fv-row">
                <label class="fw-semibold fs-6 mb-2" for="master_phone">Nomor Telepon</label>
                <input type="tel" name="phone" id="master_phone" class="form-control form-control-solid" placeholder="081234567890" maxlength="30" inputmode="tel">
            </div>
        </div>

        <div class="row g-7 mb-7">
            <div class="col-md-6 fv-row">
                <label class="fw-semibold fs-6 mb-2" for="master_organization_unit_id">Unit Pelayanan</label>
                <select name="organization_unit_id" id="master_organization_unit_id" class="form-select form-select-solid" data-control="select2"
                    data-dropdown-parent="#kt_modal_master" data-placeholder="Pilih unit" data-allow-clear="true">
                    <option></option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}{{ $unit->is_active ? '' : ' (nonaktif)' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 fv-row">
                <label class="required fw-semibold fs-6 mb-2" for="master_status">Status</label>
                <select name="status" id="master_status" class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                    data-dropdown-parent="#kt_modal_master">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
                <div class="fs-7 text-muted mt-2">User nonaktif tidak dapat masuk dan tidak ditawarkan untuk penugasan baru.</div>
            </div>
        </div>

        <div class="fv-row mb-7">
            <label class="fw-semibold fs-6 mb-2" for="master_notes">Catatan</label>
            <textarea name="notes" id="master_notes" class="form-control form-control-solid" rows="2" maxlength="2000" placeholder="Keterangan tambahan bila diperlukan"></textarea>
        </div>

        @can('users.manage-access')
            <div class="separator my-7"></div>

            <div data-user-access>
                <h4 class="fw-bold fs-5 mb-1">Akses Login</h4>
                <div class="fs-7 text-muted mb-5">Aktifkan hanya untuk orang yang perlu masuk ke aplikasi.</div>

                <div class="notice d-none bg-light-warning rounded border-warning border border-dashed p-4 mb-5" data-user-access-notice>
                    <div class="fw-semibold text-gray-700 fs-7" data-user-access-notice-text></div>
                </div>

                <fieldset data-user-access-fieldset>
                    <input type="hidden" name="login_enabled" value="0">
                    <div class="fv-row mb-7">
                        <label class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="login_enabled" value="1" data-user-login-switch>
                            <span class="form-check-label fw-semibold text-gray-800">Dapat masuk ke aplikasi</span>
                        </label>
                    </div>

                    <div class="d-none" data-user-access-fields>
                        <div class="row g-7 mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fw-semibold fs-6 mb-2" for="master_username">Nama Pengguna</label>
                                <input type="text" name="username" id="master_username" class="form-control form-control-solid" placeholder="contoh: debora.s"
                                    maxlength="50" autocomplete="off" autocapitalize="none">
                                <div class="fs-7 text-muted mt-2">Dipakai untuk masuk. Huruf kecil, angka, titik, garis bawah, atau tanda hubung.</div>
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="master_role">Role</label>
                                <select name="role" id="master_role" class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                                    data-dropdown-parent="#kt_modal_master" data-placeholder="Tanpa role organisasi" data-allow-clear="true">
                                    <option></option>
                                    @foreach ($grantableRoles as $role)
                                        <option value="{{ $role }}">{{ $role }}</option>
                                    @endforeach
                                </select>
                                <div class="fs-7 text-muted mt-2">Hak akses tingkat organisasi. Hak dalam event diatur lewat jabatan panitia.</div>
                            </div>
                        </div>
                        <div class="row g-7 mb-2">
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="master_password" data-user-password-label>Kata Sandi</label>
                                <input type="password" name="password" id="master_password" class="form-control form-control-solid" autocomplete="new-password">
                                <div class="fs-7 text-muted mt-2" data-user-password-hint></div>
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="master_password_confirmation">Ulangi Kata Sandi</label>
                                <input type="password" name="password_confirmation" id="master_password_confirmation" class="form-control form-control-solid" autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                </fieldset>

                @can('users.reset-two-factor')
                    <div class="d-none align-items-center justify-content-between gap-3 border border-gray-300 rounded p-4 mt-5" data-user-two-factor>
                        <div class="fs-7 text-gray-700">Autentikasi dua langkah aktif. Reset bila user kehilangan perangkat autentikatornya.</div>
                        <button type="button" class="btn btn-sm btn-light-danger flex-shrink-0" data-user-reset-two-factor>Reset 2FA</button>
                    </div>
                @endcan
            </div>
        @endcan
    </x-ui.form-modal>

    <x-ui.guide-modal title="Panduan User">
        Halaman ini berisi semua orang dalam organisasi, baik yang dapat masuk ke aplikasi maupun yang hanya dicatat untuk ditugaskan sebagai panitia.
        <x-slot:steps>
            <li>Klik <strong>Tambah User</strong>, lalu isi nama dan kontaknya.</li>
            <li>Pilih unit pelayanan bila orang tersebut tergabung dalam komisi atau bidang tertentu.</li>
            <li>Aktifkan <strong>Akses Login</strong> bila orang tersebut perlu masuk ke aplikasi, lalu isi nama pengguna, kata sandi, dan role bila perlu.</li>
        </x-slot:steps>
        <x-slot:notes>
            <li>Email, nomor telepon, dan nama pengguna tidak boleh sama dengan user lain.</li>
            <li>Mencabut akses login atau menonaktifkan user langsung mengeluarkannya dari aplikasi.</li>
            <li>Anda hanya dapat memberikan role, atau mengatur akses user, yang hak aksesnya tidak melebihi hak akses Anda.</li>
            <li>Akses login, role, dan status akun Anda sendiri tidak dapat diubah dari halaman ini.</li>
            <li>User yang sudah pernah ditugaskan tidak dapat dihapus. Ubah statusnya menjadi nonaktif.</li>
        </x-slot:notes>
    </x-ui.guide-modal>
@endsection

@section('script')
    <script src="{{ asset('assets/js/organizer/master-crud.js') }}"></script>
    <script src="{{ asset('assets/js/master/user.js') }}"></script>
@endsection
