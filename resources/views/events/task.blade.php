@extends('layouts.main_layout', ['title' => $task->title.' · '.$event->name, 'breadcrum' => ['Event', $event->code, 'Program & Tugas', 'Detail Tugas']])

@php($dateLabel = fn ($date) => $date?->locale('id')->translatedFormat('j M Y'))
@php($statusLabel = fn ($status) => '<span class="badge badge-light-'.$status->tone().'">'.e($status->label()).'</span>')
@php($defaultTab = $task->status === \App\Enums\TaskStatus::Completed ? 'hasil' : 'rincian')

@section('content')
    <div class="ui-page" data-task-page
        data-show-url="{{ route('events.tasks.show', [$event, $task]) }}"
        data-update-url="{{ route('events.tasks.update', [$event, $task]) }}"
        data-insert-url="{{ route('events.tasks.insert', $event) }}"
        data-complete-url="{{ route('events.tasks.complete', [$event, $task]) }}"
        data-task-id="{{ $task->id }}"
        data-task-title="{{ $task->title }}">
        {{-- Halaman tugas tidak memakai header event: cukup jalan kembali ke daftar tugas. --}}
        <div>
            <a href="{{ route('events.work', $event) }}" class="btn btn-sm btn-light" data-back-to-work>
                <i class="ki-duotone ki-arrow-left fs-4"><span class="path1"></span><span class="path2"></span></i> Kembali ke Program &amp; Tugas
            </a>
        </div>

        @include('events.partials.locked-notice')

        <div class="card ui-panel" data-task-detail>
            <div class="card-body pb-0">
                <div class="d-flex flex-wrap justify-content-between gap-4 mb-6">
                    <div>
                        @if ($task->parent)
                            <a href="{{ route('events.tasks.detail', [$event, $task->parent]) }}" class="text-muted fs-7 text-hover-primary d-inline-block mb-1">Subtugas dari: {{ $task->parent->title }}</a>
                        @endif
                        <h2 class="fw-bold text-gray-900 mb-3" data-task-heading>{{ $task->title }}</h2>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge badge-light-{{ $task->status->tone() }}" data-task-status="{{ $task->status->value }}">{{ $task->status->label() }}</span>
                            <span class="badge badge-light-{{ $task->priority->tone() }}">Prioritas {{ $task->priority->label() }}</span>
                            @if ($task->isOverdue($today))
                                <span class="badge badge-danger">Terlambat</span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-start gap-2" data-task-actions>
                        @if ($canComplete)
                            <button type="button" class="btn btn-sm btn-success" data-task-complete>
                                <i class="ki-duotone ki-check fs-4"></i> Tandai Selesai
                            </button>
                        @endif
                        @if ($canManage)
                            <button type="button" class="btn btn-sm btn-light" data-task-edit>
                                <i class="ki-duotone ki-pencil fs-4"><span class="path1"></span><span class="path2"></span></i> Ubah
                            </button>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-5 text-gray-700 fs-6 mt-n2 mb-2" data-task-summary>
                    <span><span class="text-muted">Seksi:</span> {{ $task->division?->name ?? 'Pengurus Inti' }}</span>
                    <span><span class="text-muted">Penanggung jawab:</span> {{ $task->owner?->user->name ?? '-' }}</span>
                    <span><span class="text-muted">Tenggat:</span> <span @class(['ui-overdue' => $task->isOverdue($today)])>{{ $dateLabel($task->due_on) ?? '-' }}</span></span>
                </div>

                <ul class="nav nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mt-4" role="tablist" data-task-tabs>
                    @foreach ([
                        ['rincian', 'Rincian', null],
                        ['hasil', 'Hasil & Dokumentasi', $task->documents->count() ?: null],
                        ['komentar', 'Komentar', $task->comments->count() ?: null],
                        ['riwayat', 'Riwayat', null],
                    ] as [$key, $label, $count])
                        <li class="nav-item" role="presentation">
                            <button type="button" @class(['nav-link text-active-primary pb-4', 'active' => $defaultTab === $key]) data-bs-toggle="tab" data-bs-target="#tab_{{ $key }}"
                                data-tab="{{ $key }}" role="tab" aria-controls="tab_{{ $key }}" aria-selected="{{ $defaultTab === $key ? 'true' : 'false' }}">
                                {{ $label }}
                                @if ($count)
                                    <span class="badge badge-light-primary ms-1">{{ $count }}</span>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="tab-content">
            <div @class(['tab-pane fade', 'show active' => $defaultTab === 'rincian']) id="tab_rincian" role="tabpanel">
                <div class="ui-detail-stack">
                    <div class="card ui-panel">
                        <div class="card-body">
                            <div class="row g-7">
                                @foreach ([
                                    ['Seksi', $task->division?->name ?? 'Pengurus Inti'],
                                    ['Program Kerja', $task->program?->title],
                                    ['Penanggung Jawab', $task->owner?->user->name],
                                    ['Anggota Tugas', $task->assignees->map(fn ($assignee) => $assignee->user->name)->implode(', ') ?: null],
                                    ['Mulai', $dateLabel($task->starts_on)],
                                    ['Tenggat', $dateLabel($task->due_on)],
                                    ['Besar pekerjaan', \App\Enums\TaskSize::tryFrom($task->weight)?->label()],
                                    ['Diselesaikan', $task->completed_at ? app(\App\Services\DisplayTime::class)->format($task->completed_at) : null],
                                ] as [$label, $value])
                                    <div class="col-md-6 col-xl-3 ui-info-item">
                                        <span class="ui-info-label">{{ $label }}</span>
                                        <span class="ui-info-value">{{ $value ?? '-' }}</span>
                                    </div>
                                @endforeach
                            </div>

                            @if ($task->description)
                                <div class="separator my-6"></div>
                                <div class="ui-info-item">
                                    <span class="ui-info-label">Keterangan</span>
                                    <div class="text-gray-700 fs-6 ui-pre-line">{{ $task->description }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($task->parent_id === null && ($task->children->isNotEmpty() || $canCreateSubtask))
                        <div class="card ui-panel" data-subtasks>
                            <div class="card-header">
                                <div class="card-title flex-column align-items-start">
                                    <h3 class="mb-1">Subtugas</h3>
                                    @if ($subtaskProgress)
                                        <span class="ui-panel-description mt-0">{{ $subtaskProgress['percent'] === null ? 'Belum ada subtugas yang dihitung' : $subtaskProgress['percent'].'% pekerjaan subtugas selesai' }}.</span>
                                    @endif
                                </div>
                                @if ($canCreateSubtask)
                                    <div class="card-toolbar">
                                        <button type="button" class="btn btn-sm btn-light-primary" data-subtask-add>
                                            <i class="ki-duotone ki-plus fs-3"></i> Tambah Subtugas
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body pt-2">
                                @forelse ($task->children as $child)
                                    <div class="ui-check-row">
                                        {!! $statusLabel($child->status) !!}
                                        <a href="{{ route('events.tasks.detail', [$event, $child]) }}" class="ui-task-title flex-grow-1">{{ $child->title }}</a>
                                        <span class="text-muted fs-7">{{ $child->owner?->user->name ?? 'Belum ada penanggung jawab' }}</span>
                                        @if ($child->due_on)
                                            <span @class(['fs-7', 'ui-overdue' => $child->isOverdue($today), 'text-muted' => ! $child->isOverdue($today)])>{{ $dateLabel($child->due_on) }}</span>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-muted fs-6">Belum ada subtugas. Pecah tugas besar menjadi subtugas agar progresnya terlihat.</div>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    @if ($canManage || $task->dependencies->isNotEmpty() || $task->dependents->isNotEmpty())
                    <div class="card ui-panel" data-dependencies>
                        <div class="card-header">
                            <div class="card-title flex-column align-items-start">
                                <h3 class="mb-1">Prasyarat</h3>
                                <span class="ui-panel-description mt-0">Tugas ini baru dapat diselesaikan setelah tugas prasyaratnya selesai.</span>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            @if ($canManage)
                                <form class="d-flex flex-wrap gap-3 mb-5" data-dependency-form data-url="{{ route('events.tasks.dependencies.insert', [$event, $task]) }}" novalidate>
                                    <div class="flex-grow-1 fv-row" style="min-width:240px">
                                        <select name="depends_on_task_id" class="form-select form-select-solid" data-select-remote="{{ route('events.work.task_options', $event) }}" data-placeholder="Pilih tugas prasyarat" aria-label="Tugas prasyarat">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-light-primary" data-dependency-submit>
                                        <span class="indicator-label">Tambahkan</span>
                                        <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                </form>
                            @endif

                            @forelse ($task->dependencies as $dependency)
                                <div class="ui-check-row" data-dependency-row="{{ $dependency->id }}">
                                    {!! $statusLabel($dependency->status) !!}
                                    <a href="{{ route('events.tasks.detail', [$event, $dependency]) }}" class="ui-task-title flex-grow-1">{{ $dependency->title }}</a>
                                    @if ($canManage)
                                        <button type="button" class="btn btn-sm btn-icon btn-light-danger" data-dependency-remove="{{ route('events.tasks.dependencies.delete', [$event, $task, $dependency]) }}"
                                            title="Lepas prasyarat" aria-label="Lepas prasyarat {{ $dependency->title }}">
                                            <i class="las la-times fs-3"></i>
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <div class="text-muted fs-6">Tidak ada prasyarat.</div>
                            @endforelse

                            @if ($task->dependents->isNotEmpty())
                                <div class="ui-info-label mt-6 mb-2">Ditunggu oleh</div>
                                @foreach ($task->dependents as $dependent)
                                    <div class="ui-check-row">
                                        {!! $statusLabel($dependent->status) !!}
                                        <a href="{{ route('events.tasks.detail', [$event, $dependent]) }}" class="ui-task-title flex-grow-1">{{ $dependent->title }}</a>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div @class(['tab-pane fade', 'show active' => $defaultTab === 'hasil']) id="tab_hasil" role="tabpanel">
                @if ($canComplete)
                    <div class="notice d-flex flex-wrap align-items-center gap-4 bg-light-success rounded border-success border border-dashed p-5 mb-5">
                        <div class="flex-grow-1 fw-semibold text-gray-700 fs-6">Tugas ini belum selesai. Setelah pekerjaannya beres, tandai selesai lalu tulis hasilnya dan lampirkan foto bila ada.</div>
                        <button type="button" class="btn btn-sm btn-success" data-task-complete>
                            <i class="ki-duotone ki-check fs-4"></i> Tandai Selesai
                        </button>
                    </div>
                @endif
                    @php($photos = $task->documents->filter(fn ($document) => in_array($document->latestVersion?->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true)))
                    @php($files = $task->documents->diff($photos))
                    <div class="card ui-panel" data-results>
                        <div class="card-header">
                            <div class="card-title flex-column align-items-start">
                                <h3 class="mb-1">Hasil &amp; Dokumentasi</h3>
                                <span class="ui-panel-description mt-0">Hasil pekerjaan beserta foto dan dokumen pendukungnya.</span>
                            </div>
                            @if ($canWork && $task->result)
                                <div class="card-toolbar">
                                    <button type="button" class="btn btn-sm btn-light" data-result-edit>
                                        <i class="ki-duotone ki-pencil fs-4"><span class="path1"></span><span class="path2"></span></i> Ubah Hasil
                                    </button>
                                </div>
                            @endif
                        </div>
                        <div class="card-body pt-2">
                            <div data-result-view>
                                @if ($task->result)
                                    <div class="text-gray-800 fs-6 ui-pre-line" data-result-text>{{ $task->result }}</div>
                                @else
                                    <div class="text-muted fs-6">Belum ada hasil. Hasil diisi saat tugas ditandai selesai.</div>
                                @endif
                            </div>

                            @if ($canWork)
                                <form class="d-none" data-result-form data-url="{{ route('events.tasks.result', [$event, $task]) }}" novalidate>
                                    <div class="fv-row mb-3">
                                        <label class="required fw-semibold fs-6 mb-2" for="result_text">Hasil Pekerjaan</label>
                                        <textarea name="result" id="result_text" class="form-control form-control-solid" rows="5" maxlength="5000">{{ $task->result }}</textarea>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-sm btn-light" data-result-cancel>Batal</button>
                                        <button type="submit" class="btn btn-sm btn-primary" data-result-submit>
                                            <span class="indicator-label">Simpan Hasil</span>
                                            <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                        </button>
                                    </div>
                                </form>
                            @endif

                            @if ($photos->isNotEmpty())
                                <div class="ui-info-label mt-7 mb-3">Foto</div>
                                <div class="ui-photo-grid">
                                    @foreach ($photos as $document)
                                        @php($downloadUrl = route('events.tasks.documents.download', [$event, $task, $document]))
                                        <div class="ui-photo" data-document-row="{{ $document->id }}">
                                            <a href="{{ $downloadUrl }}?preview=1" target="_blank" rel="noopener" title="{{ $document->title }}">
                                                <img src="{{ $downloadUrl }}?preview=1" alt="{{ $document->title }}" loading="lazy">
                                            </a>
                                            @if ($event->isEditable() && ($canManage || ($canWork && $document->owner_user_id === auth()->id())))
                                                <button type="button" class="btn btn-sm btn-icon btn-danger ui-photo-remove" data-document-remove="{{ route('events.tasks.documents.delete', [$event, $task, $document]) }}"
                                                    data-title="{{ $document->title }}" title="Hapus foto" aria-label="Hapus foto {{ $document->title }}">
                                                    <i class="las la-times fs-4"></i>
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($files->isNotEmpty())
                                <div class="ui-info-label mt-7 mb-1">Dokumen</div>
                                @foreach ($files as $document)
                                    <div class="ui-file-row" data-document-row="{{ $document->id }}">
                                        <i class="ki-duotone ki-document fs-2x text-primary"><span class="path1"></span><span class="path2"></span></i>
                                        <div class="flex-grow-1 min-w-0">
                                            @php($fileUrl = route('events.tasks.documents.download', [$event, $task, $document]))
                                            <a href="{{ $document->isPdf() ? $fileUrl.'?preview=1' : $fileUrl }}" @if ($document->isPdf()) target="_blank" rel="noopener" @endif class="ui-task-title d-block text-truncate">{{ $document->title }}</a>
                                            <div class="text-muted fs-8 text-truncate">
                                                {{ $document->latestVersion?->original_name }} · {{ number_format(($document->latestVersion?->size_bytes ?? 0) / 1024, 0, ',', '.') }} KB · {{ $document->owner?->name }}
                                            </div>
                                        </div>
                                        @if ($document->isPdf())
                                            <a href="{{ $fileUrl }}?preview=1" target="_blank" rel="noopener" class="btn btn-sm btn-light-primary flex-shrink-0" data-document-preview>Lihat</a>
                                        @endif
                                        <a href="{{ $fileUrl }}" class="btn btn-sm btn-icon btn-light flex-shrink-0" title="Unduh" aria-label="Unduh {{ $document->title }}"><i class="las la-download fs-3"></i></a>
                                        @if ($event->isEditable() && ($canManage || ($canWork && $document->owner_user_id === auth()->id())))
                                            <button type="button" class="btn btn-sm btn-icon btn-light-danger flex-shrink-0" data-document-remove="{{ route('events.tasks.documents.delete', [$event, $task, $document]) }}"
                                                data-title="{{ $document->title }}" title="Hapus file" aria-label="Hapus file {{ $document->title }}">
                                                <i class="las la-times fs-3"></i>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            @if ($canWork)
                                <form class="d-flex flex-wrap align-items-start gap-3 mt-7" data-document-form data-url="{{ route('events.tasks.documents.insert', [$event, $task]) }}" novalidate>
                                    <div class="flex-grow-1 fv-row" style="min-width:240px">
                                        <input type="file" name="files[]" id="document_files" class="form-control form-control-solid" multiple aria-label="Tambah foto atau dokumen"
                                            accept="{{ collect($allowedExtensions)->map(fn ($extension) => '.'.$extension)->implode(',') }}">
                                        <div class="fs-7 text-muted mt-2">Foto (JPG/PNG/WEBP), PDF, dokumen Office, TXT, atau CSV. Maksimal {{ $maxFiles }} file, masing-masing {{ intdiv($maxUploadKilobytes, 1024) }} MB.</div>
                                    </div>
                                    <button type="submit" class="btn btn-light-primary" data-document-submit>
                                        <span class="indicator-label">Unggah</span>
                                        <span class="indicator-progress">Mengunggah...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                </form>
                            @elseif ($task->documents->isEmpty())
                                <div class="text-muted fs-6 mt-5">Belum ada foto atau dokumen.</div>
                            @endif
                        </div>
                    </div>
            </div>

            <div @class(['tab-pane fade', 'show active' => $defaultTab === 'komentar']) id="tab_komentar" role="tabpanel">
                    <div class="card ui-panel" data-comments>
                        <div class="card-header">
                            <div class="card-title flex-column align-items-start">
                                <h3 class="mb-1">Komentar <span class="text-muted fs-6 fw-semibold">({{ $task->comments->count() }})</span></h3>
                                <span class="ui-panel-description mt-0">Semua panitia dapat memberi masukan atau pertanyaan di sini.</span>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            @forelse ($task->comments as $comment)
                                <div class="ui-comment" data-comment-row="{{ $comment->id }}">
                                    <div class="symbol symbol-35px symbol-circle flex-shrink-0">
                                        <span class="symbol-label bg-light-primary text-primary fw-bold">{{ mb_strtoupper(mb_substr($comment->author?->name ?? '?', 0, 1)) }}</span>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap align-items-baseline gap-2">
                                            <span class="fw-semibold text-gray-800">{{ $comment->author?->name ?? 'Pengguna dihapus' }}</span>
                                            <span class="text-muted fs-8">{{ app(\App\Services\DisplayTime::class)->format($comment->created_at) }}</span>
                                        </div>
                                        <div class="text-gray-700 fs-6 ui-pre-line mt-1">{{ $comment->body }}</div>
                                    </div>
                                    @if ($event->isEditable() && ($canManage || $comment->user_id === auth()->id()))
                                        <button type="button" class="btn btn-sm btn-icon btn-light flex-shrink-0" data-comment-remove="{{ route('events.tasks.comments.delete', [$event, $task, $comment]) }}"
                                            title="Hapus komentar" aria-label="Hapus komentar dari {{ $comment->author?->name }}">
                                            <i class="las la-times fs-4"></i>
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <div class="text-muted fs-6 mb-2">Belum ada komentar.</div>
                            @endforelse

                            @if ($canComment)
                                <form class="mt-5" data-comment-form data-url="{{ route('events.tasks.comments.insert', [$event, $task]) }}" novalidate>
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
                        @if ($task->statusChanges->isEmpty())
                            <div class="text-muted fs-6">Status belum pernah berubah sejak tugas dibuat.</div>
                        @else
                            <div class="ui-timeline">
                                @foreach ($task->statusChanges as $change)
                                    <div class="ui-timeline-item">
                                        <div class="fw-semibold text-gray-800 fs-6">{{ $change->action->label() }}: {{ $change->from_status->label() }} → {{ $change->to_status->label() }}</div>
                                        <div class="text-muted fs-7">{{ app(\App\Services\DisplayTime::class)->format($change->created_at) }} · {{ $change->actor?->name ?? 'Sistem' }}</div>
                                        @if ($change->note)
                                            <div class="text-gray-700 fs-7 mt-1 ui-pre-line">{{ $change->note }}</div>
                                        @endif
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
        @include('events.partials.task-form-modal')
    @endif

    @include('events.partials.task-complete-modal')
@endsection

@section('script')
    <script src="{{ asset('assets/js/events/task-form.js') }}"></script>
    <script src="{{ asset('assets/js/events/task-complete.js') }}"></script>
    <script src="{{ asset('assets/js/events/task.js') }}"></script>
@endsection
