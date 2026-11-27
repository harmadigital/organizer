@extends('layouts.main_layout', ['title' => 'Kalender · '.$event->name, 'breadcrum' => ['Event', $event->code, 'Kalender']])

@section('content')
    <div class="ui-page" data-calendar-page
        data-feed-url="{{ route('events.calendar.feed', $event) }}"
        data-insert-url="{{ route('events.calendar.insert', $event) }}"
        data-today="{{ $today }}">
        @include('events.partials.header')

        <div class="card ui-panel">
            <div class="card-header">
                <div class="card-title flex-column align-items-start">
                    <h3 class="mb-1">Kalender</h3>
                    <span class="ui-panel-description mt-0">Rapat, tenggat tugas, hari pelaksanaan, dan kegiatan panitia. Rapat dan tugas muncul sendiri dari Rapat &amp; Notulen dan Program &amp; Tugas.</span>
                </div>
                @if ($canAdd)
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-primary" data-entry-add>
                            <i class="ki-duotone ki-plus fs-3"></i> Tambah Kegiatan
                        </button>
                    </div>
                @endif
            </div>
            <div class="card-body pt-5">
                <div class="ui-calendar-filters">
                    <div style="min-width:220px;max-width:100%">
                        <select class="form-select form-select-sm form-select-solid" data-control="select2" data-allow-clear="true" data-placeholder="Semua seksi" data-calendar-division aria-label="Saring seksi">
                            <option value=""></option>
                            <option value="none">Pengurus Inti</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @include('events.partials.calendar-legend')
                </div>

                <div class="ui-calendar" data-calendar></div>

                <div class="text-muted fs-7 mt-4 d-none" data-calendar-empty>
                    Belum ada jadwal pada periode ini.
                    @if ($canAdd)
                        Rapat dan tenggat tugas muncul sendiri; kegiatan lain seperti survei lokasi atau latihan bisa ditambahkan dengan tombol Tambah Kegiatan.
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($canAdd)
        @include('events.partials.calendar-entry-form-modal')
    @endif

    @include('events.partials.calendar-entry-detail-modal', ['showEventLink' => false])

    @include('events.partials.calendar-guide')
@endsection

@section('script')
    <script src="{{ asset('assets/js/organizer/calendar.js') }}"></script>
    <script src="{{ asset('assets/js/events/calendar.js') }}"></script>
@endsection
