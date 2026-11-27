{{--
    Form tambah/ubah kegiatan kalender. Terlihat: judul, tanggal, jam, tempat. Seksi, sampai
    tanggal, dan keterangan ada di "Pengaturan lainnya". Kegiatan beberapa hari dicatat seharian.
--}}
<div class="modal fade" id="kt_modal_calendar_entry" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_calendar_entry_title">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="kt_modal_calendar_entry_title" data-entry-form-title>Tambah Kegiatan</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
            </div>
            <form class="form d-flex flex-column overflow-hidden" id="kt_form_calendar_entry" autocomplete="off" novalidate>
                <div class="modal-body overflow-auto">
                    <div class="fv-row mb-6">
                        <label class="required fw-semibold fs-6 mb-2" for="entry_title">Judul</label>
                        <input type="text" name="title" id="entry_title" class="form-control form-control-lg form-control-solid" maxlength="200" placeholder="Contoh: Survei lokasi, Latihan drama anak">
                    </div>
                    <div class="row g-6 mb-2">
                        <div class="col-md-4 fv-row">
                            <label class="required fw-semibold fs-6 mb-2" for="entry_starts_on">Tanggal</label>
                            <input type="text" name="starts_on" id="entry_starts_on" class="form-control form-control-solid" data-date-picker placeholder="Pilih tanggal">
                        </div>
                        <div class="col-6 col-md-4 fv-row" data-entry-time-row>
                            <label class="fw-semibold fs-6 mb-2" for="entry_starts_at">Jam mulai</label>
                            <input type="text" name="starts_at" id="entry_starts_at" class="form-control form-control-solid" data-time-picker placeholder="09:00">
                        </div>
                        <div class="col-6 col-md-4 fv-row" data-entry-time-row>
                            <label class="fw-semibold fs-6 mb-2" for="entry_ends_at">Jam selesai</label>
                            <input type="text" name="ends_at" id="entry_ends_at" class="form-control form-control-solid" data-time-picker placeholder="11:00">
                        </div>
                    </div>
                    <div class="fs-7 text-muted mb-6" data-entry-time-help>Kosongkan jam bila berlangsung seharian.</div>
                    <div class="fs-7 text-muted mb-6 d-none" data-entry-multi-day-help>Kegiatan beberapa hari dicatat seharian, tanpa jam.</div>
                    <div class="fv-row">
                        <label class="fw-semibold fs-6 mb-2" for="entry_location">Tempat</label>
                        <input type="text" name="location" id="entry_location" class="form-control form-control-solid" maxlength="200" placeholder="Contoh: Gedung gereja">
                    </div>

                    <button type="button" class="btn btn-link btn-color-gray-700 btn-active-color-primary px-0 mt-4 fs-6" data-entry-advanced-toggle aria-expanded="false" aria-controls="entry_advanced">
                        <i class="ki-duotone ki-down fs-4 me-1" data-entry-advanced-icon></i> <span data-entry-advanced-label>Pengaturan lainnya</span>
                        <span class="text-muted fs-7 ms-1">(seksi, beberapa hari, keterangan)</span>
                    </button>

                    <div class="d-none pt-5" id="entry_advanced" data-entry-advanced>
                        <div class="row g-6 mb-6">
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="entry_division">Seksi</label>
                                <select name="division_id" id="entry_division" class="form-select form-select-solid" data-control="select2" data-allow-clear="true" data-placeholder="Pengurus Inti / seluruh panitia" data-default="{{ $defaultDivisionId }}">
                                    <option value=""></option>
                                    @foreach ($divisions as $division)
                                        <option value="{{ $division->id }}">{{ $division->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="entry_ends_on">Sampai tanggal</label>
                                <input type="text" name="ends_on" id="entry_ends_on" class="form-control form-control-solid" data-date-picker data-date-min="#entry_starts_on" placeholder="Pilih tanggal">
                                <div class="fs-7 text-muted mt-2">Isi bila kegiatan berlangsung beberapa hari. Kegiatan beberapa hari dicatat seharian.</div>
                            </div>
                        </div>
                        <div class="fv-row">
                            <label class="fw-semibold fs-6 mb-2" for="entry_notes">Keterangan</label>
                            <textarea name="notes" id="entry_notes" class="form-control form-control-solid" rows="4" maxlength="2000" placeholder="Contoh: Bawa meteran dan daftar kebutuhan"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-entry-form-submit>
                        <span class="indicator-label">Simpan</span>
                        <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
