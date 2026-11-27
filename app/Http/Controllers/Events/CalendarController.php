<?php

namespace App\Http\Controllers\Events;

use App\Actions\Calendar\DeleteCalendarEntry;
use App\Actions\Calendar\SaveCalendarEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\CalendarFeedRequest;
use App\Http\Requests\Calendar\SaveCalendarEntryRequest;
use App\Models\CalendarEntry;
use App\Models\Event;
use App\Services\CalendarAuthority;
use App\Services\CalendarFeed;
use App\Services\DisplayTime;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The "Kalender" tab: event days, meetings, task deadlines and the kegiatan committee
 * members add themselves. Items come from CalendarFeed; only kegiatan are stored here.
 */
class CalendarController extends Controller
{
    public function __construct(
        private CalendarAuthority $authority,
        private CalendarFeed $feed,
        private DisplayTime $time,
    ) {}

    public function index(Request $request, Event $event): View
    {
        Gate::authorize('viewCalendar', $event);

        return view('events.calendar', [
            'event' => $event,
            'divisions' => $event->divisions()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'canAdd' => $this->authority->canAdd($request->user(), $event),
            'defaultDivisionId' => $this->authority->defaultDivisionId($request->user(), $event),
            'today' => now($this->time->timezone())->toDateString(),
        ]);
    }

    public function feed(CalendarFeedRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('viewCalendar', $event);

        return $this->jsonSuccess([
            'items' => $this->feed->forEvent($request->user(), $event, $request->from(), $request->until(), $request->division()),
        ]);
    }

    public function store(SaveCalendarEntryRequest $request, Event $event, SaveCalendarEntry $save): JsonResponse
    {
        $entry = $save->handle($event, new CalendarEntry, $request->validated(), $request->user());

        return $this->jsonSuccess(['id' => $entry->getKey()], 201);
    }

    /**
     * The kegiatan as its edit form needs it.
     */
    public function show(Request $request, Event $event, CalendarEntry $calendarEntry): JsonResponse
    {
        $calendarEntry->setRelation('event', $event);
        abort_unless($this->authority->canChange($request->user(), $calendarEntry), 403);

        return $this->jsonSuccess([
            'id' => $calendarEntry->getKey(),
            'title' => $calendarEntry->title,
            'division_id' => $calendarEntry->division_id,
            'starts_on' => $calendarEntry->starts_on->toDateString(),
            'ends_on' => $calendarEntry->lastsSeveralDays() ? $calendarEntry->ends_on->toDateString() : null,
            'starts_at' => $calendarEntry->starts_at ? substr($calendarEntry->starts_at, 0, 5) : null,
            'ends_at' => $calendarEntry->ends_at ? substr($calendarEntry->ends_at, 0, 5) : null,
            'location' => $calendarEntry->location,
            'notes' => $calendarEntry->notes,
        ]);
    }

    public function update(SaveCalendarEntryRequest $request, Event $event, CalendarEntry $calendarEntry, SaveCalendarEntry $save): JsonResponse
    {
        $save->handle($event, $calendarEntry, $request->validated(), $request->user());

        return $this->jsonSuccess();
    }

    public function delete(Request $request, Event $event, CalendarEntry $calendarEntry, DeleteCalendarEntry $delete): JsonResponse
    {
        $calendarEntry->setRelation('event', $event);
        abort_unless($this->authority->canChange($request->user(), $calendarEntry), 403);

        $delete->handle($calendarEntry, $request->user());

        return $this->jsonSuccess();
    }
}
