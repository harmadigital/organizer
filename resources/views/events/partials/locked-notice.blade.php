{{-- Pemberitahuan bahwa event sudah tidak dapat diubah (selesai, batal, atau arsip). --}}
@unless ($event->isEditable())
    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-5">
        <i class="ki-duotone ki-information-5 fs-2tx text-warning me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
        <div class="fw-semibold text-gray-700 fs-6">Event ini berstatus <strong>{{ $event->status->label() }}</strong>, sehingga data dan susunan panitianya tidak dapat diubah.</div>
    </div>
@endunless
