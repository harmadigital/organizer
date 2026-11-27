@props(['hint' => 'Data nonaktif tidak muncul sebagai pilihan saat membuat data baru.'])

<div class="fv-row mb-2">
    <label class="form-check form-switch form-check-custom form-check-solid">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
        <span class="form-check-label fw-semibold text-gray-800">Aktif</span>
    </label>
    <div class="fs-7 text-muted mt-2">{{ $hint }}</div>
</div>
