{{--
    Laporan Kondisi: keadaan seluruh event untuk Pengurus Inti dan Pengawas, dibaca bersama
    saat rapat rutin. Isinya dimuat setiap kali dibuka (assets/js/events/summary.js) dan
    semua teks dari server dipasang lewat textContent.
--}}
<div class="modal fade" id="kt_modal_event_summary" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_event_summary_title" data-summary-url="{{ route('events.summary', $event) }}">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <h3 class="modal-title" id="kt_modal_event_summary_title">Laporan Kondisi</h3>
                    <div class="text-muted fs-7 mt-1 text-truncate">{{ $event->name }} · <span data-summary-generated>Memuat...</span></div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-3">
                    <button type="button" class="btn btn-sm btn-light" data-summary-refresh>
                        <i class="ki-duotone ki-arrows-circle fs-4"><span class="path1"></span><span class="path2"></span></i><span class="d-none d-sm-inline"> Muat ulang</span>
                    </button>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
                </div>
            </div>
            <div class="modal-body bg-light">
                <div class="ui-summary">
                    <div class="ui-empty-state bg-white" data-summary-loading>
                        <span class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></span>
                        <div class="text-muted fs-6">Menyiapkan laporan...</div>
                    </div>
                    <div class="ui-empty-state bg-white d-none" data-summary-failed>
                        <div class="fw-semibold text-gray-800 fs-5 mb-3">Laporan belum bisa dimuat.</div>
                        <button type="button" class="btn btn-sm btn-light-primary" data-summary-refresh>Coba lagi</button>
                    </div>

                    <div class="d-none" data-summary-content>
                        <div class="row g-5 mb-5" data-summary-kpis></div>

                        <div class="row g-5 mb-5">
                            <div class="col-xl-8">
                                <div class="card ui-panel h-100">
                                    <div class="card-header">
                                        <div class="card-title flex-column align-items-start">
                                            <h3 class="mb-1">Progres per Seksi</h3>
                                            <span class="ui-panel-description mt-0">Klik sebuah seksi untuk melihat tugas dan programnya saja.</span>
                                        </div>
                                    </div>
                                    <div class="card-body pt-3" data-summary-divisions></div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="card ui-panel h-100">
                                    <div class="card-header">
                                        <div class="card-title"><h3>Status Semua Tugas</h3></div>
                                    </div>
                                    <div class="card-body pt-3">
                                        <div data-summary-status-chart></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ui-summary-filter mb-5">
                            <span class="fw-semibold text-gray-700 fs-6">Tampilkan untuk</span>
                            <div class="ui-summary-filter-select">
                                <select class="form-select form-select-sm form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Semua seksi" data-allow-clear="true" data-dropdown-parent="#kt_modal_event_summary" data-summary-division aria-label="Pilih seksi">
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-5 mb-5">
                            <div class="col-xl-6">
                                <div class="card ui-panel h-100">
                                    <div class="card-header">
                                        <div class="card-title flex-column align-items-start">
                                            <h3 class="mb-1">Perlu Perhatian</h3>
                                            <span class="ui-panel-description mt-0">Tugas yang sudah lewat tenggat dan yang jatuh tempo dua minggu ke depan.</span>
                                        </div>
                                    </div>
                                    <div class="card-body pt-2">
                                        <ul class="nav nav-line-tabs border-0 fs-6 fw-semibold mb-3" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button type="button" class="nav-link text-active-primary active pb-3" data-bs-toggle="tab" data-bs-target="#summary_tab_overdue" role="tab" aria-controls="summary_tab_overdue" aria-selected="true">
                                                    Terlambat <span class="badge badge-light-danger ms-1" data-summary-count="overdue">0</span>
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button type="button" class="nav-link text-active-primary pb-3" data-bs-toggle="tab" data-bs-target="#summary_tab_upcoming" role="tab" aria-controls="summary_tab_upcoming" aria-selected="false">
                                                    2 minggu ke depan <span class="badge badge-light-primary ms-1" data-summary-count="upcoming">0</span>
                                                </button>
                                            </li>
                                        </ul>
                                        <div class="tab-content">
                                            <div class="tab-pane fade show active" id="summary_tab_overdue" role="tabpanel" data-summary-list="overdue"></div>
                                            <div class="tab-pane fade" id="summary_tab_upcoming" role="tabpanel" data-summary-list="upcoming"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="card ui-panel h-100">
                                    <div class="card-header">
                                        <div class="card-title flex-column align-items-start">
                                            <h3 class="mb-1">Program Kerja</h3>
                                            <span class="ui-panel-description mt-0">Yang perlu perhatian ditaruh paling atas.</span>
                                        </div>
                                    </div>
                                    <div class="card-body pt-2" data-summary-programs></div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-5">
                            <div class="col-xl-4">
                                <div class="card ui-panel h-100">
                                    <div class="card-header">
                                        <div class="card-title"><h3>Rapat &amp; Tindak Lanjut</h3></div>
                                    </div>
                                    <div class="card-body pt-3" data-summary-meetings></div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="card ui-panel h-100">
                                    <div class="card-header">
                                        <div class="card-title"><h3>Jadwal 2 Minggu ke Depan</h3></div>
                                    </div>
                                    <div class="card-body pt-3" data-summary-schedule></div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="card ui-panel h-100">
                                    <div class="card-header">
                                        <div class="card-title"><h3>Kepanitiaan</h3></div>
                                    </div>
                                    <div class="card-body pt-3" data-summary-committee></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
