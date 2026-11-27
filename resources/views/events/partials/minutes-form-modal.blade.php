{{--
    Notulen dalam satu form layar penuh dengan tiga tab: Isi Notulen (editor dokumen selebar
    layar), Keputusan, dan Tindak Lanjut. Satu tombol simpan untuk semuanya; disimpan =
    langsung dapat dibaca semua panitia, dan tindak lanjut baru otomatis menjadi tugas.
    Kehadiran diisi di tab Absensi halaman rapat.
--}}
@php($memberOptionsUrl = route('events.work.member_options', $event))
<div class="modal fade" id="kt_modal_minutes" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_minutes_title">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header flex-column align-items-stretch pb-0">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <h3 class="modal-title" id="kt_modal_minutes_title">{{ $meeting->hasMinutes() ? 'Ubah Notulen' : 'Tulis Notulen' }}</h3>
                        <div class="text-muted fs-7 mt-1">{{ $meeting->title }} · {{ $meeting->meets_on->locale('id')->translatedFormat('j F Y') }}</div>
                    </div>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
                </div>
                <ul class="nav nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mt-4" role="tablist" data-minutes-tabs>
                    @foreach ([
                        ['isi', 'Isi Notulen', null],
                        ['keputusan', 'Keputusan', $meeting->decisions->count()],
                        ['tindak-lanjut', 'Tindak Lanjut', $meeting->actionItems->count()],
                    ] as [$key, $label, $count])
                        <li class="nav-item" role="presentation">
                            <button type="button" @class(['nav-link text-active-primary pb-4', 'active' => $key === 'isi']) data-bs-toggle="tab" data-bs-target="#minutes_tab_{{ $key }}"
                                data-minutes-tab="{{ $key }}" role="tab" aria-controls="minutes_tab_{{ $key }}" aria-selected="{{ $key === 'isi' ? 'true' : 'false' }}">
                                {{ $label }}
                                @if ($count !== null)
                                    <span class="badge badge-light-primary ms-1" data-minutes-count="{{ $key }}">{{ $count }}</span>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
            <form class="form d-flex flex-column overflow-hidden" id="kt_form_minutes" data-url="{{ route('events.meetings.minutes', [$event, $meeting]) }}" autocomplete="off" novalidate>
                <input type="hidden" name="minutes_version" value="{{ $meeting->minutes_version }}">
                <div class="modal-body overflow-auto bg-light">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="minutes_tab_isi" role="tabpanel">
                            <div class="fv-row">
                                <label class="visually-hidden" for="minutes_text">Isi notulen</label>
                                <textarea name="minutes" id="minutes_text" class="form-control" rows="20" data-rich-editor>{{ $minutesHtml }}</textarea>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="minutes_tab_keputusan" role="tabpanel">
                            <div class="card ui-panel mw-1000px mx-auto">
                                <div class="card-header">
                                    <div class="card-title flex-column align-items-start">
                                        <h3 class="mb-1">Keputusan</h3>
                                        <span class="ui-panel-description mt-0">Apa saja yang diputuskan dalam rapat ini. Satu keputusan per baris.</span>
                                    </div>
                                    <div class="card-toolbar">
                                        <button type="button" class="btn btn-sm btn-light-primary" data-decision-add><i class="ki-duotone ki-plus fs-4"></i> Tambah keputusan</button>
                                    </div>
                                </div>
                                <div class="card-body pt-4">
                                    <div class="d-flex flex-column gap-3" data-decision-rows>
                                        @foreach ($meeting->decisions as $decision)
                                            <div class="d-flex gap-2 fv-row" data-decision-row>
                                                <input type="text" name="decisions[]" class="form-control form-control-solid" maxlength="2000" value="{{ $decision->body }}" aria-label="Keputusan">
                                                <button type="button" class="btn btn-icon btn-light-danger flex-shrink-0" data-row-remove aria-label="Hapus keputusan"><i class="las la-times fs-3"></i></button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <template data-decision-template>
                                        <div class="d-flex gap-2 fv-row" data-decision-row>
                                            <input type="text" name="decisions[]" class="form-control form-control-solid" maxlength="2000" placeholder="Contoh: Ibadah malam Natal dimulai pukul 19.00" aria-label="Keputusan">
                                            <button type="button" class="btn btn-icon btn-light-danger flex-shrink-0" data-row-remove aria-label="Hapus keputusan"><i class="las la-times fs-3"></i></button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="minutes_tab_tindak-lanjut" role="tabpanel">
                            <div class="card ui-panel mw-1000px mx-auto">
                                <div class="card-header">
                                    <div class="card-title flex-column align-items-start">
                                        <h3 class="mb-1">Tindak Lanjut</h3>
                                        <span class="ui-panel-description mt-0">Setiap tindak lanjut baru otomatis menjadi tugas di Program &amp; Tugas{{ $meeting->division ? ' seksi '.$meeting->division->name : ' Pengurus Inti' }}.</span>
                                    </div>
                                    <div class="card-toolbar">
                                        <button type="button" class="btn btn-sm btn-light-primary" data-action-add><i class="ki-duotone ki-plus fs-4"></i> Tambah tindak lanjut</button>
                                    </div>
                                </div>
                                <div class="card-body pt-4">
                                    @foreach ($meeting->actionItems as $item)
                                        <div class="ui-check-row">
                                            <span class="badge badge-light-success">Sudah jadi tugas</span>
                                            <span class="flex-grow-1 text-gray-800">{{ $item->title }}</span>
                                            <span class="text-muted fs-7">{{ $item->owner?->user->name ?? '-' }}</span>
                                            @if ($item->due_on)
                                                <span class="text-muted fs-7">{{ $item->due_on->locale('id')->translatedFormat('j M Y') }}</span>
                                            @endif
                                        </div>
                                    @endforeach

                                    <div class="d-flex flex-column gap-3 mt-3" data-action-rows></div>
                                    <div class="text-muted fs-7 mt-3 d-none" data-action-empty>Belum ada tindak lanjut. Tekan "Tambah tindak lanjut" bila ada pekerjaan yang harus dilakukan setelah rapat.</div>
                                    <template data-action-template>
                                        <div class="row g-3 align-items-start ui-action-row" data-action-row>
                                            <div class="col-md-5 fv-row">
                                                <input type="text" data-action-field="title" class="form-control form-control-solid" maxlength="200" placeholder="Apa yang harus dikerjakan" aria-label="Tindak lanjut">
                                            </div>
                                            <div class="col-md-4 fv-row">
                                                <select data-action-field="owner_membership_id" class="form-select form-select-solid" data-select-remote="{{ $memberOptionsUrl }}" data-allow-clear="true" data-placeholder="Penanggung jawab" aria-label="Penanggung jawab"><option value=""></option></select>
                                            </div>
                                            <div class="col-9 col-md-2 fv-row">
                                                <input type="text" data-action-field="due_on" class="form-control form-control-solid" data-date-picker placeholder="Tenggat" aria-label="Tenggat">
                                            </div>
                                            <div class="col-3 col-md-1 text-end">
                                                <button type="button" class="btn btn-icon btn-light-danger" data-row-remove aria-label="Hapus tindak lanjut"><i class="las la-times fs-3"></i></button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-minutes-submit>
                        <span class="indicator-label">Simpan Notulen</span>
                        <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
