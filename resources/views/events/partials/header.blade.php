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
        @can('viewWork', $event)
            <a @class(['nav-link', 'active' => request()->routeIs('events.work', 'events.tasks.*')]) href="{{ route('events.work', $event) }}">Program &amp; Tugas</a>
        @endcan
    </div>
</x-ui.page-header>

@include('events.partials.locked-notice')
