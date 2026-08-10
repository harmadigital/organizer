@extends('layouts.main_layout',['title'=>'Master','breadcrum'=>['Master','User']])

@section('content')
<div class="col-xxl-12 mb-7" data-user-section="header">
    <div class="card">
        <div class="card-header card-header-stretch">
            <div class="card-title">
                <h3 class="m-0 text-gray-800">Manajemen Pengguna</h3>
            </div>
            <div class="card-toolbar">
                <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                    <li class="nav-item">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 active" href="{{ route('master.user.index') }}">
                            User
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5" href="{{ route('master.role.index') }}">
                            Role & Permission
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-5 g-xl-8 mb-7" data-user-section="counter">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <span class="symbol symbol-40px symbol-circle bg-light-primary">
                    <span class="symbol-label"><i class="ki-duotone ki-profile-user fs-2 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></span>
                </span>
                <div>
                    <div class="fs-2 fw-bold text-gray-800">{{ $userSummary['total'] }}</div>
                    <div class="text-muted fw-semibold fs-7">Total User</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border border-success border-dashed bg-light-success">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <span class="symbol symbol-40px symbol-circle">
                    <span class="symbol-label bg-success"><i class="ki-solid ki-check fs-2 text-white"></i></span>
                </span>
                <div>
                    <div class="fs-2 fw-bold text-success">{{ $userSummary['active'] }}</div>
                    <div class="text-success fw-semibold fs-7">Active</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border border-danger border-dashed bg-light-danger">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <span class="symbol symbol-40px symbol-circle">
                    <span class="symbol-label bg-danger"><i class="ki-solid ki-cross fs-2 text-white"></i></span>
                </span>
                <div>
                    <div class="fs-2 fw-bold text-danger">{{ $userSummary['inactive'] }}</div>
                    <div class="text-danger fw-semibold fs-7">Inactive</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-xxl-12 mb-7" data-user-section="filter">
    <div class="card">
        <div class="card-body py-6">
            <div class="row g-5">
                <div class="col-lg-5">
                    <div class="position-relative">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-5"><span class="path1"></span><span class="path2"></span></i>
                        <input type="text" id="search_user_name" class="form-control form-control-solid ps-14" placeholder="Cari nama, email...">
                    </div>
                </div>
                <div class="col-lg-4">
                    <select id="filter_user_role" class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Semua Role">
                        <option value=""></option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3">
                    <select id="filter_user_status" class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Semua Status">
                        <option value=""></option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2 py-3">
            <button type="button" id="filter_user_submit" class="btn btn-sm btn-light-primary px-4">
                <i class="ki-duotone ki-filter fs-3"><span class="path1"></span><span class="path2"></span></i> Filter
            </button>
            @can('users.create')
                <button type="button" class="btn btn-sm btn-primary px-4" data-bs-toggle="modal" data-bs-target="#kt_modal_add_user">
                    <i class="ki-duotone ki-plus fs-3"></i> Tambah User
                </button>
            @endcan
        </div>
    </div>
</div>

