@props(['title', 'subtitle' => null, 'guide' => null])

<div {{ $attributes->class(['ui-page-header']) }}>
    <div class="ui-page-heading d-flex flex-nowrap justify-content-between align-items-start w-100">
        <div>
            <h2 class="ui-page-title">{{ $title }}</h2>
            @if ($subtitle)
                <p class="ui-page-subtitle">{{ $subtitle }}</p>
            @endif
            @isset($meta)
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">{{ $meta }}</div>
            @endisset
        </div>
        <div class="d-flex flex-shrink-0 flex-wrap justify-content-end align-items-center gap-3">
            {{ $actions ?? '' }}
            @if ($guide)
                <button type="button" class="btn btn-icon btn-outline btn-outline-primary" data-bs-toggle="modal"
                    data-bs-target="{{ $guide }}" title="Panduan" aria-label="Buka panduan halaman ini">
                    <i class="ki-duotone ki-information-4 fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                </button>
            @endif
        </div>
    </div>
    {{ $slot }}
</div>
