@props(['id' => 'kt_modal_master', 'size' => null])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true" aria-labelledby="{{ $id }}_title">
    <div @class(['modal-dialog', 'modal-dialog-scrollable', $size])>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="{{ $id }}_title" data-master-modal-title></h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup">
                    <i class="las la-times fs-2"></i>
                </button>
            </div>
            <form id="{{ $id }}_form" class="form d-flex flex-column overflow-hidden" autocomplete="off" novalidate>
                <div class="modal-body overflow-auto">
                    {{ $slot }}
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-master-submit>
                        <span class="indicator-label">Simpan</span>
                        <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
