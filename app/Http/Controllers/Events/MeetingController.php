<?php

namespace App\Http\Controllers\Events;

use App\Actions\Meetings\DeleteMeeting;
use App\Actions\Meetings\MarkAttendance;
use App\Actions\Meetings\PostMeetingComment;
use App\Actions\Meetings\SaveMeeting;
use App\Actions\Meetings\SaveMinutes;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Meetings\MarkAttendanceRequest;
use App\Http\Requests\Meetings\PostMeetingCommentRequest;
use App\Http\Requests\Meetings\SaveMeetingRequest;
use App\Http\Requests\Meetings\SaveMinutesRequest;
use App\Models\Division;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\Meeting;
use App\Models\MeetingComment;
use App\Services\DisplayTime;
use App\Services\MeetingAuthority;
use App\Services\MinutesPdf;
use App\Services\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * The "Rapat & Notulen" tab: meetings the whole committee sees, and their minutes.
 */
class MeetingController extends Controller
{
    public function __construct(
        private MeetingAuthority $authority,
        private DisplayTime $time,
    ) {}

    public function index(Request $request, Event $event): View
    {
        Gate::authorize('viewMeetings', $event);

        $today = now($this->time->timezone())->startOfDay();
        $meetings = $event->meetings()
            ->with('division:id,name')
            ->withCount(['decisions', 'actionItems', 'comments'])
            ->orderBy('meets_on')
            ->orderBy('starts_at')
            ->get();

        return view('events.meetings', [
            'event' => $event,
            'upcoming' => $meetings->filter(fn (Meeting $meeting) => $meeting->isUpcoming($today))->values(),
            'past' => $meetings->reject(fn (Meeting $meeting) => $meeting->isUpcoming($today))->sortByDesc(fn (Meeting $meeting) => $meeting->meets_on->toDateString().' '.$meeting->starts_at)->values(),
            'today' => $today,
            'divisions' => $this->schedulableDivisions($request, $event),
            'canSchedule' => $this->authority->canSchedule($request->user(), $event),
        ]);
    }

    public function store(SaveMeetingRequest $request, Event $event, SaveMeeting $save): JsonResponse
    {
        $meeting = $save->handle($event, new Meeting, $request->validated(), $request->user());

        return $this->jsonSuccess(['id' => $meeting->getKey(), 'url' => route('events.meetings.detail', [$event, $meeting])], 201);
    }

    public function detail(Request $request, Event $event, Meeting $meeting): View
    {
        Gate::authorize('viewMeetings', $event);

        $meeting->load([
            'division:id,name',
            'minutesAuthor:id,name',
            'attendees.user:id,name',
            'decisions',
            'actionItems.owner.user:id,name',
            'actionItems.task:id,title,status',
            'comments.author:id,name',
            'minutesVersions.author:id,name',
        ]);

        $canManage = $this->authority->canManage($request->user(), $meeting);
        $presentIds = $meeting->attendees->modelKeys();
        $committee = $event->memberships()
            ->whereIn('event_memberships.status', [MembershipStatus::Invited, MembershipStatus::Active])
            ->with(['user:id,name', 'activeAssignments.committeeRole:id,name,sort_order', 'activeAssignments.division:id,name'])
            ->get()
            ->sortBy(fn (EventMembership $membership) => $membership->user->name)
            ->values();

        return view('events.meeting', [
            'event' => $event,
            'meeting' => $meeting,
            'today' => now($this->time->timezone())->startOfDay(),
            'canManage' => $canManage,
            'canComment' => $this->authority->canComment($request->user(), $meeting),
            'divisions' => $canManage ? $this->schedulableDivisions($request, $event) : collect(),
            'maxRows' => SaveMinutesRequest::MAX_ROWS,
            'committee' => $committee,
            'attendanceState' => $meeting->attendanceState(now(), $this->time->timezone()),
            'attendanceClosesAt' => $meeting->attendanceClosesAt($this->time->timezone()),
            'minutesHtml' => app(RichText::class)->sanitize($meeting->minutes),
            'presentIds' => $presentIds,
        ]);
    }

    /**
     * The meeting as its edit form needs it.
     */
    public function show(Request $request, Event $event, Meeting $meeting): JsonResponse
    {
        abort_unless($this->authority->canManage($request->user(), $meeting), 403);

        return $this->jsonSuccess([
            'id' => $meeting->getKey(),
            'title' => $meeting->title,
            'division_id' => $meeting->division_id,
            'meets_on' => $meeting->meets_on->toDateString(),
            'starts_at' => substr($meeting->starts_at, 0, 5),
            'ends_at' => $meeting->ends_at ? substr($meeting->ends_at, 0, 5) : null,
            'location' => $meeting->location,
            'agenda' => $meeting->agenda,
        ]);
    }

    public function update(SaveMeetingRequest $request, Event $event, Meeting $meeting, SaveMeeting $save): JsonResponse
    {
        $save->handle($event, $meeting, $request->validated(), $request->user());

        return $this->jsonSuccess();
    }

    public function delete(Request $request, Event $event, Meeting $meeting, DeleteMeeting $delete): JsonResponse
    {
        abort_unless($this->authority->canManage($request->user(), $meeting), 403);

        $delete->handle($meeting, $request->user());

        return $this->jsonSuccess(['url' => route('events.meetings', $event)]);
    }

    /**
     * Save the minutes: readable by the whole committee at once, follow-ups become tasks.
     */
    public function saveMinutes(SaveMinutesRequest $request, Event $event, Meeting $meeting, SaveMinutes $save): JsonResponse
    {
        $tasksCreated = $save->handle(
            $meeting,
            $request->validated('minutes'),
            $request->validated('decisions') ?? [],
            $request->newActionItems(),
            (int) $request->validated('minutes_version'),
            $request->user(),
        );

        return $this->jsonSuccess(['tasks_created' => $tasksCreated]);
    }

    /**
     * The minutes as a PDF to share outside the committee.
     */
    public function pdf(Event $event, Meeting $meeting, MinutesPdf $pdf): Response
    {
        Gate::authorize('viewMeetings', $event);
        abort_unless($meeting->hasMinutes(), 404);

        return response($pdf->render($meeting), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf->filename($meeting).'"',
        ]);
    }

    /**
     * Move one person between "tidak hadir" and "hadir". Saved at once.
     */
    public function markAttendance(MarkAttendanceRequest $request, Event $event, Meeting $meeting, MarkAttendance $mark): JsonResponse
    {
        $present = $mark->handle($meeting, EventMembership::query()->findOrFail($request->validated('membership_id')), $request->boolean('present'));

        return $this->jsonSuccess(['present_count' => $present]);
    }

    public function postComment(PostMeetingCommentRequest $request, Event $event, Meeting $meeting, PostMeetingComment $post): JsonResponse
    {
        $comment = $post->handle($meeting, $request->user(), $request->validated('body'));

        return $this->jsonSuccess(['id' => $comment->getKey()], 201);
    }

    /**
     * The {comment} parameter is resolved through the meeting's own comments.
     */
    public function deleteComment(Request $request, Event $event, Meeting $meeting, MeetingComment $comment): JsonResponse
    {
        abort_unless($this->authority->canDeleteComment($request->user(), $comment), 403);

        $comment->delete();

        return $this->jsonSuccess();
    }

    /**
     * Divisions the person may hold meetings for; an empty choice means the core team.
     *
     * @return Collection<int, Division>
     */
    private function schedulableDivisions(Request $request, Event $event): Collection
    {
        return $event->divisions()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn (Division $division) => $this->authority->canManageIn($request->user(), $event, $division->id))
            ->values();
    }
}
