{{--
    Aksi di header Ringkasan event. Yang sering dipakai tampil sebagai tombol (Laporan Kondisi,
    Ubah, dan Aktifkan Event saat masih draf); sisanya ada di menu "Lainnya" agar header tetap lega.
--}}
@php($statusMenu = auth()->user()->can('changeStatus', $event) ? collect($transitions) : collect())
@php($activate = $statusMenu->first(fn ($transition) => $transition === \App\Enums\EventStatus::Active))
@php($menuTransitions = $statusMenu->reject(fn ($transition) => $transition === \App\Enums\EventStatus::Active)->values())
@php($canChangePhase = auth()->user()->can('changePhase', $event))

@can('viewSummary', $event)
    <button type="button" class="btn btn-primary" data-summary-open>
        <i class="ki-duotone ki-chart-simple fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i> Laporan Kondisi
    </button>
@endcan
@if ($activate)
    <button type="button" class="btn btn-primary" data-status-target="{{ $activate->value }}" data-status-label="{{ $activate->actionLabel() }}"
        data-requires-reason="{{ $event->status->requiresReasonToMoveTo($activate) ? '1' : '0' }}">
        {{ $activate->actionLabel() }}
    </button>
@endif
@can('update', $event)
    <button type="button" class="btn btn-light-primary" data-event-edit="{{ route('events.show', $event) }}" data-update-url="{{ route('events.update', $event) }}">
        <i class="ki-duotone ki-pencil fs-4"><span class="path1"></span><span class="path2"></span></i> Ubah
    </button>
@endcan
@if ($canChangePhase || $menuTransitions->isNotEmpty())
    <div class="dropdown" data-event-more>
        <button type="button" class="btn btn-light" data-bs-toggle="dropdown" aria-expanded="false">
            Lainnya <i class="ki-duotone ki-down fs-5 ms-1"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end ui-action-menu">
            @if ($canChangePhase)
                <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#kt_modal_event_phase">Ubah Fase</button></li>
            @endif
            @foreach ($menuTransitions as $transition)
                @if ($transition === \App\Enums\EventStatus::Cancelled && ($canChangePhase || $loop->index > 0))
                    <li><hr class="dropdown-divider"></li>
                @endif
                <li>
                    <button type="button" @class(['dropdown-item', 'text-danger' => $transition === \App\Enums\EventStatus::Cancelled])
                        data-status-target="{{ $transition->value }}" data-status-label="{{ $transition->actionLabel() }}"
                        data-requires-reason="{{ $event->status->requiresReasonToMoveTo($transition) ? '1' : '0' }}">
                        {{ $transition->actionLabel() }}
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
@endif
