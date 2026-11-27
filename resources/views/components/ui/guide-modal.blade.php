@props(['id' => 'kt_modal_guide', 'title' => 'Panduan'])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true" aria-labelledby="{{ $id }}_title">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="{{ $id }}_title">{{ $title }}</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup">
                    <i class="las la-times fs-2"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5 mb-7">
                    <i class="ki-duotone ki-information-2 fs-2tx text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <div class="fw-semibold text-gray-700 fs-6">{{ $slot }}</div>
                </div>
                @isset($steps)
                    <h5 class="fw-bold mb-3">Alur Kerja</h5>
                    <ol class="text-gray-700 fs-6 mb-7">{{ $steps }}</ol>
                @endisset
                @isset($notes)
                    <h5 class="fw-bold mb-3">Catatan Penting</h5>
                    <ul class="text-gray-700 fs-6 mb-0">{{ $notes }}</ul>
                @endisset
            </div>
            <div class="modal-footer flex-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
