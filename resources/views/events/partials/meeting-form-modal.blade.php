{{-- Form tambah/ubah rapat. Rapat langsung terlihat oleh semua panitia; tidak ada undangan. --}}
<div class="modal fade" id="kt_modal_meeting" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_meeting_title">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="kt_modal_meeting_title" data-meeting-form-title>Jadwalkan Rapat</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
            </div>
            <form class="form d-flex flex-column overflow-hidden" id="kt_form_meeting" autocomplete="off" novalidate>
                <div class="modal-body overflow-auto">
                    <div class="fv-row mb-6">
                        <label class="required fw-semibold fs-6 mb-2" for="meeting_title">Nama rapat</label>
                        <input type="text" name="title" id="meeting_title" class="form-control form-control-lg form-control-solid" maxlength="200" placeholder="Contoh: Rapat pleno persiapan Natal">
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-md-4 fv-row">
                            <label class="required fw-semibold fs-6 mb-2" for="meeting_meets_on">Tanggal</label>
                            <input type="text" name="meets_on" id="meeting_meets_on" class="form-control form-control-solid" data-date-picker placeholder="Pilih tanggal">
                        </div>
                        <div class="col-6 col-md-4 fv-row">
                            <label class="required fw-semibold fs-6 mb-2" for="meeting_starts_at">Jam mulai</label>
                            <input type="text" name="starts_at" id="meeting_starts_at" class="form-control form-control-solid" data-time-picker placeholder="19:00">
                        </div>
                        <div class="col-6 col-md-4 fv-row">
                            <label class="fw-semibold fs-6 mb-2" for="meeting_ends_at">Jam selesai</label>
                            <input type="text" name="ends_at" id="meeting_ends_at" class="form-control form-control-solid" data-time-picker placeholder="21:00">
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="fw-semibold fs-6 mb-2" for="meeting_location">Tempat</label>
                            <input type="text" name="location" id="meeting_location" class="form-control form-control-solid" maxlength="200" placeholder="Contoh: Ruang konsistori atau tautan Zoom">
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="fw-semibold fs-6 mb-2" for="meeting_division">Rapat untuk</label>
                            <select name="division_id" id="meeting_division" class="form-select form-select-solid" data-control="select2" data-allow-clear="true" data-placeholder="Pengurus Inti / seluruh panitia">
                                <option value=""></option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="fv-row">
                        <label class="fw-semibold fs-6 mb-2" for="meeting_agenda">Agenda</label>
                        <textarea name="agenda" id="meeting_agenda" class="form-control form-control-solid" rows="5" maxlength="5000" placeholder="Satu agenda per baris, misalnya:&#10;Laporan tiap seksi&#10;Pembahasan susunan acara&#10;Lain-lain"></textarea>
                        <div class="fs-7 text-muted mt-2">Tulis satu agenda per baris.</div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-meeting-form-submit>
                        <span class="indicator-label">Simpan</span>
                        <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
