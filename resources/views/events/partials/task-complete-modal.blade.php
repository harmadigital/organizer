{{-- Menandai tugas selesai: hasil pekerjaan dan foto/dokumen pendukung, dikirim sekaligus. --}}
<div class="modal fade" id="kt_modal_task_complete" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_task_complete_title">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="kt_modal_task_complete_title">Tandai Tugas Selesai</h3>
                    <div class="text-muted fs-7 mt-1" data-complete-task-title></div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
            </div>
            <form class="form d-flex flex-column overflow-hidden" id="kt_form_task_complete" autocomplete="off" novalidate>
                <div class="modal-body overflow-auto">
                    <div class="fv-row mb-6">
                        <label class="required fw-semibold fs-6 mb-2" for="complete_result">Hasil Pekerjaan</label>
                        <textarea name="result" id="complete_result" class="form-control form-control-solid" rows="5" maxlength="5000"
                            placeholder="Apa yang sudah dikerjakan dan hasilnya. Contoh: Sound system 2 unit sudah disewa dari CV Nada, terpasang 23 Des pukul 16.00."></textarea>
                    </div>
                    <div class="fv-row">
                        <label class="fw-semibold fs-6 mb-2" for="complete_files">Foto / Dokumen</label>
                        <input type="file" name="files[]" id="complete_files" class="form-control form-control-solid" multiple
                            accept="{{ collect(\App\Http\Requests\Work\UploadTaskDocumentRequest::ALLOWED_EXTENSIONS)->map(fn ($extension) => '.'.$extension)->implode(',') }}">
                        <div class="fs-7 text-muted mt-2">Boleh lebih dari satu: foto (JPG/PNG/WEBP), PDF, dokumen Office, TXT, atau CSV. Maksimal {{ \App\Http\Requests\Work\UploadTaskDocumentRequest::MAX_FILES }} file, masing-masing 10 MB.</div>
                        <ul class="list-unstyled fs-7 text-gray-700 mt-3 mb-0" data-complete-file-list></ul>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" data-complete-submit>
                        <span class="indicator-label">Simpan &amp; Tandai Selesai</span>
                        <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