<div class="col-xxl-12" data-user-section="list">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <h3 class="m-0 text-gray-800">List Data User</h3>
            </div>
        </div>
        <div class="card-body py-6">
            <div class="table-responsive">
                <table id="kt_datatable_user" class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                            <th>#</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Diedit Terakhir</th>
                            <th class="text-end min-w-100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="kt_modal_add_user" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Tambah User Baru</h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times fs-2"></i>
                </div>
            </div>

            <form id="kt_docs_form_add_user" class="form" action="#" autocomplete="off">
                @csrf
                <div class="modal-body py-10 px-lg-17">
                    <div class="scroll-y me-n7 pe-7" data-kt-scroll="true" data-kt-scroll-max-height="auto">
                        <div class="mb-7">
                            <h4 class="mb-4">Informasi Akun</h4>
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Nama Lengkap</label>
                                <input type="text" name="name" id="name" class="form-control form-control-solid" placeholder="Masukkan nama lengkap user"/>
                            </div>
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Email</label>
                                <input type="email" name="email" id="email" class="form-control form-control-solid" placeholder="contoh@domain.com"/>
                            </div>
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Username</label>
                                <input type="text" name="username" id="username" class="form-control form-control-solid" placeholder="Masukkan username unik"/>
                            </div>
                        </div>

                        <div class="separator separator-dashed my-9"></div>

                        <div class="mb-7">
                            <h4 class="mb-4">Keamanan</h4>
                            <div class="row" data-kt-password-meter="true">
                                <div class="col-md-6 fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Password</label>
                                    <div class="position-relative mb-3">
                                        <input class="form-control form-control-solid" type="password" placeholder="Masukkan password" name="password" id="password" autocomplete="off" />
                                        <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" data-kt-password-meter-control="visibility">
                                            <i class="bi bi-eye-slash fs-2"></i>
                                            <i class="bi bi-eye fs-2 d-none"></i>
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
                                    </div>
                                    <div class="text-muted">Gunakan 8 karakter atau lebih dengan campuran huruf, angka & simbol.</div>
                                </div>
                                <div class="col-md-6 fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Konfirmasi Password</label>
                                    <input class="form-control form-control-solid" type="password" placeholder="Ulangi password" name="password_confirmation" id="password_confirmation" autocomplete="off" />
                                </div>
                            </div>
                            <div class="fv-row">
                                <label class="required fw-semibold fs-6 mb-2">Role</label>
                                <select class="form-select form-select-solid" name="role" id="role" data-control="select2" data-hide-search="true" data-dropdown-parent="#kt_modal_add_user" data-placeholder="Pilih Role">
                                    <option></option>
                                    @foreach ($roles as $item)
                                        <option value="{{ $item->name }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button id="kt_docs_form_add_user_submit" type="submit" class="btn btn-primary">
                        <span class="indicator-label">Simpan</span>
                        <span class="indicator-progress">Please wait...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" id="kt_modal_edit_user">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit User</h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times fs-2"></i>
                </div>
            </div>

            <form id="kt_docs_form_edit_user" class="form" action="#" autocomplete="off">
                @csrf
                <input type="hidden" id="user_id">
                <div class="modal-body py-10 px-lg-17">
                    <div class="scroll-y me-n7 pe-7" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_edit_user_header" data-kt-scroll-wrappers="#kt_modal_edit_user_scroll" data-kt-scroll-offset="300px">

                        <div class="mb-7">
                            <h4 class="mb-4">Informasi Akun</h4>
                             <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Nama Lengkap</label>
                                <input type="text" name="name_edit" id="name_edit" class="form-control form-control-solid" placeholder="Masukkan nama lengkap user"/>
                            </div>
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Email</label>
                                <input type="email" name="email_edit" id="email_edit" class="form-control form-control-solid" placeholder="contoh@domain.com"/>
                            </div>
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Username</label>
                                <input type="text" name="username_edit" id="username_edit" class="form-control form-control-solid" placeholder="Masukkan username unik"/>
                            </div>
                        </div>
                        <div class="separator separator-dashed my-9"></div>

                        <div class="mb-7">
                            <h4 class="mb-4">Keamanan</h4>
                            <div class="row" data-kt-password-meter="true">
                                <div class="col-md-6 fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Password</label>
                                    <div class="position-relative mb-3">
                                        <input class="form-control form-control-solid" type="password" placeholder="Masukkan password" name="password_edit" id="password_edit" autocomplete="off" />
                                        <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" data-kt-password-meter-control="visibility">
                                            <i class="bi bi-eye-slash fs-2"></i>
                                            <i class="bi bi-eye fs-2 d-none"></i>
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                        <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
                                    </div>
                                    <div class="text-muted">Gunakan 8 karakter atau lebih dengan campuran huruf, angka & simbol.</div>
                                </div>
                                <div class="col-md-6 fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Konfirmasi Password</label>
                                    <input class="form-control form-control-solid" type="password" placeholder="Ulangi password" name="password_confirmation_edit" id="password_confirmation_edit" autocomplete="off" />
                                </div>
                            </div>
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Role</label>
                                <select class="form-select form-select-solid" name="role_id" id="role_edit" data-control="select2" data-hide-search="true" data-dropdown-parent="#kt_modal_edit_user" data-placeholder="Pilih Role">
                                    <option></option>
                                    @foreach ($roles as $item)
                                        <option value="{{ $item->name }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="fv-row">
                                <label class="fw-semibold fs-6 mb-2">Permission</label>
                                <select class="form-select form-select-solid" name="permission_additional" id="permission_additional" data-control="select2" data-hide-search="true" data-dropdown-parent="#kt_modal_edit_user" data-placeholder="Pilih Role">
                                    <option></option>
                                    @foreach ($permissions as $permission)
                                        <option value="{{ $permission->name }}">{{ $permission->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button id="kt_docs_form_edit_user_submit" type="submit" class="btn btn-primary">
                        <span class="indicator-label">Simpan</span>
                        <span class="indicator-progress">Please wait...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@section('script')
    <script src="{{ asset('assets/js/master/user.js') }}"></script>
@endsection
