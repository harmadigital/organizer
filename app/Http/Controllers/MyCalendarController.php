<?php

namespace App\Http\Controllers;

use App\Http\Requests\Calendar\MyCalendarFeedRequest;
use App\Services\CalendarFeed;
use App\Services\DisplayTime;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Kalender Saya": the event days, meetings and kegiatan of every event the person is an
 * active committee member of, plus their own task deadlines. Read only; kegiatan are added
 * on the event's Kalender tab. Those who oversee every event (events.view) may switch to
 * all events of the organization.
 */
class MyCalendarController extends Controller
{
    public function __construct(
        private CalendarFeed $feed,
        private DisplayTime $time,
    ) {}

    public function index(Request $request): View
    {
        return view('calendar.index', [
            'today' => now($this->time->timezone())->toDateString(),
            'canSeeOrganization' => $request->user()->can('events.view'),
            'hasEvents' => $this->feed->hasEvents($request->user()),
        ]);
    }

    public function feed(MyCalendarFeedRequest $request): JsonResponse
    {
        $items = $request->wantsOrganization()
            ? $this->feed->forOrganization($request->user(), $request->from(), $request->until())
            : $this->feed->forPerson($request->user(), $request->from(), $request->until());

        return $this->jsonSuccess(['items' => $items]);
    }
}
