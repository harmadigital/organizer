@extends('layouts.main_layout', ['title' => 'Panitia · '.$event->name, 'breadcrum' => ['Event', $event->code, 'Panitia']])

@section('content')
    <div class="ui-page">
        @include('events.partials.header', ['actionsView' => 'events.partials.committee-actions'])

        <div class="card ui-panel" data-committee>
            <div class="card-header">
                <div class="card-title"><h3>Susunan Panitia</h3></div>
                <div class="card-toolbar gap-2">
                    <button type="button" class="btn btn-sm btn-light" data-tree-toggle="open">Buka Semua</button>
                    <button type="button" class="btn btn-sm btn-light" data-tree-toggle="close">Tutup Semua</button>
                    <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_committee_chart" data-chart-open>
                        <i class="ki-duotone ki-abstract-26 fs-3"><span class="path1"></span><span class="path2"></span></i> Lihat Bagan
                    </button>
                </div>
            </div>
            <div class="card-body">
                @php($eventRoles = $roles->where('scope', \App\Enums\CommitteeRoleScope::Event)
                    ->filter(fn ($role) => isset($coreAssignments[$role->id]) || ($rules[$role->id]->is_required ?? false)))
                @php($rootDivisions = $divisionsByParent[''] ?? collect())
                <div class="ui-tree" data-committee-tree>
                    <details open data-core-committee>
                        <summary>
                            <span class="ui-tree-caret"></span>
                            <span class="ui-tree-label ui-tree-label--group">Pengurus Inti</span>
                            <span class="ui-tree-count">{{ $coreAssignments->flatten()->count() }} orang</span>
                        </summary>
                        <div class="ui-tree-children">
                            @foreach ($eventRoles as $role)
                                @php($holders = $coreAssignments[$role->id] ?? collect())
                                <details open>
                                    <summary>
                                        <span class="ui-tree-caret"></span>
                                        <span class="ui-tree-label ui-tree-label--role">{{ $role->name }}</span>
                                        @if ($rules[$role->id]->is_required ?? false)
                                            <span class="badge badge-light-warning fs-8">Wajib</span>
                                        @endif
                                        <span class="ui-tree-count">{{ $holders->count() }}</span>
                                    </summary>
                                    <div class="ui-tree-children">
                                        @forelse ($holders as $assignment)
                                            <div class="ui-tree-leaf">@include('events.partials.assignment-row')</div>
                                        @empty
                                            <div class="ui-tree-leaf"><div class="ui-tree-note">Belum terisi.</div></div>
                                        @endforelse
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </details>

                    <details open data-divisions>
                        <summary>
                            <span class="ui-tree-caret"></span>
                            <span class="ui-tree-label ui-tree-label--group">Seksi</span>
                            <span class="ui-tree-count">{{ count($divisionTree) }} seksi</span>
                        </summary>
                        <div class="ui-tree-children">
                            @forelse ($rootDivisions as $division)
                                @include('events.partials.tree-division')
                            @empty
                                <div class="ui-tree-leaf"><div class="ui-tree-note">Belum ada seksi.@if ($canManageDivisions) Tambahkan seksi satu per satu, atau buat sekaligus dari template seksi.@endif</div></div>
                            @endforelse
                        </div>
                    </details>

                    @if ($membersWithoutRole->isNotEmpty())
                        <details open>
                            <summary>
                                <span class="ui-tree-caret"></span>
                                <span class="ui-tree-label ui-tree-label--group">Tanpa Jabatan Aktif</span>
                                <span class="ui-tree-count">{{ $membersWithoutRole->count() }} orang</span>
                            </summary>
                            <div class="ui-tree-children">
                                @foreach ($membersWithoutRole as $membership)
                                    <div class="ui-tree-leaf">
                                        <div class="ui-committee-row">
                                            <div>
                                                <div class="fw-semibold text-gray-800 fs-6">{{ $membership->user->name }}</div>
                                                <div class="text-muted fs-7">Seluruh jabatannya sudah berakhir. Tanpa jabatan, ia tidak memiliki akses ke event ini.</div>
                                            </div>
                                            @if (($membershipAuthority[$membership->id] ?? false) && $membership->user_id !== auth()->id())
                                                <div class="ui-committee-actions">
                                                    <button type="button" class="btn btn-sm btn-light-danger" data-membership-revoke="{{ route('events.memberships.revoke', [$event, $membership]) }}" data-person="{{ $membership->user->name }}">Keluarkan</button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kt_modal_committee_chart" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_committee_chart_title" data-org-chart>
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h3 class="modal-title" id="kt_modal_committee_chart_title">Bagan Organisasi</h3>
                        <div class="text-muted fs-7 mt-1">{{ $event->name }} · {{ $event->code }}</div>
                    </div>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
                </div>
                <div class="modal-body ui-org-canvas">
                    @include('events.partials.org-chart')
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="d-flex flex-wrap gap-4 text-muted fs-7">
                        <span><span class="ui-org-legend ui-org-legend--lead"></span> Ketua</span>
                        <span><span class="ui-org-legend ui-org-legend--side"></span> Pengawas</span>
                        <span><span class="ui-org-legend ui-org-legend--empty"></span> Belum terisi</span>
                        <span><span class="badge badge-light-warning fs-9">menunggu</span> Belum menerima undangan</span>
                    </div>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    @if ($event->isEditable() && $assignableRoles !== [])
        <div class="modal fade" id="kt_modal_assignment" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_assignment_title"
            data-assignable-roles='@json($assignableRoles)' data-url="{{ route('events.assignments.insert', $event) }}">
            <div class="modal-dialog modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="kt_modal_assignment_title">Tambah Panitia</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
                    </div>
                    <form class="form d-flex flex-column overflow-hidden" id="kt_form_assignment" novalidate>
                        <div class="modal-body overflow-auto">
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2" for="assignment_user">Orang</label>
                                <select name="user_id" id="assignment_user" class="form-select form-select-solid"
                                    data-select-remote="{{ route('events.event_people_options', $event) }}" data-placeholder="Ketik nama, username, atau telepon">
                                    <option></option>
                                </select>
                                <div class="fs-7 text-muted mt-2">Orang yang belum terdaftar ditambahkan dulu di halaman User.</div>
                            </div>
                            <div class="row g-7 mb-7">
                                <div class="col-md-6 fv-row">
                                    <label class="required fw-semibold fs-6 mb-2" for="assignment_role">Jabatan</label>
                                    <select name="committee_role_id" id="assignment_role" class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-dropdown-parent="#kt_modal_assignment" data-placeholder="Pilih jabatan">
                                        <option></option>
                                        @foreach ($assignableRoles as $role)
                                            <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 fv-row d-none" data-assignment-division>
                                    <label class="required fw-semibold fs-6 mb-2" for="assignment_division">Seksi</label>
                                    <select name="division_id" id="assignment_division" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#kt_modal_assignment" data-placeholder="Pilih seksi">
                                        <option></option>
                                        @foreach ($divisionTree as $row)
                                            <option value="{{ $row['division']->id }}">{{ str_repeat('— ', $row['depth']).$row['division']->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="fv-row mb-7 d-none" data-assignment-primary>
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="is_primary" value="1" checked>
                                    <span class="form-check-label fw-semibold text-gray-800">Pemegang utama</span>
                                </label>
                                <div class="fs-7 text-muted mt-2">Jabatan ini hanya memiliki satu pemegang utama. Matikan untuk menugaskan sebagai pendamping.</div>
                            </div>
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2" for="assignment_job_description">Uraian Tugas</label>
                                <textarea name="job_description" id="assignment_job_description" class="form-control form-control-solid" rows="3" maxlength="2000" placeholder="Tugas khusus orang ini, bila ada"></textarea>
                            </div>
                            <div class="fv-row">
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="confirm_now" value="1" data-assignment-confirm>
                                    <span class="form-check-label fw-semibold text-gray-800">Sudah menyatakan bersedia</span>
                                </label>
                                <div class="fs-7 text-muted mt-2" data-assignment-confirm-hint>Bila tidak dicentang, orang ini menerima undangan dan baru mendapat akses setelah menerimanya.</div>
                            </div>
                        </div>
                        <div class="modal-footer flex-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" data-assignment-submit>
                                <span class="indicator-label">Simpan</span>
                                <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="kt_modal_assignment_edit" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_assignment_edit_title">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="kt_modal_assignment_edit_title" data-assignment-edit-title>Ubah Jabatan</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
                </div>
                <form class="form" id="kt_form_assignment_edit" novalidate>
                    <div class="modal-body">
                        <div class="fv-row mb-7 d-none" data-assignment-edit-primary>
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_primary" value="1">
                                <span class="form-check-label fw-semibold text-gray-800">Pemegang utama</span>
                            </label>
                        </div>
                        <div class="fv-row">
                            <label class="fw-semibold fs-6 mb-2" for="assignment_edit_job_description">Uraian Tugas</label>
                            <textarea name="job_description" id="assignment_edit_job_description" class="form-control form-control-solid" rows="4" maxlength="2000"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" data-assignment-edit-submit>
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($canManageDivisions)
        <div class="modal fade" id="kt_modal_division" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_division_title" data-url="{{ route('events.divisions.insert', $event) }}">
            <div class="modal-dialog modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="kt_modal_division_title" data-division-title>Tambah Seksi</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
                    </div>
                    <form class="form d-flex flex-column overflow-hidden" id="kt_form_division" novalidate>
                        <div class="modal-body overflow-auto">
                            <div class="row g-7 mb-7">
                                <div class="col-md-8 fv-row">
                                    <label class="required fw-semibold fs-6 mb-2" for="division_name">Nama Seksi</label>
                                    <input type="text" name="name" id="division_name" class="form-control form-control-solid" maxlength="150" placeholder="Contoh: Seksi Acara">
                                </div>
                                <div class="col-md-4 fv-row">
                                    <label class="fw-semibold fs-6 mb-2" for="division_sort_order">Urutan</label>
                                    <input type="number" name="sort_order" id="division_sort_order" class="form-control form-control-solid" min="0" max="9999" inputmode="numeric">
                                </div>
                            </div>
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2" for="division_parent">Seksi Induk</label>
                                <select name="parent_id" id="division_parent" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#kt_modal_division" data-placeholder="Tidak ada (seksi utama)" data-allow-clear="true">
                                    <option></option>
                                    @foreach ($divisionTree as $row)
                                        <option value="{{ $row['division']->id }}">{{ str_repeat('— ', $row['depth']).$row['division']->name }}</option>
                                    @endforeach
                                </select>
                                <div class="fs-7 text-muted mt-2">Isi bila seksi ini adalah sub-seksi.</div>
                            </div>
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2" for="division_description">Deskripsi</label>
                                <input type="text" name="description" id="division_description" class="form-control form-control-solid" maxlength="1000">
                            </div>
                            <div class="fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="division_responsibilities">Tanggung Jawab</label>
                                <textarea name="responsibilities" id="division_responsibilities" class="form-control form-control-solid" rows="5" maxlength="5000" placeholder="Satu tanggung jawab per baris"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer flex-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" data-division-submit>
                                <span class="indicator-label">Simpan</span>
                                <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="kt_modal_division_templates" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_division_templates_title" data-url="{{ route('events.divisions.from_templates', $event) }}">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="kt_modal_division_templates_title">Seksi dari Template</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
                    </div>
                    <form class="form" id="kt_form_division_templates" novalidate>
                        <div class="modal-body">
                            @if ($templates->isEmpty())
                                <div class="text-muted fs-6">Belum ada template seksi aktif. Tambahkan di Master Data → Template Seksi.</div>
                            @else
                                <div class="fs-7 text-muted mb-5">Seksi yang namanya sudah ada di event ini dilewati.</div>
                                <div class="fv-row">
                                    @foreach ($templates as $template)
                                        <label class="form-check form-check-custom form-check-solid mb-4">
                                            <input class="form-check-input" type="checkbox" name="template_ids[]" value="{{ $template->id }}" @checked($divisions->doesntContain('name', $template->name))>
                                            <span class="form-check-label text-gray-800">{{ $template->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer flex-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" data-division-templates-submit @disabled($templates->isEmpty())>
                                <span class="indicator-label">Buat Seksi</span>
                                <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @include('events.partials.event-guide')
@endsection

@section('script')
    <script src="{{ asset('assets/js/events/committee.js') }}"></script>
@endsection
