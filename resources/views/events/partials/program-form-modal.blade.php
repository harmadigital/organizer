{{-- Form tambah/ubah program kerja seksi. Tujuan, keluaran, indikator, dan status ada di "Detail lainnya". --}}
<div class="modal fade" id="kt_modal_program" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_program_title">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="kt_modal_program_title" data-program-form-title>Tambah Program Kerja</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
            </div>
            <form class="form d-flex flex-column overflow-hidden" id="kt_form_program" autocomplete="off" novalidate>
                <div class="modal-body overflow-auto">
                    <div class="fv-row mb-6">
                        <label class="required fw-semibold fs-6 mb-2" for="program_title">Nama Program</label>
                        <input type="text" name="title" id="program_title" class="form-control form-control-lg form-control-solid" maxlength="200" placeholder="Contoh: Persiapan liturgi ibadah Natal">
                    </div>
                    <div class="row g-6">
                        <div class="col-md-6 fv-row">
                            <label class="fw-semibold fs-6 mb-2" for="program_division">Seksi</label>
                            <select name="division_id" id="program_division" class="form-select form-select-solid" data-control="select2" data-allow-clear="true" data-placeholder="Pengurus Inti (tanpa seksi)">
                                <option value=""></option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="fw-semibold fs-6 mb-2" for="program_pic">Penanggung Jawab</label>
                            <select name="pic_membership_id" id="program_pic" class="form-select form-select-solid" data-select-remote="{{ route('events.work.member_options', $event) }}" data-allow-clear="true" data-placeholder="Pilih panitia">
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="col-6 fv-row">
                            <label class="fw-semibold fs-6 mb-2" for="program_starts_on">Mulai</label>
                            <input type="text" name="starts_on" id="program_starts_on" class="form-control form-control-solid" data-date-picker placeholder="Pilih tanggal">
                        </div>
                        <div class="col-6 fv-row">
                            <label class="fw-semibold fs-6 mb-2" for="program_ends_on">Selesai</label>
                            <input type="text" name="ends_on" id="program_ends_on" class="form-control form-control-solid" data-date-picker data-date-min="#program_starts_on" placeholder="Pilih tanggal">
                        </div>
                    </div>

                    <button type="button" class="btn btn-link btn-color-gray-700 btn-active-color-primary px-0 mt-5 fs-6" data-program-advanced-toggle aria-expanded="false" aria-controls="program_advanced">
                        <i class="ki-duotone ki-down fs-4 me-1"></i> <span data-program-advanced-label>Detail lainnya</span>
                        <span class="text-muted fs-7 ms-1">(tujuan, keluaran, indikator, status)</span>
                    </button>

                    <div class="d-none pt-5" id="program_advanced" data-program-advanced>
                        <div class="fv-row mb-6">
                            <label class="fw-semibold fs-6 mb-2" for="program_objective">Tujuan</label>
                            <textarea name="objective" id="program_objective" class="form-control form-control-solid" rows="2" maxlength="2000" placeholder="Apa yang ingin dicapai program ini"></textarea>
                        </div>
                        <div class="row g-6 mb-6">
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="program_output">Keluaran</label>
                                <textarea name="output" id="program_output" class="form-control form-control-solid" rows="2" maxlength="2000" placeholder="Hasil nyata yang diserahkan"></textarea>
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="program_indicator">Indikator Keberhasilan</label>
                                <textarea name="indicator" id="program_indicator" class="form-control form-control-solid" rows="2" maxlength="2000" placeholder="Cara mengukur bahwa program berhasil"></textarea>
                            </div>
                        </div>
                        <div class="row g-6">
                            <div class="col-md-4 fv-row">
                                <label class="required fw-semibold fs-6 mb-2" for="program_status">Status</label>
                                <select name="status" id="program_status" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                                    @foreach ($programStatuses as $status)
                                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="program_sort_order">Urutan</label>
                                <input type="number" name="sort_order" id="program_sort_order" class="form-control form-control-solid" min="0" max="9999" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-program-form-submit>
                        <span class="indicator-label">Simpan</span>
                        <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
