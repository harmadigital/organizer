@extends('layouts.main_layout', ['title' => $meeting->title.' · '.$event->name, 'breadcrum' => ['Event', $event->code, 'Rapat & Notulen', 'Detail Rapat']])

@php($defaultTab = $meeting->hasMinutes() ? 'notulen' : 'agenda')
@php($displayTime = app(\App\Services\DisplayTime::class))

@section('content')
    <div class="ui-page" data-meeting-page
        data-show-url="{{ route('events.meetings.show', [$event, $meeting]) }}"
        data-update-url="{{ route('events.meetings.update', [$event, $meeting]) }}"
        data-delete-url="{{ route('events.meetings.delete', [$event, $meeting]) }}"
        data-meeting-title="{{ $meeting->title }}"
        data-attendance-url="{{ route('events.meetings.attendance', [$event, $meeting]) }}"
        data-editor-base="{{ asset('assets/plugins/custom/tinymce') }}">
        <div>
            <a href="{{ route('events.meetings', $event) }}" class="btn btn-sm btn-light" data-back-to-meetings>
                <i class="ki-duotone ki-arrow-left fs-4"><span class="path1"></span><span class="path2"></span></i> Kembali ke Rapat &amp; Notulen
            </a>
        </div>

        @include('events.partials.locked-notice')

        <div class="card ui-panel" data-meeting-detail>
            <div class="card-body pb-0">
                <div class="d-flex flex-wrap justify-content-between gap-4 mb-4">
                    <div>
                        <h2 class="fw-bold text-gray-900 mb-3">{{ $meeting->title }}</h2>
                        <div class="d-flex flex-wrap gap-5 text-gray-700 fs-6">
                            <span><span class="text-muted">Waktu:</span> {{ $meeting->meets_on->locale('id')->translatedFormat('l, j F Y') }} · {{ $meeting->timeLabel() }}</span>
                            <span><span class="text-muted">Tempat:</span> {{ $meeting->location ?? '-' }}</span>
                            <span><span class="text-muted">Untuk:</span> {{ $meeting->division?->name ?? 'Pengurus Inti / seluruh panitia' }}</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-start gap-2">
                        @if ($meeting->hasMinutes())
                            <a href="{{ route('events.meetings.pdf', [$event, $meeting]) }}" class="btn btn-sm btn-light-primary" data-minutes-pdf>
                                <i class="ki-duotone ki-file-down fs-4"><span class="path1"></span><span class="path2"></span></i> Unduh PDF
                            </a>
                        @endif
                    @if ($canManage)
                            <button type="button" class="btn btn-sm btn-primary" data-minutes-open>
                                <i class="ki-duotone ki-notepad-edit fs-4"><span class="path1"></span><span class="path2"></span></i> {{ $meeting->hasMinutes() ? 'Ubah Notulen' : 'Tulis Notulen' }}
                            </button>
                            <button type="button" class="btn btn-sm btn-light" data-meeting-edit>
                                <i class="ki-duotone ki-pencil fs-4"><span class="path1"></span><span class="path2"></span></i> Ubah
                            </button>
                            @unless ($meeting->hasMinutes())
                                <button type="button" class="btn btn-sm btn-light-danger" data-meeting-delete>Hapus</button>
                            @endunless
                    @endif
                    </div>
                </div>

                <ul class="nav nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mt-2" role="tablist" data-meeting-tabs>
                    @foreach ([
                        ['agenda', 'Agenda', count($meeting->agendaItems()) ?: null],
                        ['absensi', 'Absensi', count($presentIds).'/'.$committee->count()],
                        ['notulen', 'Notulen', null],
                        ['komentar', 'Komentar', $meeting->comments->count() ?: null],
                        ['riwayat', 'Riwayat', null],
                    ] as [$key, $label, $count])
                        <li class="nav-item" role="presentation">
                            <button type="button" @class(['nav-link text-active-primary pb-4', 'active' => $defaultTab === $key]) data-bs-toggle="tab" data-bs-target="#tab_{{ $key }}"
                                data-tab="{{ $key }}" role="tab" aria-controls="tab_{{ $key }}" aria-selected="{{ $defaultTab === $key ? 'true' : 'false' }}">
                                {{ $label }}
                                @if ($count)
                                    <span class="badge badge-light-primary ms-1" @if ($key === 'absensi') data-attendance-badge @endif>{{ $count }}</span>
                                @endif
                                @if ($key === 'notulen' && $meeting->hasMinutes())
                                    <span class="badge badge-light-success ms-1">Tersedia</span>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="tab-content">
            <div @class(['tab-pane fade', 'show active' => $defaultTab === 'agenda']) id="tab_agenda" role="tabpanel">
                <div class="card ui-panel">
                    <div class="card-body">
                        @if ($meeting->agendaItems() === [])
                            <div class="text-muted fs-6">Agenda belum diisi.</div>
                        @else
                            <ol class="mb-0 ps-5 fs-6 text-gray-800">
                                @foreach ($meeting->agendaItems() as $item)
                                    <li class="mb-2">{{ $item }}</li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>
            </div>

            @php($canMarkAttendance = $canManage && $attendanceState === 'open')
            @php($presentCount = count($presentIds))
            @php($committeeCount = $committee->count())
            <div @class(['tab-pane fade', 'show active' => $defaultTab === 'absensi']) id="tab_absensi" role="tabpanel">
                <div class="card ui-panel" data-attendance @if ($canMarkAttendance) data-attendance-editable @endif>
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center gap-5 mb-5">
                            <div class="flex-grow-1" style="min-width:240px">
                                <div class="d-flex align-items-baseline gap-2 mb-2">
                                    <span class="fs-2x fw-bold text-gray-900" data-attendance-present>{{ $presentCount }}</span>
                                    <span class="text-gray-600 fs-6">dari <span data-attendance-total>{{ $committeeCount }}</span> panitia hadir</span>
                                </div>
                                <div class="progress h-8px bg-light">
                                    <div class="progress-bar bg-success" data-attendance-bar style="width: {{ $committeeCount ? round($presentCount * 100 / $committeeCount) : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="position-relative" style="width:240px;max-width:100%">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-4"><span class="path1"></span><span class="path2"></span></i>
                                <input type="text" class="form-control form-control-solid ps-12" placeholder="Cari nama" data-attendance-search aria-label="Cari nama panitia">
                            </div>
                        </div>

                        @if ($attendanceState === 'locked')
                            <div class="notice d-flex align-items-center bg-light rounded border border-dashed border-gray-400 p-4 mb-5" data-attendance-locked>
                                <i class="ki-duotone ki-lock-2 fs-2x text-gray-600 me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                <div class="fs-6 text-gray-700"><strong>Absensi dikunci.</strong> Rapat selesai {{ $attendanceClosesAt->locale('id')->translatedFormat('j F Y, H.i') }}, sehingga daftar hadir tidak dapat diubah lagi.</div>
                            </div>
                        @elseif ($attendanceState === 'not_open')
                            <div class="notice d-flex align-items-center bg-light-primary rounded border border-dashed border-primary p-4 mb-5">
                                <i class="ki-duotone ki-time fs-2x text-primary me-3"><span class="path1"></span><span class="path2"></span></i>
                                <div class="fs-6 text-gray-700">Absensi dibuka pada hari rapat, {{ $meeting->meets_on->locale('id')->translatedFormat('l, j F Y') }}.</div>
                            </div>
                        @elseif ($canMarkAttendance)
                            <div class="fs-7 text-muted mb-4">Tekan <strong>Hadir</strong> pada nama panitia yang datang. Salah tekan? Tekan <strong>Batal</strong> di kolom Hadir. Yang tidak ditandai dianggap tidak hadir. Absensi terkunci otomatis saat rapat selesai ({{ $attendanceClosesAt->locale('id')->translatedFormat('H.i') }}).</div>
                        @endif

                        <div class="ui-attendance">
                            @foreach ([['absent', 'Tidak hadir', 'danger', 'Semua panitia sudah ditandai hadir.'], ['present', 'Hadir', 'success', $canMarkAttendance ? 'Belum ada yang ditandai hadir. Tekan "Hadir" pada nama di kolom kiri.' : 'Tidak ada yang ditandai hadir.']] as [$column, $label, $tone, $emptyText])
                                <div class="ui-attendance-column ui-attendance-column--{{ $column }}" data-attendance-column="{{ $column }}">
                                    <div class="ui-attendance-header">
                                        <span class="bullet bullet-dot bg-{{ $tone }} me-2 h-8px w-8px"></span>
                                        <span class="fw-bold text-gray-800">{{ $label }}</span>
                                        <span class="badge badge-light-{{ $tone }} ms-2" data-attendance-count>{{ $column === 'present' ? $presentCount : $committeeCount - $presentCount }}</span>
                                    </div>
                                    <div class="ui-attendance-list" data-attendance-list>
                                        @foreach ($committee as $membership)
                                            @php($isPresent = in_array($membership->id, $presentIds, true))
                                            @if (($column === 'present') === $isPresent)
                                                <div class="ui-attendance-person" data-attendance-person="{{ $membership->id }}" data-name="{{ mb_strtolower($membership->user->name) }}">
                                                    <span class="ui-attendance-avatar ui-attendance-avatar--{{ crc32($membership->user->name) % 5 }}">{{ mb_strtoupper(mb_substr($membership->user->name, 0, 1)) }}</span>
                                                    <span class="flex-grow-1 min-w-0">
                                                        <span class="d-block fw-semibold text-gray-800 text-truncate" data-attendance-name>{{ $membership->user->name }}</span>
                                                        <span class="d-block text-muted fs-8 text-truncate">{{ $membership->activeAssignments->map(fn ($assignment) => $assignment->committeeRole->name.($assignment->division ? ' · '.$assignment->division->name : ''))->implode(', ') ?: 'Panitia' }}</span>
                                                    </span>
                                                    @if ($canMarkAttendance)
                                                        <button type="button" class="btn btn-sm ui-attendance-toggle" data-attendance-toggle aria-label="{{ $isPresent ? 'Batalkan hadir' : 'Tandai hadir' }}: {{ $membership->user->name }}">
                                                            <span data-toggle-label>{{ $isPresent ? 'Batal' : 'Hadir' }}</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                        <div class="ui-attendance-empty" data-attendance-empty>{{ $emptyText }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div @class(['tab-pane fade', 'show active' => $defaultTab === 'notulen']) id="tab_notulen" role="tabpanel">
                @if (! $meeting->hasMinutes())
                    <div class="ui-empty-state bg-white">
                        <div class="fw-semibold text-gray-800 fs-5">Notulen belum ditulis</div>
                        <div class="text-muted fs-7 mt-1">{{ $canManage ? 'Tekan "Tulis Notulen" setelah rapat. Begitu disimpan, semua panitia dapat membacanya.' : 'Notulen akan muncul di sini setelah ditulis.' }}</div>
                    </div>
                @else
                    <div class="ui-detail-stack">
                        <div class="card ui-panel">
                            <div class="card-header">
                                <div class="card-title flex-column align-items-start">
                                    <h3 class="mb-1">Notulen</h3>
                                    <span class="ui-panel-description mt-0">Ditulis {{ $meeting->minutesAuthor?->name ?? '-' }} · {{ $displayTime->format($meeting->minutes_updated_at) }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-2">
                                {{-- Sanitized by RichText: only formatting elements and safe inline styles remain. --}}
                                <div class="ui-rich-text" data-minutes-text>{!! $minutesHtml !!}</div>
                                <div class="separator my-6"></div>
                                <div class="ui-info-label mb-2">Yang hadir ({{ $meeting->attendees->count() }}) · lihat tab Absensi</div>
                                <div class="text-gray-700 fs-6">{{ $meeting->attendees->map(fn ($attendee) => $attendee->user->name)->sort()->implode(', ') ?: 'Tidak dicatat.' }}</div>
                            </div>
                        </div>

                        <div class="card ui-panel">
                            <div class="card-header"><div class="card-title"><h3>Keputusan</h3></div></div>
                            <div class="card-body pt-2">
                                @if ($meeting->decisions->isEmpty())
                                    <div class="text-muted fs-6">Tidak ada keputusan yang dicatat.</div>
                                @else
                                    <ol class="mb-0 ps-5 fs-6 text-gray-800" data-decisions>
                                        @foreach ($meeting->decisions as $decision)
                                            <li class="mb-2 ui-pre-line">{{ $decision->body }}</li>
                                        @endforeach
                                    </ol>
                                @endif
                            </div>
                        </div>

                        <div class="card ui-panel">
                            <div class="card-header">
                                <div class="card-title flex-column align-items-start">
                                    <h3 class="mb-1">Tindak Lanjut</h3>
                                    <span class="ui-panel-description mt-0">Setiap tindak lanjut otomatis menjadi tugas di Program &amp; Tugas.</span>
                                </div>
                            </div>
                            <div class="card-body pt-2">
                                @forelse ($meeting->actionItems as $item)
                                    <div class="ui-check-row" data-action-item="{{ $item->id }}">
                                        @if ($item->task)
                                            <span class="badge badge-light-{{ $item->task->status->tone() }}">{{ $item->task->status->label() }}</span>
                                            <a href="{{ route('events.tasks.detail', [$event, $item->task]) }}" class="ui-task-title flex-grow-1">{{ $item->title }}</a>
                                        @else
                                            <span class="ui-task-title flex-grow-1">{{ $item->title }}</span>
                                        @endif
                                        <span class="text-muted fs-7">{{ $item->owner?->user->name ?? 'Belum ada penanggung jawab' }}</span>
                                        @if ($item->due_on)
                                            <span class="text-muted fs-7">{{ $item->due_on->locale('id')->translatedFormat('j M Y') }}</span>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-muted fs-6">Tidak ada tindak lanjut.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div @class(['tab-pane fade', 'show active' => $defaultTab === 'komentar']) id="tab_komentar" role="tabpanel">
                <div class="card ui-panel" data-comments>
                    <div class="card-header">
                        <div class="card-title flex-column align-items-start">
                            <h3 class="mb-1">Komentar <span class="text-muted fs-6 fw-semibold">({{ $meeting->comments->count() }})</span></h3>
                            <span class="ui-panel-description mt-0">Semua panitia dapat memberi masukan atau pertanyaan tentang rapat ini.</span>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        @forelse ($meeting->comments as $comment)
                            <div class="ui-comment" data-comment-row="{{ $comment->id }}">
                                <div class="symbol symbol-35px symbol-circle flex-shrink-0">
                                    <span class="symbol-label bg-light-primary text-primary fw-bold">{{ mb_strtoupper(mb_substr($comment->author?->name ?? '?', 0, 1)) }}</span>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                                        <span class="fw-semibold text-gray-800">{{ $comment->author?->name ?? 'Pengguna dihapus' }}</span>
                                        <span class="text-muted fs-8">{{ $displayTime->format($comment->created_at) }}</span>
                                    </div>
                                    <div class="text-gray-700 fs-6 ui-pre-line mt-1">{{ $comment->body }}</div>
                                </div>
                                @if ($event->isEditable() && ($canManage || $comment->user_id === auth()->id()))
                                    <button type="button" class="btn btn-sm btn-icon btn-light flex-shrink-0" data-comment-remove="{{ route('events.meetings.comments.delete', [$event, $meeting, $comment]) }}"
                                        title="Hapus komentar" aria-label="Hapus komentar dari {{ $comment->author?->name }}">
                                        <i class="las la-times fs-4"></i>
                                    </button>
                                @endif
                            </div>
                        @empty
                            <div class="text-muted fs-6 mb-2">Belum ada komentar.</div>
                        @endforelse

                        @if ($canComment)
                            <form class="mt-5" data-comment-form data-url="{{ route('events.meetings.comments.insert', [$event, $meeting]) }}" novalidate>
                                <div class="fv-row mb-3">
                                    <label class="visually-hidden" for="comment_body">Komentar</label>
                                    <textarea name="body" id="comment_body" class="form-control form-control-solid" rows="2" maxlength="2000" placeholder="Tulis komentar..."></textarea>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-sm btn-primary" data-comment-submit>
                                        <span class="indicator-label">Kirim Komentar</span>
                                        <span class="indicator-progress">Mengirim...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div @class(['tab-pane fade', 'show active' => $defaultTab === 'riwayat']) id="tab_riwayat" role="tabpanel">
                <div class="card ui-panel">
                    <div class="card-body">
                        @if ($meeting->minutesVersions->isEmpty())
                            <div class="text-muted fs-6">Notulen belum pernah disimpan.</div>
                        @else
                            <div class="ui-timeline">
                                @foreach ($meeting->minutesVersions as $version)
                                    <div class="ui-timeline-item">
                                        <div class="fw-semibold text-gray-800 fs-6">{{ $version->version_no === 1 ? 'Notulen ditulis' : 'Notulen diperbarui (versi '.$version->version_no.')' }}</div>
                                        <div class="text-muted fs-7">{{ $displayTime->format($version->created_at) }} · {{ $version->author?->name ?? 'Sistem' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canManage)
        @include('events.partials.meeting-form-modal')
        @include('events.partials.minutes-form-modal')
    @endif
@endsection

@section('script')
    @if ($canManage)
        <script src="{{ asset('assets/plugins/custom/tinymce/tinymce.bundle.js') }}"></script>
    @endif
    <script src="{{ asset('assets/js/events/meeting-form.js') }}"></script>
    <script src="{{ asset('assets/js/events/meeting.js') }}"></script>
@endsection
