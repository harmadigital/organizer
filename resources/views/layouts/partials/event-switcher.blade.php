@if (isset($switcherEvents) && ($switcherEvents->isNotEmpty() || isset($currentEvent)))
    <div class="app-navbar-item ms-1 ms-md-4">
        <button type="button" class="btn btn-sm btn-light d-flex align-items-center gap-2 mw-250px"
            data-kt-menu-trigger="{default: 'click'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end"
            aria-label="Pindah event" data-event-switcher>
            <i class="ki-duotone ki-calendar fs-3"><span class="path1"></span><span class="path2"></span></i>
            <span class="text-truncate d-none d-md-inline">{{ isset($currentEvent) ? $currentEvent->name : 'Pilih Event' }}</span>
            <i class="ki-duotone ki-down fs-5"><span class="path1"></span><span class="path2"></span></i>
        </button>
        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg fw-semibold py-3 fs-6 w-300px" data-kt-menu="true">
            <div class="menu-item px-3">
                <div class="menu-content px-3 py-2 ui-info-label">Event Saya</div>
            </div>
            @forelse ($switcherEvents as $switcherEvent)
                <div class="menu-item px-3">
                    <a href="{{ route('events.overview', $switcherEvent) }}"
                        @class(['menu-link px-3 d-flex flex-column align-items-start', 'active' => isset($currentEvent) && $currentEvent->is($switcherEvent)])>
                        <span class="text-gray-800 text-truncate mw-250px">{{ $switcherEvent->name }}</span>
                        <span class="text-muted fs-8">{{ $switcherEvent->code }} · {{ $switcherEvent->status->label() }}</span>
                    </a>
                </div>
            @empty
                <div class="menu-item px-3">
                    <div class="menu-content px-3 text-muted fs-7">Anda belum tergabung dalam kepanitiaan.</div>
                </div>
            @endforelse
            <div class="separator my-2"></div>
            <div class="menu-item px-3">
                <a href="{{ route('events.index') }}" class="menu-link px-3">Lihat semua event</a>
            </div>
        </div>
    </div>
@endif
