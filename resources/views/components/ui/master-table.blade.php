@props(['columns', 'permission', 'statusColumn' => true])

<div class="ui-table-shell">
    <table id="kt_datatable_master" class="table align-middle table-row-dashed fs-6 gy-5"
        data-can-update="{{ auth()->user()->can($permission.'.update') ? '1' : '0' }}"
        data-can-delete="{{ auth()->user()->can($permission.'.delete') ? '1' : '0' }}">
        <thead>
            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                <th class="w-10px">#</th>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
                @if ($statusColumn)
                    <th>Status</th>
                @endif
                <th class="text-end min-w-100px">Aksi</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 fw-semibold"></tbody>
    </table>
</div>
