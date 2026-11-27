{{-- Satu seksi di tree view: keterangan, panitia per jabatan, lalu sub-seksinya. --}}
@php($people = $divisionAssignments[$division->id] ?? collect())
@php($children = $divisionsByParent[(string) $division->id] ?? collect())
<details open data-division-block="{{ $division->id }}">
    <summary>
        <span class="ui-tree-caret"></span>
        <span class="ui-tree-label">{{ $division->name }}</span>
        <span class="ui-tree-count">{{ $people->count() }} orang{{ $children->isNotEmpty() ? ' · '.$children->count().' sub-seksi' : '' }}</span>
        @if ($canManageDivisions)
            <span class="ui-committee-actions ms-auto">
                <button type="button" class="btn btn-sm btn-icon btn-light" data-division-edit="{{ route('events.divisions.show', [$event, $division]) }}"
                    data-update-url="{{ route('events.divisions.update', [$event, $division]) }}" title="Ubah seksi" aria-label="Ubah seksi {{ $division->name }}">
                    <i class="las la-edit fs-2"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-division-delete="{{ route('events.divisions.delete', [$event, $division]) }}"
                    data-name="{{ $division->name }}" title="Hapus seksi" aria-label="Hapus seksi {{ $division->name }}">
                    <i class="las la-trash fs-2"></i>
                </button>
            </span>
        @endif
    </summary>
    <div class="ui-tree-children">
        @if ($division->description || $division->responsibilities)
            <div class="ui-tree-leaf">
                @if ($division->description)
                    <div class="ui-tree-note">{{ $division->description }}</div>
                @endif
                @if ($division->responsibilities)
                    <details class="mb-2">
                        <summary class="text-primary fs-7 py-1">Tanggung jawab</summary>
                        <div class="text-gray-700 fs-7 mt-1 mb-2 ui-pre-line">{{ $division->responsibilities }}</div>
                    </details>
                @endif
            </div>
        @endif

        @foreach ($people->groupBy('committee_role_id') as $roleAssignments)
            <details open>
                <summary>
                    <span class="ui-tree-caret"></span>
                    <span class="ui-tree-label ui-tree-label--role">{{ $roleAssignments->first()->committeeRole->name }}</span>
                    <span class="ui-tree-count">{{ $roleAssignments->count() }}</span>
                </summary>
                <div class="ui-tree-children">
                    @foreach ($roleAssignments as $assignment)
                        <div class="ui-tree-leaf">@include('events.partials.assignment-row')</div>
                    @endforeach
                </div>
            </details>
        @endforeach

        @if ($people->isEmpty())
            <div class="ui-tree-leaf"><div class="ui-tree-note">Belum ada panitia di seksi ini.</div></div>
        @endif

        @foreach ($children as $division)
            @include('events.partials.tree-division')
        @endforeach
    </div>
</details>
