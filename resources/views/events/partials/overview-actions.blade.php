@can('update', $event)
    <button type="button" class="btn btn-light-primary" data-event-edit="{{ route('events.show', $event) }}" data-update-url="{{ route('events.update', $event) }}">
        <i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i> Ubah
    </button>
@endcan
@can('changePhase', $event)
    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#kt_modal_event_phase">Ubah Fase</button>
@endcan
@can('changeStatus', $event)
    @foreach ($transitions as $transition)
        <button type="button" @class(['btn', 'btn-primary' => $transition === \App\Enums\EventStatus::Active, 'btn-light-danger' => $transition === \App\Enums\EventStatus::Cancelled, 'btn-light' => ! in_array($transition, [\App\Enums\EventStatus::Active, \App\Enums\EventStatus::Cancelled], true)])
            data-status-target="{{ $transition->value }}" data-status-label="{{ $transition->actionLabel() }}"
            data-requires-reason="{{ $event->status->requiresReasonToMoveTo($transition) ? '1' : '0' }}">
            {{ $transition->actionLabel() }}
        </button>
    @endforeach
@endcan
