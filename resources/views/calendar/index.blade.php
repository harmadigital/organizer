@extends('layouts.main_layout', ['title' => 'Kalender Saya', 'breadcrum' => ['Event', 'Kalender Saya']])

@section('content')
    <div class="ui-page" data-my-calendar-page
        data-feed-url="{{ route('calendar.feed') }}"
        data-today="{{ $today }}"
        data-has-events="{{ $hasEvents ? '1' : '0' }}">
        <x-ui.page-header title="Kalender Saya" subtitle="Rapat, kegiatan, dan tenggat tugas Anda dari semua event yang Anda ikuti" />

        <div class="card ui-panel">
            <div class="card-body">
                <div class="ui-calendar-filters">
                    @if ($canSeeOrganization)
                        <div style="min-width:240px;max-width:100%">
                            <select class="form-select form-select-sm form-select-solid" data-control="select2" data-hide-search="true" data-my-calendar-scope aria-label="Tampilkan">
                                <option value="mine" selected>Event yang saya ikuti</option>
                                <option value="organization">Semua event organisasi</option>
                            </select>
                        </div>
                    @endif
                    @include('events.partials.calendar-legend')
                </div>

                <div @class(['ui-calendar', 'd-none' => ! $hasEvents]) data-calendar></div>

                <div @class(['text-muted fs-7 mt-4', 'd-none' => ! $hasEvents]) data-my-calendar-hint>Untuk menambah kegiatan, buka event lalu tab Kalender.</div>
                <div class="text-muted fs-7 mt-2 d-none" data-calendar-empty>Belum ada jadwal pada periode ini.</div>

                <div @class(['ui-empty-state', 'd-none' => $hasEvents]) data-my-calendar-no-events>
                    <i class="ki-duotone ki-calendar-tick fs-3x text-muted mb-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i>
                    <div class="fw-semibold text-gray-800 fs-5">Anda belum menjadi panitia aktif di event mana pun.</div>
                    <div class="text-muted fs-7 mt-1 mb-4">Jadwal akan muncul di sini setelah Anda menerima undangan panitia.</div>
                    <a href="{{ route('events.index') }}" class="btn btn-sm btn-light-primary">Lihat daftar event</a>
                </div>
            </div>
        </div>
    </div>

    @include('events.partials.calendar-entry-detail-modal', ['showEventLink' => true])
@endsection

@section('script')
    <script src="{{ asset('assets/js/organizer/calendar.js') }}"></script>
    <script src="{{ asset('assets/js/my-calendar.js') }}"></script>
@endsection
