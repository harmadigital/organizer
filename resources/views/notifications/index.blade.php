@extends('layouts.main_layout', ['title' => 'Notifikasi', 'breadcrum' => ['Notifikasi']])

@section('content')
    <div class="ui-page" data-notifications-page>
        <x-ui.page-header title="Notifikasi" subtitle="Pemberitahuan untuk Anda dari semua event yang Anda ikuti">
            <x-slot:actions>
                @if ($unread > 0)
                    <button type="button" class="btn btn-light-primary" data-notifications-read-all data-url="{{ route('notifications.read_all') }}">Tandai semua dibaca</button>
                @endif
            </x-slot:actions>
            <div class="ui-subnav nav">
                <a @class(['nav-link', 'active' => ! $showAll]) href="{{ route('notifications.index') }}">Belum dibaca ({{ $unread }})</a>
                <a @class(['nav-link', 'active' => $showAll]) href="{{ route('notifications.index', ['tampil' => 'semua']) }}">Semua</a>
            </div>
        </x-ui.page-header>

        <div class="card ui-panel">
            <div class="card-body">
                @forelse ($items as $day => $dayNotices)
                    <div class="ui-info-label mb-2 {{ $loop->first ? '' : 'mt-6' }}">{{ $day }}</div>
                    <div class="ui-notice-list">
                        @foreach ($dayNotices as $notice)
                            @include('notifications.partials.item', ['notice' => $notice])
                        @endforeach
                    </div>
                @empty
                    <div class="ui-empty-state" data-notifications-empty>
                        <i class="ki-duotone ki-notification-on fs-3x text-muted mb-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                        <div class="fw-semibold text-gray-800 fs-5">{{ $showAll ? 'Belum ada notifikasi.' : 'Semua notifikasi sudah dibaca.' }}</div>
                        <div class="text-muted fs-7 mt-1">Anda akan diberi tahu bila ada tugas untuk Anda, rapat baru, atau pengingat tenggat dan rapat.</div>
                    </div>
                @endforelse

                @if ($notices->hasPages())
                    <div class="d-flex justify-content-between mt-6">
                        @if ($notices->previousPageUrl())
                            <a href="{{ $notices->previousPageUrl() }}" class="btn btn-sm btn-light">Sebelumnya</a>
                        @else
                            <span></span>
                        @endif
                        @if ($notices->nextPageUrl())
                            <a href="{{ $notices->nextPageUrl() }}" class="btn btn-sm btn-light">Berikutnya</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
