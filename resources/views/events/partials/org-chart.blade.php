{{-- Bagan organisasi panitia: pengawas, ketua, pengurus inti, lalu seksi dan sub-seksinya. --}}
@php($eventRoles = $roles->where('scope', \App\Enums\CommitteeRoleScope::Event))
@php($holdersOf = fn ($role) => $coreAssignments[$role->id] ?? collect())
@php($chairRole = $eventRoles->first(fn ($role) => $role->kind === \App\Enums\CommitteeRoleKind::Chair))
@php($supervisorRoles = $eventRoles->filter(fn ($role) => $role->kind === \App\Enums\CommitteeRoleKind::Supervisor))
@php($staffRoles = $eventRoles->reject(fn ($role) => in_array($role->kind, [\App\Enums\CommitteeRoleKind::Chair, \App\Enums\CommitteeRoleKind::Supervisor], true))
    ->filter(fn ($role) => $holdersOf($role)->isNotEmpty() || ($rules[$role->id]->is_required ?? false)))
@php($rootDivisions = $divisionsByParent[''] ?? collect())

<div class="ui-org-scroll">
    <div class="ui-org-tree">
        @if ($supervisorRoles->isNotEmpty())
            <div class="ui-org-supervision">
                @foreach ($supervisorRoles as $role)
                    <div @class(['ui-org-node ui-org-node--side', 'ui-org-node--empty' => $holdersOf($role)->isEmpty()])>
                        <div class="ui-org-role">{{ $role->name }}</div>
                        @forelse ($holdersOf($role) as $assignment)
                            @include('events.partials.org-person')
                        @empty
                            <span class="d-block ui-org-name text-muted fw-normal">Belum ditetapkan</span>
                        @endforelse
                        <div class="ui-org-meta">Mengawasi seluruh panitia</div>
                    </div>
                @endforeach
            </div>
            <div class="ui-org-supervision-line"></div>
        @endif

        <ul>
            <li>
                <div @class(['ui-org-node ui-org-node--lead', 'ui-org-node--empty' => $chairRole === null || $holdersOf($chairRole)->isEmpty()])>
                    <div class="ui-org-role">{{ $chairRole?->name ?? 'Ketua' }}</div>
                    @forelse ($chairRole ? $holdersOf($chairRole) : collect() as $assignment)
                        @include('events.partials.org-person')
                    @empty
                        <span class="d-block ui-org-name text-muted fw-normal">Belum ditetapkan</span>
                    @endforelse
                </div>
                @if ($staffRoles->isNotEmpty() || $rootDivisions->isNotEmpty())
                    <ul>
                        @foreach ($staffRoles as $role)
                            <li>
                                <div @class(['ui-org-node', 'ui-org-node--empty' => $holdersOf($role)->isEmpty()])>
                                    <div class="ui-org-role">{{ $role->name }}</div>
                                    @forelse ($holdersOf($role) as $assignment)
                                        @include('events.partials.org-person')
                                    @empty
                                        <span class="d-block ui-org-name text-muted fw-normal">Belum ditetapkan</span>
                                    @endforelse
                                </div>
                            </li>
                        @endforeach
                        @if ($rootDivisions->isNotEmpty())
                            <li>
                                <div class="ui-org-node ui-org-node--group">
                                    <div class="ui-org-role">Seksi</div>
                                    <div class="ui-org-meta mt-1">{{ $divisionTree === [] ? 0 : count($divisionTree) }} seksi</div>
                                </div>
                                <ul>
                                    @foreach ($rootDivisions as $division)
                                        @include('events.partials.org-division-node')
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    </ul>
                @endif
            </li>
        </ul>
    </div>
</div>
