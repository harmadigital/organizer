@extends('layouts.main_layout', ['title' => 'Media & Dokumen · '.$event->name, 'breadcrum' => ['Event', $event->code, 'Media & Dokumen']])

@section('content')
    <div class="ui-page" data-library-page data-insert-url="{{ route('events.library.insert', $event) }}">
        @include('events.partials.header')

        <div class="card ui-panel">
            <div class="card-header">
                <div class="card-title flex-column align-items-start">
                    <h3 class="mb-1">Media &amp; Dokumen</h3>
                    <span class="ui-panel-description mt-0">Foto, dokumen, dan link yang dibagikan panitia. Semua panitia dapat melihat dan menambahkan.</span>
                </div>
                @if ($canAdd)
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-primary" data-library-add>
                            <i class="ki-duotone ki-plus fs-3"></i> Tambah
                        </button>
                    </div>
                @endif
            </div>
            <div class="card-body pt-5">
                <div class="d-flex flex-wrap align-items-center gap-3 mb-6">
                    <div class="btn-group" role="group" aria-label="Saring jenis" data-library-kinds>
                        @foreach (['all' => 'Semua', 'photo' => 'Foto', 'document' => 'Dokumen', 'link' => 'Link'] as $kind => $label)
                            <button type="button" @class(['btn btn-sm btn-light-primary', 'active' => $kind === 'all']) data-library-kind="{{ $kind }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <div style="min-width:220px">
                        <select class="form-select form-select-sm form-select-solid" data-control="select2" data-allow-clear="true" data-placeholder="Semua seksi" data-library-division aria-label="Saring seksi">
                            <option value=""></option>
                            <option value="none">Pengurus Inti</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="position-relative ms-md-auto" style="width:240px;max-width:100%">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-4"><span class="path1"></span><span class="path2"></span></i>
                        <input type="text" class="form-control form-control-sm form-control-solid ps-12" placeholder="Cari judul" data-library-search aria-label="Cari judul">
                    </div>
                </div>

                <div class="ui-library-grid" data-library-grid>
                    @foreach ($items as $item)
                        @php($kind = $item->isLink() ? 'link' : ($item->isImage() ? 'photo' : 'document'))
                        @php($downloadUrl = $item->isLink() ? null : route('events.library.download', [$event, $item]))
                        <div class="ui-library-item" data-library-item="{{ $item->id }}" data-kind="{{ $kind }}" data-division="{{ $item->division_id ?? 'none' }}" data-title="{{ mb_strtolower($item->title) }}">
                            @if ($kind === 'photo')
                                <a href="{{ $downloadUrl }}?preview=1" target="_blank" rel="noopener" class="ui-library-thumb" title="{{ $item->title }}">
                                    <img src="{{ $downloadUrl }}?preview=1" alt="{{ $item->title }}" loading="lazy">
                                </a>
                            @elseif ($kind === 'document')
                                <a href="{{ $item->isPdf() ? $downloadUrl.'?preview=1' : $downloadUrl }}" @if ($item->isPdf()) target="_blank" rel="noopener" @endif
                                    class="ui-library-thumb ui-library-thumb--document" title="{{ $item->isPdf() ? 'Lihat' : 'Unduh' }} {{ $item->title }}">
                                    <i class="ki-duotone ki-document fs-3x text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <span class="ui-library-ext">{{ strtoupper(pathinfo($item->latestVersion?->original_name ?? '', PATHINFO_EXTENSION)) }}</span>
                                </a>
                            @else
                                <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="ui-library-thumb ui-library-thumb--link" title="Buka {{ $item->title }}">
                                    <i class="ki-duotone ki-fasten fs-3x text-info"><span class="path1"></span><span class="path2"></span></i>
                                    <span class="ui-library-ext">{{ parse_url($item->url, PHP_URL_HOST) }}</span>
                                </a>
                            @endif
                            <div class="ui-library-body">
                                <div class="ui-task-title text-truncate" title="{{ $item->title }}">{{ $item->title }}</div>
                                <div class="text-muted fs-8 text-truncate">{{ $item->division?->name ?? 'Pengurus Inti' }} · {{ $item->owner?->name ?? '-' }}</div>
                                <div class="text-muted fs-8">{{ $time->format($item->created_at, 'j M Y') }}</div>
                            </div>
                            <div class="ui-library-actions">
                                @if ($kind === 'link')
                                    <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light-primary flex-grow-1">Buka link</a>
                                @else
                                    @if ($item->canPreview())
                                        <a href="{{ $downloadUrl }}?preview=1" target="_blank" rel="noopener" class="btn btn-sm btn-light-primary flex-grow-1" data-library-preview>Lihat</a>
                                    @endif
                                    <a href="{{ $downloadUrl }}" @class(['btn btn-sm flex-grow-1', 'btn-light' => $item->canPreview(), 'btn-light-primary' => ! $item->canPreview()]) data-library-download>Unduh</a>
                                @endif
                                @if ($canRemove[$item->id] ?? false)
                                    <button type="button" class="btn btn-sm btn-icon btn-light-danger" data-library-remove="{{ route('events.library.delete', [$event, $item]) }}" data-title="{{ $item->title }}"
                                        title="Hapus" aria-label="Hapus {{ $item->title }}">
                                        <i class="las la-trash fs-4"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div @class(['ui-empty-state', 'd-none' => $items->isNotEmpty()]) data-library-empty>
                    <i class="ki-duotone ki-picture fs-3x text-muted mb-3"><span class="path1"></span><span class="path2"></span></i>
                    <div class="fw-semibold text-gray-800 fs-5">Belum ada media atau dokumen</div>
                    <div class="text-muted fs-7 mt-1">Bagikan foto contoh, draf materi, atau link referensi agar seluruh panitia bisa melihatnya.</div>
                </div>
                <div class="ui-empty-state d-none" data-library-no-match>
                    <div class="fw-semibold text-gray-800 fs-6">Tidak ada yang cocok dengan saringan.</div>
                </div>
            </div>
        </div>
    </div>

    @if ($canAdd)
        <div class="modal fade" id="kt_modal_library" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_library_title">
            <div class="modal-dialog modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="kt_modal_library_title">Tambah ke Media &amp; Dokumen</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
                    </div>
                    <form class="form d-flex flex-column overflow-hidden" autocomplete="off" novalidate>
                        <div class="modal-body overflow-auto">
                            <div class="ui-choice-group ui-choice-group--two mb-6" role="radiogroup" aria-label="Jenis">
                                <label class="ui-choice">
                                    <input type="radio" name="kind" value="file" checked>
                                    <span class="ui-choice-body"><span class="ui-choice-title">Unggah foto / dokumen</span><span class="ui-choice-hint">JPG, PNG, WEBP, PDF, Word, Excel, PowerPoint</span></span>
                                </label>
                                <label class="ui-choice">
                                    <input type="radio" name="kind" value="link">
                                    <span class="ui-choice-body"><span class="ui-choice-title">Tambah link</span><span class="ui-choice-hint">Google Drive, Docs, YouTube, atau situs lain</span></span>
                                </label>
                            </div>
                            <div class="row g-6 mb-6">
                                <div class="col-md-7 fv-row">
                                    <label class="required fw-semibold fs-6 mb-2" for="library_title">Judul</label>
                                    <input type="text" name="title" id="library_title" class="form-control form-control-solid" maxlength="200" placeholder="Contoh: Contoh dekorasi panggung">
                                </div>
                                <div class="col-md-5 fv-row">
                                    <label class="fw-semibold fs-6 mb-2" for="library_division">Seksi</label>
                                    <select name="division_id" id="library_division" class="form-select form-select-solid" data-control="select2" data-allow-clear="true" data-placeholder="Pengurus Inti" data-default="{{ $defaultDivisionId }}">
                                        <option value=""></option>
                                        @foreach ($divisions as $division)
                                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="fv-row" data-library-files-row>
                                <label class="required fw-semibold fs-6 mb-2" for="library_files">File</label>
                                <input type="file" name="files[]" id="library_files" class="form-control form-control-solid" multiple
                                    accept="{{ collect(\App\Http\Requests\Library\AddLibraryItemRequest::ALLOWED_EXTENSIONS)->map(fn ($extension) => '.'.$extension)->implode(',') }}">
                                <div class="fs-7 text-muted mt-2">Boleh beberapa file sekaligus (maksimal {{ \App\Http\Requests\Library\AddLibraryItemRequest::MAX_FILES }}), masing-masing maksimal 10 MB. Semua file memakai judul di atas.</div>
                            </div>
                            <div class="fv-row d-none" data-library-url-row>
                                <label class="required fw-semibold fs-6 mb-2" for="library_url">Link</label>
                                <input type="text" name="url" id="library_url" class="form-control form-control-solid" maxlength="2000" placeholder="https://...">
                            </div>
                        </div>
                        <div class="modal-footer flex-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" data-library-submit>
                                <span class="indicator-label">Simpan</span>
                                <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('script')
    <script src="{{ asset('assets/js/events/library.js') }}"></script>
@endsection
