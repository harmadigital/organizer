@extends('layouts.main_layout', ['title' => 'Rapat & Notulen · '.$event->name, 'breadcrum' => ['Event', $event->code, 'Rapat & Notulen']])

@php($dayLabel = fn ($date) => $date->locale('id')->translatedFormat('l, j F Y'))

@section('content')
    <div class="ui-page" data-meetings-page data-insert-url="{{ route('events.meetings.insert', $event) }}">
        @include('events.partials.header')

        <div class="card ui-panel">
            <div class="card-header">
                <div class="card-title flex-column align-items-start">
                    <h3 class="mb-1">Rapat Akan Datang</h3>
                    <span class="ui-panel-description mt-0">Semua panitia dapat melihat jadwal dan agenda rapat. Tidak perlu undangan.</span>
                </div>
                @if ($canSchedule)
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-primary" data-meeting-add>
                            <i class="ki-duotone ki-plus fs-3"></i> Rapat
                        </button>
                    </div>
                @endif
            </div>
            <div class="card-body pt-2">
                @forelse ($upcoming as $meeting)
                    <a href="{{ route('events.meetings.detail', [$event, $meeting]) }}" class="ui-meeting-row" data-meeting-row="{{ $meeting->id }}">
                        <div class="ui-meeting-date">
                            <span class="ui-meeting-day">{{ $meeting->meets_on->format('j') }}</span>
                            <span class="ui-meeting-month">{{ $meeting->meets_on->locale('id')->translatedFormat('M') }}</span>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="ui-task-title">{{ $meeting->title }}</span>
                                @if ($meeting->meets_on->isSameDay($today))
                                    <span class="badge badge-light-success">Hari ini</span>
                                @endif
                            </div>
                            <div class="ui-task-meta">
                                <span>{{ $dayLabel($meeting->meets_on) }} · {{ $meeting->timeLabel() }}</span>
                                @if ($meeting->location)
                                    <span>{{ $meeting->location }}</span>
                                @endif
                                <span>{{ $meeting->division?->name ?? 'Pengurus Inti' }}</span>
                            </div>
                            @if ($meeting->agendaItems() !== [])
                                <div class="text-gray-700 fs-7 mt-2 text-truncate">Agenda: {{ implode(' · ', $meeting->agendaItems()) }}</div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="ui-empty-state">
                        <div class="fw-semibold text-gray-800 fs-5">Belum ada rapat yang dijadwalkan</div>
                        <div class="text-muted fs-7 mt-1">Rapat yang dibuat sekretaris atau koordinator seksi akan muncul di sini.</div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="card ui-panel">
            <div class="card-header">
                <div class="card-title flex-column align-items-start">
                    <h3 class="mb-1">Rapat Sebelumnya</h3>
                    <span class="ui-panel-description mt-0">Buka rapat untuk membaca notulen, keputusan, dan tindak lanjutnya.</span>
                </div>
            </div>
            <div class="card-body pt-2">
                @forelse ($past as $meeting)
                    <a href="{{ route('events.meetings.detail', [$event, $meeting]) }}" class="ui-meeting-row" data-meeting-row="{{ $meeting->id }}">
                        <div class="ui-meeting-date ui-meeting-date--past">
                            <span class="ui-meeting-day">{{ $meeting->meets_on->format('j') }}</span>
                            <span class="ui-meeting-month">{{ $meeting->meets_on->locale('id')->translatedFormat('M') }}</span>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="ui-task-title">{{ $meeting->title }}</span>
                                @if ($meeting->hasMinutes())
                                    <span class="badge badge-light-success">Notulen tersedia</span>
                                @else
                                    <span class="badge badge-light-warning">Belum ada notulen</span>
                                @endif
                            </div>
                            <div class="ui-task-meta">
                                <span>{{ $dayLabel($meeting->meets_on) }}</span>
                                <span>{{ $meeting->division?->name ?? 'Pengurus Inti' }}</span>
                                @if ($meeting->hasMinutes())
                                    <span>{{ $meeting->decisions_count }} keputusan · {{ $meeting->action_items_count }} tindak lanjut</span>
                                @endif
                                @if ($meeting->comments_count)
                                    <span>{{ $meeting->comments_count }} komentar</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-muted fs-6">Belum ada rapat yang sudah berlangsung.</div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($canSchedule)
        @include('events.partials.meeting-form-modal')
    @endif

    @include('events.partials.meeting-guide')
@endsection

@section('script')
    <script src="{{ asset('assets/js/events/meeting-form.js') }}"></script>
    <script src="{{ asset('assets/js/events/meetings.js') }}"></script>
@endsection
