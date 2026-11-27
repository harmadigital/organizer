{{-- Satu pemberitahuan, dipakai di lonceng dan halaman Notifikasi. $notice dari NotificationInbox::present(). --}}
<a href="{{ route('notifications.open', $notice['id']) }}" @class(['ui-notice', 'is-unread' => $notice['is_unread']]) data-notice>
    <span class="ui-notice-icon bg-light-{{ $notice['kind']->tone() }} text-{{ $notice['kind']->tone() }}">
        <i class="ki-duotone {{ $notice['kind']->icon() }} fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
    </span>
    <span class="ui-notice-body">
        <span class="ui-notice-title">{{ $notice['title'] }}</span>
        @if ($notice['line'])
            <span class="ui-notice-line">{{ $notice['line'] }}</span>
        @endif
        <span class="ui-notice-meta">{{ $notice['event_name'] }} · {{ $notice['when'] }}</span>
    </span>
    @if ($notice['is_unread'])
        <span class="ui-notice-dot" aria-label="Belum dibaca"></span>
    @endif
</a>
