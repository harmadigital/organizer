{{-- Header bersama halaman sebuah event: identitas, status, aksi, dan tab. --}}
<x-ui.page-header :title="$event->name" :subtitle="$event->code.' · '.$event->dateRangeLabel()" guide="#kt_modal_guide">
    <x-slot:meta>
        <span class="badge badge-light-{{ $event->status->tone() }}">{{ $event->status->label() }}</span>
        <span class="badge badge-light-info">Fase: {{ $event->phase->label() }}</span>
        @if ($event->category)
            <span class="badge badge-light">{{ $event->category->name }}</span>
        @endif
    </x-slot:meta>
    <x-slot:actions>
        @isset($actionsView)
            @include($actionsView)
        @endisset
    </x-slot:actions>
    <div class="ui-subnav nav">
        <a @class(['nav-link', 'active' => request()->routeIs('events.overview')]) href="{{ route('events.overview', $event) }}">Ringkasan</a>
        @can('viewCommittee', $event)
            <a @class(['nav-link', 'active' => request()->routeIs('events.committee')]) href="{{ route('events.committee', $event) }}">Panitia</a>
        @endcan
    </div>
</x-ui.page-header>

@unless ($event->isEditable())
    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-5">
        <i class="ki-duotone ki-information-5 fs-2tx text-warning me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
        <div class="fw-semibold text-gray-700 fs-6">Event ini berstatus <strong>{{ $event->status->label() }}</strong>, sehingga data dan susunan panitianya tidak dapat diubah.</div>
    </div>
@endunless
