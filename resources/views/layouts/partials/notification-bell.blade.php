{{-- Lonceng: pemberitahuan terbaru untuk orang yang masuk. Datanya dari view composer di AppServiceProvider. --}}
<div class="app-navbar-item ms-1 ms-md-4" data-notification-bell>
    <button type="button" @class(['btn btn-icon btn-sm btn-primary ui-bell-button position-relative', 'has-unread' => $bellUnread > 0])
        data-kt-menu-trigger="{default: 'click'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end"
        title="Notifikasi" aria-label="Notifikasi{{ $bellUnread > 0 ? ', '.$bellUnread.' belum dibaca' : '' }}">
        <i class="ki-duotone ki-notification-on"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
        @if ($bellUnread > 0)
            <span class="ui-bell-count" data-bell-count>{{ $bellUnread > 9 ? '9+' : $bellUnread }}</span>
        @endif
    </button>
    <div class="menu menu-sub menu-sub-dropdown menu-column ui-bell-menu" data-kt-menu="true">
        <div class="d-flex align-items-center justify-content-between px-5 py-4 border-bottom">
            <span class="fw-bold text-gray-900 fs-5">Notifikasi</span>
            @if ($bellUnread > 0)
                <button type="button" class="btn btn-link btn-sm p-0 fw-semibold" data-notifications-read-all data-url="{{ route('notifications.read_all') }}">Tandai semua dibaca</button>
            @endif
        </div>
        <div class="ui-bell-list">
            @forelse ($bellNotices as $notice)
                @include('notifications.partials.item', ['notice' => $notice])
            @empty
                <div class="px-5 py-8 text-center">
                    <div class="fw-semibold text-gray-800">Belum ada notifikasi</div>
                    <div class="text-muted fs-7 mt-1">Anda akan diberi tahu bila ada tugas untuk Anda, rapat baru, atau pengingat tenggat.</div>
                </div>
            @endforelse
        </div>
        <div class="border-top text-center py-3">
            <a href="{{ route('notifications.index') }}" class="fw-semibold fs-7">Lihat semua notifikasi</a>
        </div>
    </div>
</div>
