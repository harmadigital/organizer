<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureEventAccess
{
    /**
     * Someone with no access to the event gets "not found", so an event's existence
     * does not leak to people outside it. The event is shared with every view as the
     * current event context.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $event = $request->route('event');

        abort_unless($event instanceof Event && $request->user()?->can('view', $event), 404);

        View::share('currentEvent', $event);

        return $next($request);
    }
}
