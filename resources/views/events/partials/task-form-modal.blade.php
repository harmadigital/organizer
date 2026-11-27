{{--
    Form tambah/ubah tugas. Yang terlihat hanya yang selalu dibutuhkan: judul, seksi,
    penanggung jawab, dan tenggat. Pengaturan lain ada di "Pengaturan lainnya".
--}}
<div class="modal fade" id="kt_modal_task" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_task_title"
    @if (! empty($selfOnlyMembership)) data-self-membership-id="{{ $selfOnlyMembership['id'] }}" data-self-membership-name="{{ $selfOnlyMembership['name'] }}" @endif>
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="kt_modal_task_title" data-task-form-title>Tambah Tugas</h3>
                    <div class="text-muted fs-7 mt-1 d-none" data-task-form-context></div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
            </div>
            <form class="form d-flex flex-column overflow-hidden" id="kt_form_task" autocomplete="off" novalidate>
                <div class="modal-body overflow-auto">
                    <div class="fv-row mb-6">
                        <label class="required fw-semibold fs-6 mb-2" for="task_title">Apa yang harus dikerjakan?</label>
                        <input type="text" name="title" id="task_title" class="form-control form-control-lg form-control-solid" maxlength="200" placeholder="Contoh: Sewa sound system untuk ibadah malam Natal">
                    </div>

                    <div class="row g-6 mb-2">
                        <div class="col-md-4 fv-row" data-task-division-row>
                            <label class="fw-semibold fs-6 mb-2" for="task_division">Seksi</label>
                            <select name="division_id" id="task_division" class="form-select form-select-solid" data-control="select2" data-allow-clear="true" data-placeholder="Pengurus Inti (tanpa seksi)">
                                <option value=""></option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 fv-row" data-task-owner-row>
                            <label class="fw-semibold fs-6 mb-2" for="task_owner">Penanggung Jawab</label>
                            <select name="owner_membership_id" id="task_owner" class="form-select form-select-solid" data-select-remote="{{ route('events.work.member_options', $event) }}" data-allow-clear="true" data-placeholder="Pilih panitia">
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="col-md-4 fv-row">
                            <label class="fw-semibold fs-6 mb-2" for="task_due_on">Tenggat</label>
                            <input type="text" name="due_on" id="task_due_on" class="form-control form-control-solid" data-date-picker data-date-min="#task_starts_on" placeholder="Pilih tanggal">
                        </div>
                    </div>

                    <button type="button" class="btn btn-link btn-color-gray-700 btn-active-color-primary px-0 mt-4 fs-6" data-task-advanced-toggle aria-expanded="false" aria-controls="task_advanced">
                        <i class="ki-duotone ki-down fs-4 me-1" data-task-advanced-icon></i> <span data-task-advanced-label>Pengaturan lainnya</span>
                        <span class="text-muted fs-7 ms-1">(program, subtugas, anggota, besar pekerjaan)</span>
                    </button>

                    <div class="d-none pt-5" id="task_advanced" data-task-advanced>
                        <div class="row g-6 mb-6">
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="task_program">Program Kerja</label>
                                <select name="work_program_id" id="task_program" class="form-select form-select-solid" data-select-remote="{{ route('events.work.program_options', $event) }}" data-allow-clear="true" data-placeholder="Tidak terkait program">
                                    <option value=""></option>
                                </select>
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="task_parent">Bagian dari tugas</label>
                                <select name="parent_id" id="task_parent" class="form-select form-select-solid" data-select-remote="{{ route('events.work.task_options', $event) }}" data-allow-clear="true" data-placeholder="Bukan subtugas">
                                    <option value=""></option>
                                </select>
                                <div class="fs-7 text-muted mt-2">Isi bila ini pecahan dari tugas yang lebih besar.</div>
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="task_assignees">Dibantu oleh</label>
                                <select name="assignee_ids[]" id="task_assignees" class="form-select form-select-solid" multiple data-select-remote="{{ route('events.work.member_options', $event) }}" data-placeholder="Panitia lain yang ikut mengerjakan">
                                </select>
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="task_starts_on">Mulai</label>
                                <input type="text" name="starts_on" id="task_starts_on" class="form-control form-control-solid" data-date-picker placeholder="Pilih tanggal">
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="required fw-semibold fs-6 mb-2" for="task_priority">Seberapa mendesak?</label>
                                <select name="priority" id="task_priority" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                                    @foreach ($priorities as $priority)
                                        <option value="{{ $priority->value }}" @selected($priority === \App\Enums\TaskPriority::Medium)>{{ $priority->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8 fv-row">
                                <span class="required fw-semibold fs-6 mb-2 d-block" id="task_weight_label">Besar pekerjaan</span>
                                <div class="ui-choice-group" role="radiogroup" aria-labelledby="task_weight_label">
                                    @foreach (\App\Enums\TaskSize::cases() as $size)
                                        <label class="ui-choice">
                                            <input type="radio" name="weight" value="{{ $size->value }}" @checked($size === \App\Enums\TaskSize::Medium)>
                                            <span class="ui-choice-body">
                                                <span class="ui-choice-title">{{ $size->label() }}</span>
                                                <span class="ui-choice-hint">{{ $size->hint() }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="fs-7 text-muted mt-2">Dipakai untuk menghitung progres: tugas berat dihitung lebih besar daripada tugas ringan.</div>
                            </div>
                        </div>

                        <div class="fv-row mb-6">
                            <label class="fw-semibold fs-6 mb-2" for="task_description">Keterangan</label>
                            <textarea name="description" id="task_description" class="form-control form-control-solid" rows="3" maxlength="5000" placeholder="Rincian atau hasil yang diharapkan"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-task-form-submit>
                        <span class="indicator-label">Simpan</span>
                        <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
