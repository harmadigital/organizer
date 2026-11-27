{{-- Nama satu orang di bagan, dengan keterangan pendamping atau menunggu konfirmasi. --}}
@php($isSingleton = (bool) ($rules[$assignment->committee_role_id]->is_singleton ?? false))
<span @class(['d-block', 'ui-org-name', 'ui-org-name--secondary' => $isSingleton && ! $assignment->is_primary])>
    {{ $assignment->membership->user->name }}@if ($isSingleton && ! $assignment->is_primary) <span class="text-muted fw-normal">(pendamping)</span>@endif
    @if ($assignment->membership->status === \App\Enums\MembershipStatus::Invited)
        <span class="badge badge-light-warning fs-9 ms-1">menunggu</span>
    @endif
</span>
