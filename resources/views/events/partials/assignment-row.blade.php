@php($membership = $assignment->membership)
@php($isSingleton = (bool) ($rules[$assignment->committee_role_id]->is_singleton ?? false))
@php($canManageMembership = $membershipAuthority[$membership->id] ?? false)
@php($isSelf = $membership->user_id === auth()->id())
<div class="ui-committee-row" data-assignment-row="{{ $assignment->id }}">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-semibold text-gray-800 fs-6">{{ $membership->user->name }}</span>
            @if ($isSingleton)
                <span class="badge badge-light-{{ $assignment->is_primary ? 'primary' : 'secondary' }}">{{ $assignment->is_primary ? 'Utama' : 'Pendamping' }}</span>
            @endif
            @if ($membership->status === \App\Enums\MembershipStatus::Invited)
                <span class="badge badge-light-warning">Menunggu konfirmasi</span>
            @endif
        </div>
        <div class="text-muted fs-7">{{ $membership->user->unit?->name ?? 'Tanpa unit' }}</div>
        @if ($assignment->job_description)
            <div class="text-gray-700 fs-7 mt-1 ui-pre-line">{{ $assignment->job_description }}</div>
        @endif
    </div>
    @if (! $isSelf && ($assignment->can_manage || $canManageMembership))
        <div class="ui-committee-actions">
            @if ($membership->status === \App\Enums\MembershipStatus::Invited && $canManageMembership)
                <button type="button" class="btn btn-sm btn-icon btn-light-success" data-membership-confirm="{{ route('events.memberships.confirm', [$event, $membership]) }}"
                    data-person="{{ $membership->user->name }}" title="Konfirmasi sudah bersedia" aria-label="Konfirmasi {{ $membership->user->name }} sudah bersedia">
                    <i class="las la-user-check fs-2"></i>
                </button>
            @endif
            @if ($assignment->can_manage)
                <button type="button" class="btn btn-sm btn-icon btn-light" data-assignment-edit="{{ route('events.assignments.show', [$event, $assignment]) }}"
                    data-update-url="{{ route('events.assignments.update', [$event, $assignment]) }}" title="Ubah uraian tugas" aria-label="Ubah uraian tugas {{ $membership->user->name }}">
                    <i class="las la-edit fs-2"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-assignment-end="{{ route('events.assignments.end', [$event, $assignment]) }}"
                    data-person="{{ $membership->user->name }}" data-role="{{ $assignment->committeeRole->name }}" title="Akhiri jabatan" aria-label="Akhiri jabatan {{ $membership->user->name }}">
                    <i class="las la-user-minus fs-2"></i>
                </button>
            @endif
            @if ($canManageMembership)
                <button type="button" class="btn btn-sm btn-icon btn-light-danger" data-membership-revoke="{{ route('events.memberships.revoke', [$event, $membership]) }}"
                    data-person="{{ $membership->user->name }}" title="Keluarkan dari panitia" aria-label="Keluarkan {{ $membership->user->name }} dari panitia">
                    <i class="las la-user-times fs-2"></i>
                </button>
            @endif
        </div>
    @endif
</div>
