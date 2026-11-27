{{-- Satu seksi di bagan beserta sub-seksinya. --}}
@php($people = $divisionAssignments[$division->id] ?? collect())
@php($coordinators = $people->filter(fn ($assignment) => $assignment->committeeRole->kind === \App\Enums\CommitteeRoleKind::DivisionCoordinator))
@php($others = $people->reject(fn ($assignment) => $assignment->committeeRole->kind === \App\Enums\CommitteeRoleKind::DivisionCoordinator))
@php($children = $divisionsByParent[(string) $division->id] ?? collect())
<li>
    <div @class(['ui-org-node', 'ui-org-node--empty' => $people->isEmpty()])>
        <div class="ui-org-role">{{ $division->name }}</div>
        @forelse ($coordinators as $assignment)
            @include('events.partials.org-person')
        @empty
            <span class="d-block ui-org-name text-muted fw-normal">Koordinator belum ada</span>
        @endforelse
        @if ($others->isNotEmpty())
            <ul class="ui-org-list">
                @foreach ($others->take(6) as $assignment)
                    <li>{{ $assignment->membership->user->name }}@if ($assignment->membership->status === \App\Enums\MembershipStatus::Invited) <span class="text-warning">(menunggu)</span>@endif</li>
                @endforeach
            </ul>
            @if ($others->count() > 6)
                <div class="ui-org-meta">+{{ $others->count() - 6 }} anggota lainnya</div>
            @endif
        @endif
        <div class="ui-org-meta">{{ $people->count() }} orang{{ $children->isNotEmpty() ? ' · '.$children->count().' sub-seksi' : '' }}</div>
    </div>
    @if ($children->isNotEmpty())
        <ul>
            @foreach ($children as $division)
                @include('events.partials.org-division-node')
            @endforeach
        </ul>
    @endif
</li>
