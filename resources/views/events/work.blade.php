@extends('layouts.main_layout', ['title' => 'Program & Tugas · '.$event->name, 'breadcrum' => ['Event', $event->code, 'Program & Tugas']])

@section('content')
    <div class="ui-page" data-work-page
        data-data-url="{{ route('events.work.data', $event) }}"
        data-task-insert-url="{{ route('events.tasks.insert', $event) }}"
        data-task-base-url="{{ url('events/'.$event->getKey().'/tasks') }}"
        data-program-insert-url="{{ route('events.programs.insert', $event) }}"
        data-program-base-url="{{ url('events/'.$event->getKey().'/programs') }}">
        @include('events.partials.header')

        <div class="card ui-panel" data-work-summary>
            <div class="card-body py-6">
                <div class="d-flex flex-wrap align-items-center gap-6">
                    <div class="flex-grow-1" style="min-width:220px">
                        <div class="d-flex align-items-baseline gap-3 mb-2">
                            <span class="fs-2x fw-bold text-gray-900" data-summary="progress">–</span>
                            <span class="text-muted fs-7" data-summary-footer>Progres event</span>
                        </div>
                        <div class="progress h-8px bg-light">
                            <div class="progress-bar bg-primary" data-summary-bar style="width:0%"></div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach ([
                            ['open', 'Belum selesai', 'info'],
                            ['overdue', 'Terlambat', 'danger'],
                            ['completed', 'Selesai', 'success'],
                        ] as [$key, $label, $tone])
                            <div class="ui-summary-chip ui-summary-chip--{{ $tone }}">
                                <span class="ui-summary-chip-value" data-summary="{{ $key }}">–</span>
                                <span class="ui-summary-chip-label">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card ui-panel" data-tasks-panel>
            <div class="card-header align-items-center">
                <ul class="nav nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold" role="tablist" data-view-switch>
                    <li class="nav-item"><button type="button" class="nav-link text-active-primary active" data-view="mine" role="tab">Tugas Saya <span class="badge badge-light-primary ms-1" data-mine-count>0</span></button></li>
                    <li class="nav-item"><button type="button" class="nav-link text-active-primary" data-view="programs" role="tab">Per Program</button></li>
                    <li class="nav-item"><button type="button" class="nav-link text-active-primary" data-view="calendar" role="tab">Kalender</button></li>
                </ul>
                <div class="card-toolbar gap-2">
                    @if ($canCreatePrograms)
                        <button type="button" class="btn btn-sm btn-light-primary" data-program-add>
                            <i class="ki-duotone ki-plus fs-3"></i> Program Kerja
                        </button>
                    @endif
                    @if ($canCreateTasks)
                        <button type="button" class="btn btn-sm btn-primary" data-task-add>
                            <i class="ki-duotone ki-plus fs-3"></i> Tugas
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body pt-5">
                {{-- Tugas Saya: hanya tugas milik orang yang membuka halaman, satu tombol per tugas. --}}
                <div data-view-panel="mine">
                    <div class="fs-6 text-muted mb-5">Tugas yang menjadi tanggung jawab Anda. Setelah pekerjaannya beres, tekan <strong>Tandai Selesai</strong> lalu tulis hasilnya dan lampirkan foto bila ada. Semua panitia dapat melihat dan mengomentari tugas.</div>
                    <div class="ui-task-cards" data-mine-open></div>
                    <div class="ui-empty-state d-none" data-mine-empty>
                        <i class="ki-duotone ki-check-circle fs-3x text-success mb-3"><span class="path1"></span><span class="path2"></span></i>
                        <div class="fw-semibold text-gray-800 fs-5">Tidak ada tugas yang menunggu Anda</div>
                        <div class="text-muted fs-7 mt-1">Tugas yang ditugaskan kepada Anda akan muncul di sini.</div>
                    </div>
                    <details class="mt-7" data-mine-done-wrapper>
                        <summary class="fw-semibold text-gray-700 fs-6 cursor-pointer">Sudah selesai (<span data-mine-done-count>0</span>)</summary>
                        <div class="ui-task-cards mt-4" data-mine-done></div>
                    </details>
                </div>

                {{-- Per Program: setiap program beserta daftar tugasnya. --}}
                <div class="d-none" data-view-panel="programs">
                    <div class="d-flex flex-wrap gap-3 mb-6" data-task-filters>
                        <div class="position-relative flex-grow-1" style="min-width:220px;max-width:360px">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-4"><span class="path1"></span><span class="path2"></span></i>
                            <input type="text" class="form-control form-control-solid ps-12" placeholder="Cari tugas" data-filter="search" aria-label="Cari tugas">
                        </div>
                        <div style="min-width:220px">
                            <select class="form-select form-select-solid" data-control="select2" data-placeholder="Semua seksi" data-allow-clear="true" data-filter="division" aria-label="Saring seksi">
                                <option value=""></option>
                                <option value="none">Pengurus Inti</option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" data-filter="hide-done">
                            <span class="form-check-label text-gray-700">Sembunyikan yang selesai</span>
                        </label>
                    </div>
                    <div class="d-flex flex-column gap-5" data-program-groups></div>
                    <div class="ui-empty-state d-none" data-programs-empty>
                        <div class="fw-semibold text-gray-800 fs-5">Belum ada program atau tugas</div>
                        <div class="text-muted fs-7 mt-1">Mulai dengan menambah program kerja seksi, lalu isi tugas-tugasnya.</div>
                    </div>
                </div>

                <div class="d-none" data-view-panel="calendar">
                    <div class="fs-7 text-muted mb-4">Tugas tampil pada tanggal mulai sampai tenggatnya. Tugas tanpa tanggal tidak muncul di kalender.</div>
                    <div data-calendar></div>
                </div>
            </div>
        </div>
    </div>

    @if ($canCreateTasks)
        @include('events.partials.task-form-modal')
    @endif

    @if ($canCreatePrograms)
        @include('events.partials.program-form-modal')
    @endif

    @include('events.partials.task-complete-modal')

    @include('events.partials.work-guide')
@endsection

@section('script')
    <script src="{{ asset('assets/js/events/task-form.js') }}"></script>
    <script src="{{ asset('assets/js/events/task-complete.js') }}"></script>
    <script src="{{ asset('assets/js/events/work.js') }}"></script>
@endsection
