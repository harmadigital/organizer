@props(['searchPlaceholder' => 'Cari...', 'addLabel' => null, 'canCreate' => false, 'statusFilter' => true])

<div class="ui-toolbar">
    <div class="ui-toolbar-filters">
        <div class="ui-search-box">
            <i class="ki-duotone ki-magnifier fs-3 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
            <input type="text" class="form-control form-control-solid" placeholder="{{ $searchPlaceholder }}"
                aria-label="{{ $searchPlaceholder }}" data-master-search>
        </div>
        {{ $filters ?? '' }}
        @if ($statusFilter)
            <div class="w-100 w-md-200px">
                <select class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                    data-placeholder="Semua Status" data-allow-clear="true" aria-label="Saring menurut status" data-master-filter="status">
                    <option></option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>
        @endif
    </div>
    @if ($addLabel && $canCreate)
        <div class="ui-toolbar-actions">
            <button type="button" class="btn btn-primary" data-master-add>
                <i class="ki-duotone ki-plus fs-2"><span class="path1"></span><span class="path2"></span></i> {{ $addLabel }}
            </button>
        </div>
    @endif
</div>
