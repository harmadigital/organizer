<?php

namespace App\Http\Controllers;

use App\Services\NotificationInbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * The lonceng: a person's own notices from every event they belong to.
 */
class NotificationController extends Controller
{
    public function __construct(private NotificationInbox $inbox) {}

    /**
     * "Belum dibaca" by default; ?tampil=semua shows read notices too.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $showAll = $request->query('tampil') === 'semua';
        $notices = $this->inbox->visibleTo($user)
            ->when(! $showAll, fn ($query) => $query->whereNull('read_at'))
            ->simplePaginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notices' => $notices,
            'items' => collect($notices->items())->map(fn (DatabaseNotification $notice): array => $this->inbox->present($notice))->groupBy('day'),
            'showAll' => $showAll,
            'unread' => $this->inbox->unreadCount($user),
        ]);
    }

    /**
     * Mark the notice read and go to the page it is about. Only a path on this site is
     * followed; anything else falls back to the list.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $notice = $this->inbox->visibleTo($request->user())->whereKey($notification)->firstOrFail();
        $notice->markAsRead();

        $path = (string) ($notice->data['path'] ?? '');

        return preg_match('#^/(?![/\\\\])#', $path) === 1
            ? redirect()->to($path)
            : redirect()->route('notifications.index');
    }

    public function readAll(Request $request): JsonResponse
    {
        $this->inbox->markAllRead($request->user());

        return $this->jsonSuccess(['unread' => 0]);
    }
}
