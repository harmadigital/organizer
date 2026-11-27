{{--
    Form tambah/ubah event dalam bentuk stepper (komponen KTStepper Metronic), satu langkah per kategori.
    Dipakai di halaman daftar event (tambah) dan ringkasan event (ubah).
--}}
<div class="modal fade" id="kt_modal_event" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_event_title">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="stepper stepper-pills ui-stepper d-flex flex-column h-100 overflow-hidden" id="kt_event_stepper">
                <div class="modal-header">
                    <h3 class="modal-title" id="kt_modal_event_title" data-event-form-title>Tambah Event</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup">
                        <i class="las la-times fs-2"></i>
                    </button>
                </div>

                <div class="stepper-nav flex-center flex-wrap px-5 pt-5 pb-2 border-bottom border-gray-300">
                    @foreach ([
                        ['Identitas', 'Nama, kode, tema'],
                        ['Waktu & Tempat', 'Tanggal dan lokasi'],
                        ['Sasaran', 'Penanggung jawab dan target'],
                        ['Deskripsi', 'Uraian dan latar belakang'],
                        ['Tujuan', 'Tujuan dan indikator'],
                    ] as $index => [$title, $description])
                        <div @class(['stepper-item', 'current' => $index === 0]) data-kt-stepper-element="nav" data-kt-stepper-action="step">
                            <div class="stepper-wrapper d-flex align-items-center">
                                <div class="stepper-icon w-40px h-40px">
                                    <i class="ki-solid ki-check stepper-check fs-2"></i>
                                    <span class="stepper-number">{{ $index + 1 }}</span>
                                </div>
                                <div class="stepper-label">
                                    <h3 class="stepper-title fs-6">{{ $title }}</h3>
                                    <div class="stepper-desc">{{ $description }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form id="kt_form_event" class="form d-flex flex-column flex-grow-1 overflow-hidden" autocomplete="off" novalidate>
                    <div class="modal-body overflow-auto">
                        {{-- Langkah 1: Identitas --}}
                        <div class="flex-column current" data-kt-stepper-element="content">
                            <div class="w-100">
                                <div class="ui-stepper-section-title">Identitas Event</div>
                                <div class="ui-stepper-section-hint">Nama dan tema dipakai di seluruh dokumen event, termasuk proposal dan LPJ.</div>
                                <div class="row g-7 mb-7">
                                    <div class="col-lg-8 fv-row">
                                        <label class="required fw-semibold fs-6 mb-2" for="event_name">Nama Event</label>
                                        <input type="text" name="name" id="event_name" class="form-control form-control-solid" placeholder="Contoh: Perayaan Natal 2026"
                                            maxlength="200" data-step-required data-step-label="nama event">
                                    </div>
                                    <div class="col-md-6 col-lg-4 fv-row">
                                        <label class="fw-semibold fs-6 mb-2" for="event_code">Kode Event</label>
                                        <input type="text" name="code" id="event_code" class="form-control form-control-solid text-uppercase" placeholder="Otomatis bila kosong"
                                            maxlength="30" autocapitalize="characters">
                                        <div class="fs-7 text-muted mt-2">Contoh otomatis: EVT-2026-001.</div>
                                    </div>
                                </div>
                                <div class="row g-7 mb-7">
                                    <div class="col-md-6 fv-row">
                                        <label class="fw-semibold fs-6 mb-2" for="event_category">Kategori</label>
                                        <select name="event_category_id" id="event_category" class="form-select form-select-solid" data-control="select2"
                                            data-dropdown-parent="#kt_modal_event" data-placeholder="Pilih kategori" data-allow-clear="true">
                                            <option></option>
                                            @foreach ($categoryOptions as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="fv-row mb-7">
                                    <label class="fw-semibold fs-6 mb-2" for="event_theme">Tema</label>
                                    <input type="text" name="theme" id="event_theme" class="form-control form-control-solid" maxlength="255" placeholder="Tema utama kegiatan">
                                </div>
                                <div class="fv-row">
                                    <label class="fw-semibold fs-6 mb-2" for="event_subtheme">Subtema</label>
                                    <input type="text" name="subtheme" id="event_subtheme" class="form-control form-control-solid" maxlength="255" placeholder="Misalnya ayat atau penjabaran tema">
                                </div>
                            </div>
                        </div>

                        {{-- Langkah 2: Waktu dan tempat --}}
                        <div class="flex-column" data-kt-stepper-element="content">
                            <div class="w-100">
                                <div class="ui-stepper-section-title">Tanggal Pelaksanaan</div>
                                <div class="ui-stepper-section-hint">Untuk kegiatan satu hari, isi tanggal mulai dan selesai dengan tanggal yang sama.</div>
                                <div class="row g-7 mb-9">
                                    <div class="col-md-6 col-lg-4 fv-row">
                                        <label class="required fw-semibold fs-6 mb-2" for="event_starts_on">Tanggal Mulai</label>
                                        <input type="text" name="starts_on" id="event_starts_on" class="form-control form-control-solid" placeholder="Pilih tanggal"
                                            data-date-picker data-step-required data-step-label="tanggal mulai">
                                    </div>
                                    <div class="col-md-6 col-lg-4 fv-row">
                                        <label class="required fw-semibold fs-6 mb-2" for="event_ends_on">Tanggal Selesai</label>
                                        <input type="text" name="ends_on" id="event_ends_on" class="form-control form-control-solid" placeholder="Pilih tanggal"
                                            data-date-picker data-date-min="#event_starts_on" data-step-required data-step-label="tanggal selesai">
                                    </div>
                                </div>

                                <div class="ui-stepper-section-title">Masa Kepanitiaan</div>
                                <div class="ui-stepper-section-hint">Rentang waktu panitia bekerja, dari pembentukan sampai LPJ selesai. Boleh diisi belakangan.</div>
                                <div class="row g-7 mb-9">
                                    <div class="col-md-6 col-lg-4 fv-row">
                                        <label class="fw-semibold fs-6 mb-2" for="event_committee_starts_on">Awal Masa Panitia</label>
                                        <input type="text" name="committee_starts_on" id="event_committee_starts_on" class="form-control form-control-solid" placeholder="Pilih tanggal" data-date-picker>
                                    </div>
                                    <div class="col-md-6 col-lg-4 fv-row">
                                        <label class="fw-semibold fs-6 mb-2" for="event_committee_ends_on">Akhir Masa Panitia</label>
                                        <input type="text" name="committee_ends_on" id="event_committee_ends_on" class="form-control form-control-solid" placeholder="Pilih tanggal"
                                            data-date-picker data-date-min="#event_committee_starts_on">
                                    </div>
                                </div>

                                <div class="ui-stepper-section-title">Tempat</div>
                                <div class="ui-stepper-section-hint">Pilih dari daftar venue, atau tulis lokasinya bila tidak ada di daftar.</div>
                                <div class="row g-7">
                                    <div class="col-md-6 fv-row">
                                        <label class="fw-semibold fs-6 mb-2" for="event_venue">Venue</label>
                                        <select name="venue_id" id="event_venue" class="form-select form-select-solid" data-control="select2"
                                            data-dropdown-parent="#kt_modal_event" data-placeholder="Pilih venue" data-allow-clear="true">
                                            <option></option>
                                            @foreach ($venueOptions as $venue)
                                                <option value="{{ $venue->id }}">{{ $venue->name }}{{ $venue->capacity ? ' ('.number_format($venue->capacity, 0, ',', '.').' orang)' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 fv-row">
                                        <label class="fw-semibold fs-6 mb-2" for="event_location_text">Lokasi Lain</label>
                                        <input type="text" name="location_text" id="event_location_text" class="form-control form-control-solid" maxlength="255" placeholder="Nama tempat dan alamat">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Langkah 3: Penanggung jawab dan target --}}
                        <div class="flex-column" data-kt-stepper-element="content">
                            <div class="w-100">
                                <div class="ui-stepper-section-title">Penanggung Jawab</div>
                                <div class="ui-stepper-section-hint">Pihak organisasi yang menaungi event. Ketua panitia ditetapkan di halaman Panitia.</div>
                                <div class="row g-7 mb-9">
                                    <div class="col-md-6 fv-row">
                                        <label class="fw-semibold fs-6 mb-2" for="event_unit">Unit Penanggung Jawab</label>
                                        <select name="organization_unit_id" id="event_unit" class="form-select form-select-solid" data-control="select2"
                                            data-dropdown-parent="#kt_modal_event" data-placeholder="Pilih unit" data-allow-clear="true">
                                            <option></option>
                                            @foreach ($unitOptions as $unit)
                                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 fv-row">
                                        <label class="fw-semibold fs-6 mb-2" for="event_responsible">Penanggung Jawab Organisasi</label>
                                        <select name="responsible_user_id" id="event_responsible" class="form-select form-select-solid"
                                            data-select-remote="{{ isset($event) ? route('events.event_people_options', $event) : route('events.people_options') }}"
                                            data-placeholder="Ketik nama" data-allow-clear="true">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="ui-stepper-section-title">Target</div>
                                <div class="ui-stepper-section-hint">Perkiraan awal. Anggaran resmi disusun lewat RAB.</div>
                                <div class="row g-7">
                                    <div class="col-sm-6 col-lg-3 fv-row">
                                        <label class="fw-semibold fs-6 mb-2" for="event_target_participants">Target Peserta</label>
                                        <div class="input-group input-group-solid">
                                            <input type="text" name="target_participants" id="event_target_participants" class="form-control form-control-solid text-end"
                                                inputmode="numeric" maxlength="9" placeholder="0" data-number-input>
                                            <span class="input-group-text">orang</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-4 fv-row">
                                        <label class="fw-semibold fs-6 mb-2" for="event_budget">Target Anggaran</label>
                                        <div class="input-group input-group-solid">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" name="budget_target_amount" id="event_budget" class="form-control form-control-solid text-end"
                                                inputmode="numeric" maxlength="22" placeholder="0" data-number-input>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Langkah 4: Deskripsi --}}
                        <div class="flex-column" data-kt-stepper-element="content">
                            <div class="w-100">
                                <div class="ui-stepper-section-title">Deskripsi dan Latar Belakang</div>
                                <div class="ui-stepper-section-hint">Dipakai sebagai bahan narasi proposal. Boleh diisi belakangan.</div>
                                <div class="fv-row mb-7">
                                    <label class="fw-semibold fs-6 mb-2" for="event_description">Deskripsi</label>
                                    <textarea name="description" id="event_description" class="form-control form-control-solid" rows="4" maxlength="5000"
                                        placeholder="Gambaran singkat kegiatan"></textarea>
                                </div>
                                <div class="fv-row">
                                    <label class="fw-semibold fs-6 mb-2" for="event_background">Latar Belakang</label>
                                    <textarea name="background" id="event_background" class="form-control form-control-solid" rows="6" maxlength="5000"
                                        placeholder="Alasan dan konteks kegiatan diadakan"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Langkah 5: Tujuan dan indikator --}}
                        <div class="flex-column" data-kt-stepper-element="content">
                            <div class="w-100">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="ui-stepper-section-title">Tujuan, Sasaran, dan Indikator</div>
                                        <div class="ui-stepper-section-hint">Dipakai lagi saat menyusun proposal dan saat evaluasi. Boleh diisi belakangan.</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary" data-objective-add>
                                        <i class="ki-duotone ki-plus fs-3"><span class="path1"></span><span class="path2"></span></i> Tambah Baris
                                    </button>
                                </div>
                                <div class="row g-3 d-none d-md-flex text-gray-500 fw-bold fs-7 text-uppercase mb-2" data-objectives-header>
                                    <div class="col-md-3">Jenis</div>
                                    <div class="col-md-5">Uraian</div>
                                    <div class="col-md-3">Target</div>
                                </div>
                                <div data-objectives></div>
                                <div class="text-muted fs-7 d-none" data-objectives-empty>Belum ada tujuan atau indikator.</div>

                                <template data-objective-template>
                                    <div class="row g-3 align-items-start mb-4" data-objective-row>
                                        <div class="col-md-3">
                                            <select class="form-select form-select-solid" data-objective-field="type" aria-label="Jenis" data-control="select2" data-hide-search="true">
                                                @foreach ($objectiveTypes as $type)
                                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-5 fv-row">
                                            <input type="text" class="form-control form-control-solid" data-objective-field="description" maxlength="1000" placeholder="Uraian" aria-label="Uraian">
                                        </div>
                                        <div class="col-9 col-md-3 fv-row">
                                            <input type="text" class="form-control form-control-solid" data-objective-field="target" maxlength="100" placeholder="Contoh: 300 orang" aria-label="Target">
                                        </div>
                                        <div class="col-3 col-md-1 text-end">
                                            <button type="button" class="btn btn-icon btn-light" data-objective-remove title="Hapus baris" aria-label="Hapus baris"><i class="las la-trash fs-2"></i></button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer d-flex flex-stack">
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-light-primary" data-kt-stepper-action="previous">
                                <i class="ki-duotone ki-arrow-left fs-3"><span class="path1"></span><span class="path2"></span></i> Kembali
                            </button>
                        </div>
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-primary" data-kt-stepper-action="next">
                                Lanjut <i class="ki-duotone ki-arrow-right fs-3 ms-1 me-0"><span class="path1"></span><span class="path2"></span></i>
                            </button>
                            <button type="submit" class="btn btn-primary" data-kt-stepper-action="submit" data-event-form-submit>
                                <span class="indicator-label">Simpan</span>
                                <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
