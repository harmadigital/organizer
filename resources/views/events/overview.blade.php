@extends('layouts.main_layout', ['title' => $event->name, 'breadcrum' => ['Event', $event->code]])

@section('content')
    <div class="ui-page" data-event-page data-status-url="{{ route('events.status', $event) }}" data-phase-url="{{ route('events.phase', $event) }}">
        @include('events.partials.header', ['actionsView' => 'events.partials.overview-actions'])

        <div class="row g-5">
            @foreach ([
                ['Panitia Aktif', $stats['active_members'], 'Sudah menerima undangan', 'primary', 'ki-people', 5],
                ['Menunggu Konfirmasi', $stats['pending_invitations'], 'Undangan belum dijawab', 'warning', 'ki-time', 2],
                ['Seksi', $stats['divisions'], 'Termasuk sub-seksi', 'info', 'ki-element-11', 4],
                ['Jabatan Wajib Terisi', $stats['required_filled'].'/'.$stats['required_total'], 'Syarat untuk mengaktifkan event', $stats['required_filled'] === $stats['required_total'] ? 'success' : 'danger', 'ki-shield-tick', 3],
            ] as [$label, $value, $footer, $tone, $icon, $paths])
                <div class="col-sm-6 col-xl-3">
                    <div class="ui-stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="ui-stat-label">{{ $label }}</span>
                            <div class="ui-stat-icon ui-stat-icon--{{ $tone }}">
                                <i class="ki-duotone {{ $icon }} fs-2">
                                    @for ($path = 1; $path <= $paths; $path++)
                                        <span class="path{{ $path }}"></span>
                                    @endfor
                                </i>
                            </div>
                        </div>
                        <div class="ui-stat-value">{{ $value }}</div>
                        <div class="ui-stat-footer">{{ $footer }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-5">
            <div class="col-xl-8">
                <div class="ui-detail-stack">
                    @if ($requiredRoles->isNotEmpty())
                        <div class="card ui-panel" data-readiness>
                            <div class="card-header">
                                <div class="card-title">
                                    <h3>Jabatan Wajib</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="ui-panel-description mt-0 mb-5">Event hanya dapat diaktifkan bila setiap jabatan wajib dipegang oleh panitia yang sudah menerima undangan.</div>
                                <div class="row g-4">
                                    @foreach ($requiredRoles as $requirement)
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-3 border border-gray-300 rounded p-4" data-required-role="{{ $requirement['rule']->committeeRole->name }}" data-filled="{{ $requirement['is_filled'] ? '1' : '0' }}">
                                                @if ($requirement['is_filled'])
                                                    <i class="ki-duotone ki-check-circle fs-2 text-success"><span class="path1"></span><span class="path2"></span></i>
                                                @else
                                                    <i class="ki-duotone ki-cross-circle fs-2 text-danger"><span class="path1"></span><span class="path2"></span></i>
                                                @endif
                                                <div>
                                                    <div class="fw-semibold text-gray-800">{{ $requirement['rule']->committeeRole->name }}</div>
                                                    <div class="text-muted fs-7">{{ $requirement['is_filled'] ? $requirement['holders'].' orang' : 'Belum terisi' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="card ui-panel">
                        <div class="card-header">
                            <div class="card-title"><h3>Profil Event</h3></div>
                        </div>
                        <div class="card-body">
                            <div class="row g-7">
                                @foreach ([
                                    ['Tema', $event->theme],
                                    ['Subtema', $event->subtheme],
                                    ['Tanggal', $event->dateRangeLabel()],
                                    ['Masa Kepanitiaan', $event->committee_starts_on ? $event->committee_starts_on->locale('id')->translatedFormat('j M Y').' – '.($event->committee_ends_on?->locale('id')->translatedFormat('j M Y') ?? '…') : null],
                                    ['Tempat', $event->venue?->name ?? $event->location_text],
                                    ['Kategori', $event->category?->name],
                                    ['Unit Penanggung Jawab', $event->unit?->name],
                                    ['Penanggung Jawab Organisasi', $event->responsibleUser?->name],
                                    ['Target Peserta', $event->target_participants ? number_format($event->target_participants, 0, ',', '.').' orang' : null],
                                    ['Target Anggaran', $event->budget_target_amount !== null ? 'Rp '.number_format($event->budget_target_amount, 0, ',', '.') : null],
                                ] as [$label, $value])
                                    <div class="col-md-6 ui-info-item">
                                        <span class="ui-info-label">{{ $label }}</span>
                                        <span class="ui-info-value">{{ $value ?? '-' }}</span>
                                    </div>
                                @endforeach
                            </div>
                            @foreach ([['Deskripsi', $event->description], ['Latar Belakang', $event->background]] as [$label, $text])
                                @if ($text)
                                    <div class="separator my-6"></div>
                                    <div class="ui-info-item">
                                        <span class="ui-info-label">{{ $label }}</span>
                                        <div class="text-gray-700 fs-6 ui-pre-line">{{ $text }}</div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="card ui-panel">
                        <div class="card-header">
                            <div class="card-title"><h3>Tujuan, Sasaran, dan Indikator</h3></div>
                        </div>
                        <div class="card-body">
                            @if ($event->objectives->isEmpty())
                                <div class="text-muted fs-6">Belum diisi. Tujuan dan indikator dipakai lagi saat menyusun proposal dan evaluasi.</div>
                            @else
                                @foreach ($objectiveTypes as $type)
                                    @if ($objectivesByType->has($type->value))
                                        <div class="mb-6">
                                            <div class="ui-info-label mb-3">{{ $type->label() }}</div>
                                            <ul class="mb-0 ps-5">
                                                @foreach ($objectivesByType[$type->value] as $objective)
                                                    <li class="text-gray-700 fs-6 mb-2">
                                                        {{ $objective->description }}
                                                        @if ($objective->target)
                                                            <span class="badge badge-light-primary ms-2">Target: {{ $objective->target }}</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                @if ($work)
                    <div class="card ui-panel mb-5" data-work-progress>
                        <div class="card-header">
                            <div class="card-title"><h3>Progres Kerja</h3></div>
                            <div class="card-toolbar">
                                <a href="{{ route('events.work', $event) }}" class="btn btn-sm btn-light-primary">Lihat Tugas</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-end justify-content-between mb-2">
                                <span class="fs-2hx fw-bold text-gray-900" data-work-percent>{{ $work['progress']['percent'] === null ? 'N/A' : $work['progress']['percent'].'%' }}</span>
                                <span class="text-muted fs-7">{{ $work['progress']['total'] > 0 ? $work['completed'].' dari '.($work['open'] + $work['completed']).' tugas selesai' : 'Belum ada tugas' }}</span>
                            </div>
                            <div class="progress h-8px bg-light mb-6">
                                <div class="progress-bar bg-primary" style="width: {{ $work['progress']['percent'] ?? 0 }}%"></div>
                            </div>
                            <div class="row g-3 text-center">
                                @foreach ([['Belum Selesai', $work['open'], 'text-gray-900'], ['Terlambat', $work['overdue'], 'text-danger'], ['Selesai', $work['completed'], 'text-success']] as [$label, $value, $tone])
                                    <div class="col-4">
                                        <div class="border border-dashed border-gray-300 rounded py-3">
                                            <div class="fs-3 fw-bold {{ $tone }}">{{ $value }}</div>
                                            <div class="text-muted fs-8">{{ $label }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                <div class="card ui-panel">
                    <div class="card-header">
                        <div class="card-title"><h3>Riwayat Status</h3></div>
                    </div>
                    <div class="card-body">
                        <div class="ui-timeline">
                            @foreach ($history as $item)
                                @php($entry = $item['entry'])
                                <div class="ui-timeline-item">
                                    <div class="fw-semibold text-gray-800 fs-6">
                                        @if ($entry->from_status === null)
                                            Event dibuat sebagai {{ $entry->to_status->label() }}
                                        @elseif ($entry->from_status !== $entry->to_status)
                                            {{ $entry->from_status->label() }} → {{ $entry->to_status->label() }}
                                        @else
                                            Fase: {{ $entry->to_phase->label() }}
                                        @endif
                                    </div>
                                    <div class="text-muted fs-7">{{ $item['at'] }} · {{ $entry->actor?->name ?? 'Sistem' }}</div>
                                    @if ($entry->reason && $entry->from_status !== null)
                                        <div class="text-gray-700 fs-7 mt-1 ui-pre-line">{{ $entry->reason }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('update', $event)
        @include('events.partials.form-modal')
    @endcan

    @can('changeStatus', $event)
        <div class="modal fade" id="kt_modal_event_status" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_event_status_title">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="kt_modal_event_status_title" data-status-title>Ubah Status</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
                    </div>
                    <form class="form" id="kt_form_event_status" novalidate>
                        <div class="modal-body">
                            <input type="hidden" name="status">
                            <div class="fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="event_status_reason" data-status-reason-label>Catatan</label>
                                <textarea name="reason" id="event_status_reason" class="form-control form-control-solid" rows="4" maxlength="1000"></textarea>
                                <div class="fs-7 text-muted mt-2">Tercatat di riwayat status event.</div>
                            </div>
                        </div>
                        <div class="modal-footer flex-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" data-status-submit>
                                <span class="indicator-label">Simpan</span>
                                <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('changePhase', $event)
        <div class="modal fade" id="kt_modal_event_phase" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_event_phase_title">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="kt_modal_event_phase_title">Ubah Fase</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
                    </div>
                    <form class="form" id="kt_form_event_phase" novalidate>
                        <div class="modal-body">
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2" for="event_phase">Fase</label>
                                <select name="phase" id="event_phase" class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-dropdown-parent="#kt_modal_event_phase">
                                    @foreach ($phases as $phase)
                                        <option value="{{ $phase->value }}" @selected($event->phase === $phase)>{{ $phase->label() }}</option>
                                    @endforeach
                                </select>
                                <div class="fs-7 text-muted mt-2">Fase menunjukkan pekerjaan yang sedang berjalan dan tidak harus berurutan.</div>
                            </div>
                            <div class="fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="event_phase_note">Catatan</label>
                                <textarea name="note" id="event_phase_note" class="form-control form-control-solid" rows="3" maxlength="1000"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer flex-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" data-phase-submit>
                                <span class="indicator-label">Simpan</span>
                                <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @include('events.partials.event-guide')
@endsection

@section('script')
    <script src="{{ asset('assets/js/events/form.js') }}"></script>
    <script src="{{ asset('assets/js/events/overview.js') }}"></script>
@endsection
