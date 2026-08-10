@extends('layouts.main_layout', ['title' => 'Role & Permission', 'breadcrum' => ['Master', 'Role & Permission']])

@section('content')
    <div class="col-xxl-12 mb-7" data-role-section="header">
        <div class="card">
            <div class="card-header card-header-stretch">
                <div class="card-title">
                    <h3 class="m-0 text-gray-800">Manajemen Pengguna</h3>
                </div>
                <div class="card-toolbar">
                    <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                        <li class="nav-item">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5" href="{{ route('master.user.index') }}">
                                User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 active"
                                href="{{ route('master.role.index') }}">
                                Role & Permission
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-7" data-role-section="counter">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-4">
                    <span class="symbol symbol-40px symbol-circle bg-light-primary">
                        <span class="symbol-label"><i class="ki-duotone ki-security-user fs-2 text-primary"><span class="path1"></span><span class="path2"></span></i></span>
                    </span>
                    <div>
                        <div class="fs-2 fw-bold text-gray-800">{{ $roleSummary['total'] }}</div>
                        <div class="text-muted fw-semibold fs-7">Total Role</div>
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
                        <div class="fs-2 fw-bold text-success">{{ $roleSummary['permissions'] }}</div>
                        <div class="text-success fw-semibold fs-7">Total Permission</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border border-primary border-dashed bg-light-primary">
                <div class="card-body d-flex align-items-center gap-3 py-4">
                    <span class="symbol symbol-40px symbol-circle">
                        <span class="symbol-label bg-primary"><i class="ki-solid ki-people fs-2 text-white"></i></span>
                    </span>
                    <div>
                        <div class="fs-2 fw-bold text-primary">{{ $roleSummary['used'] }}</div>
                        <div class="text-primary fw-semibold fs-7">Role Digunakan</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-12 mb-7" data-role-section="filter">
        <div class="card">
            <div class="card-body py-4">
                <div class="position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-5"><span class="path1"></span><span class="path2"></span></i>
                    <input type="text" id="search_role_name" data-kt-permissions-table-filter="search"
                        class="form-control form-control-solid ps-14" placeholder="Cari nama role...">
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2 py-3">
                <button type="button" id="filter_role_submit" class="btn btn-sm btn-light-primary px-4">
                    <i class="ki-duotone ki-filter fs-3"><span class="path1"></span><span class="path2"></span></i> Filter
                </button>
                @can('roles.create')
                    <button type="button" class="btn btn-sm btn-primary px-4" data-bs-toggle="modal"
                        data-bs-target="#kt_modal_add_role">
                        <i class="ki-duotone ki-plus fs-3"></i> Tambah Role
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="col-xxl-12" data-role-section="list">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="m-0 text-gray-800">List Data Role</h3>
                </div>
            </div>
            <div class="card-body py-6">
                <div class="table-responsive">
                    <table id="kt_datatable_role" class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th>#</th>
                                <th>Nama Role</th>
                                <th>Dibuat Tanggal</th>
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

    <div class="modal fade" tabindex="-1" id="kt_modal_add_role">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Tambah Role</h3>
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close">
                        <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg width="24" height="24"
                                viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10"
                                    fill="currentColor" />
                                <rect x="7" y="15.3137" width="12" height="2" rx="1"
                                    transform="rotate(-45 7 15.3137)" fill="currentColor" />
                                <rect x="8.41422" y="7" width="12" height="2" rx="1"
                                    transform="rotate(45 8.41422 7)" fill="currentColor" />
                            </svg>
                        </span>
                    </div>
                    <!--end::Close-->
                </div>

                <!--begin::Form-->
                <form id="kt_docs_form_add_role" class="form" action="#" autocomplete="off">
                    <div class="modal-body py-7 px-lg-10">
                        <!--begin::Scroll-->
                        <div class="scroll-y me-n7 pe-7" data-kt-scroll="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-offset="300px">
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Nama Role</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="name" id="name"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Nama Role"
                                    value="" />
                                <!--end::Input-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="fv-row mb-0">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Permission</label>
                                <!--end::Label-->
                                @include('master.partials.permission-picker', ['pickerId' => 'add'])
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Scroll-->
                    </div>

                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                        <button id="kt_docs_form_add_role_submit" type="submit" class="btn btn-primary">
                            <span class="indicator-label">
                                Simpan
                            </span>
                            <span class="indicator-progress">
                                Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
                <!--end::Form-->
            </div>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" id="kt_modal_edit_role">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Edit Role</h3>
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close">
                        <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg width="24" height="24"
                                viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10"
                                    fill="currentColor" />
                                <rect x="7" y="15.3137" width="12" height="2" rx="1"
                                    transform="rotate(-45 7 15.3137)" fill="currentColor" />
                                <rect x="8.41422" y="7" width="12" height="2" rx="1"
                                    transform="rotate(45 8.41422 7)" fill="currentColor" />
                            </svg>
                        </span>
                    </div>
                    <!--end::Close-->
                </div>

                <!--begin::Form-->
                <form id="kt_docs_form_edit_role" class="form" action="#" autocomplete="off">
                    <input type="hidden" name="role_id" id="role_id">
                    <div class="modal-body py-7 px-lg-10">
                        <!--begin::Scroll-->
                        <div class="scroll-y me-n7 pe-7" data-kt-scroll="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-offset="300px">
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Nama Role</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="name_edit" id="name_edit"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Nama Role"
                                    value="" readonly />
                                <!--end::Input-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="fv-row mb-0">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Permission</label>
                                <!--end::Label-->
                                @include('master.partials.permission-picker', ['pickerId' => 'edit'])
                            </div>
                            <!--end::Input group-->
                        </div>
                    </div>

                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                        <button id="kt_docs_form_edit_role_submit" type="submit" class="btn btn-primary">
                            <span class="indicator-label">
                                Simpan
                            </span>
                            <span class="indicator-progress">
                                Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
                <!--end::Form-->
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ asset('assets/js/master/role.js') }}"></script>
@endsection
