@if ($canManageDivisions)
    <button type="button" class="btn btn-light" data-division-templates>
        <i class="ki-duotone ki-copy fs-3"></i> Seksi dari Template
    </button>
    <button type="button" class="btn btn-light-primary" data-division-add>
        <i class="ki-duotone ki-plus fs-3"><span class="path1"></span><span class="path2"></span></i> Tambah Seksi
    </button>
@endif
@if ($event->isEditable() && $assignableRoles !== [])
    <button type="button" class="btn btn-primary" data-assignment-add>
        <i class="ki-duotone ki-plus fs-2"><span class="path1"></span><span class="path2"></span></i> Tambah Panitia
    </button>
@endif
