@extends('layouts.main_layout', ['title' => 'Event', 'breadcrum' => ['Event']])

@section('content')
    <div class="ui-page">
        <x-ui.page-header title="Event" subtitle="Kegiatan yang Anda ikuti sebagai panitia, dan seluruh kegiatan organisasi bila Anda berwenang melihatnya." guide="#kt_modal_guide">
            <x-slot:actions>
                @can('create', \App\Models\Event::class)
                    <button type="button" class="btn btn-primary" data-event-create>
                        <i class="ki-duotone ki-plus fs-2"><span class="path1"></span><span class="path2"></span></i> Tambah Event
                    </button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>

        @if ($invitations->isNotEmpty())
            <div class="card ui-panel" data-invitations>
                <div class="card-header">
                    <div class="card-title"><h3>Undangan Kepanitiaan</h3></div>
                </div>
                <div class="card-body">
                    @foreach ($invitations as $invitation)
                        <div class="ui-committee-row">
                            <div>
                                <div class="fw-bold text-gray-800 fs-6">{{ $invitation->event->name }}</div>
                                <div class="text-muted fs-7">{{ $invitation->event->code }} · {{ $invitation->event->dateRangeLabel() }}</div>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach ($invitation->activeAssignments as $assignment)
                                        <span class="badge badge-light-primary">{{ $assignment->committeeRole->name }}{{ $assignment->division ? ' · '.$assignment->division->name : '' }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="ui-committee-actions gap-2">
                                <button type="button" class="btn btn-sm btn-light" data-invitation-response="decline" data-event-id="{{ $invitation->event_id }}">Tolak</button>
                                <button type="button" class="btn btn-sm btn-primary" data-invitation-response="accept" data-event-id="{{ $invitation->event_id }}">Terima</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card ui-panel">
            <div class="card-header">
                <div class="card-title"><h3>Event Saya</h3></div>
            </div>
            <div class="card-body">
                @if ($myMemberships->isEmpty())
                    <div class="text-muted fs-6">Anda belum tergabung dalam kepanitiaan event mana pun. Undangan kepanitiaan akan muncul di halaman ini.</div>
                @else
                    <div class="row g-5">
                        @foreach ($myMemberships as $membership)
                            <div class="col-md-6 col-xl-4">
                                <a href="{{ route('events.overview', $membership->event) }}" class="d-block ui-stat-card text-hover-primary">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                        <span class="fw-bold text-gray-800 fs-5">{{ $membership->event->name }}</span>
                                        <span class="badge badge-light-{{ $membership->event->status->tone() }} flex-shrink-0">{{ $membership->event->status->label() }}</span>
                                    </div>
                                    <div class="text-muted fs-7 mb-3">{{ $membership->event->code }} · {{ $membership->event->dateRangeLabel() }}</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse ($membership->activeAssignments as $assignment)
                                            <span class="badge badge-light-primary">{{ $assignment->committeeRole->name }}{{ $assignment->division ? ' · '.$assignment->division->name : '' }}</span>
                                        @empty
                                            <span class="text-muted fs-7">Tanpa jabatan aktif</span>
                                        @endforelse
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @can('events.view')
            <div class="card ui-panel">
                <div class="card-header">
                    <div class="card-title"><h3>Semua Event</h3></div>
                </div>
                <div class="card-body">
                    <div class="ui-toolbar">
                        <div class="ui-toolbar-filters">
                            <div class="ui-search-box">
                                <i class="ki-duotone ki-magnifier fs-3 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                                <input type="text" class="form-control form-control-solid" placeholder="Cari nama, kode, atau tema..." aria-label="Cari event" data-events-search>
                            </div>
                            <div class="w-100 w-md-125px">
                                <select class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Tahun"
                                    data-allow-clear="true" aria-label="Saring menurut tahun" data-events-filter="year">
                                    <option></option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-100 w-md-150px">
                                <select class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Semua Status"
                                    data-allow-clear="true" aria-label="Saring menurut status" data-events-filter="status">
                                    <option></option>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-100 w-md-200px">
                                <select class="form-select form-select-solid" data-control="select2" data-placeholder="Semua Kategori"
                                    data-allow-clear="true" aria-label="Saring menurut kategori" data-events-filter="event_category_id">
                                    <option></option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="ui-table-shell">
                        <table id="kt_datatable_events" class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Event</th>
                                    <th>Kategori</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Fase</th>
                                    <th>Panitia</th>
                                    <th class="text-end min-w-75px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 fw-semibold"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    @can('create', \App\Models\Event::class)
        @include('events.partials.form-modal')
    @endcan

    <x-ui.guide-modal title="Panduan Event">
        Setiap kegiatan dikelola sebagai satu event: identitas, kepanitiaan, dan nantinya program kerja, rapat, serta keuangannya.
        <x-slot:steps>
            <li>Klik <strong>Tambah Event</strong>, isi identitas, tanggal, dan tempatnya. Event baru berstatus <strong>Draf</strong>.</li>
            <li>Buka event, lalu susun panitia di tab <strong>Panitia</strong>: pengurus inti, seksi, dan anggotanya.</li>
            <li>Setelah semua jabatan wajib terisi oleh panitia yang sudah bersedia, event dapat diaktifkan.</li>
        </x-slot:steps>
        <x-slot:notes>
            <li>Undangan kepanitiaan untuk Anda muncul di halaman ini. Anda baru dapat membuka event setelah menerima undangan.</li>
            <li>Anda hanya melihat event tempat Anda menjadi panitia, kecuali Anda berwenang melihat semua event.</li>
        </x-slot:notes>
    </x-ui.guide-modal>
@endsection

@section('script')
    <script src="{{ asset('assets/js/events/form.js') }}"></script>
    <script src="{{ asset('assets/js/events/index.js') }}"></script>
@endsection
