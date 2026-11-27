<?php

namespace Tests\Feature\Meetings;

use App\Actions\Meetings\SaveMeeting;
use App\Actions\Meetings\SaveMinutes;
use App\Enums\CommitteeRoleKind;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\User;
use App\Services\MinutesPdf;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * The minutes as a PDF to share with people outside the committee.
 */
class MinutesPdfTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private User $member;

    private Meeting $meeting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCommittees();
        $this->event = $this->makeEvent(['name' => 'Perayaan Natal 2026']);
        $this->member = User::factory()->create(['name' => 'Ruth Hutapea']);
        $this->seat($this->member, $this->event, CommitteeRoleKind::DivisionMember, $this->makeDivision($this->event));
        $this->meeting = $this->app->make(SaveMeeting::class)->handle($this->event, new Meeting, [
            'title' => 'Rapat pleno 1', 'meets_on' => '2026-11-10', 'starts_at' => '19:00', 'ends_at' => '21:00', 'location' => 'Ruang konsistori', 'agenda' => 'Laporan seksi',
        ], $this->organizer);
    }

    private function writeMinutes(): void
    {
        $this->meeting->attendees()->attach($this->membershipOf($this->member, $this->event)->id, ['event_id' => $this->event->id]);
        $this->app->make(SaveMinutes::class)->handle(
            $this->meeting,
            '<p>Rapat dibuka <strong>pukul 19.00</strong>.</p><script>alert(1)</script>',
            ['Ibadah dimulai pukul 19.00'],
            [['title' => 'Kirim liturgi <ke> pendeta', 'owner_membership_id' => $this->membershipOf($this->member, $this->event)->id, 'due_on' => '2026-11-15']],
            0,
            $this->organizer,
        );
    }

    public function test_a_committee_member_downloads_the_minutes_as_a_pdf(): void
    {
        $this->writeMinutes();

        $response = $this->actingAs($this->member)->get(route('events.meetings.pdf', [$this->event, $this->meeting]))->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename="notulen-rapat-pleno-1-2026-11-10.pdf"', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_the_document_holds_the_meeting_minutes_attendance_decisions_and_follow_ups(): void
    {
        $this->writeMinutes();

        $html = $this->app->make(MinutesPdf::class)->html($this->meeting->fresh());

        foreach (['Perayaan Natal 2026', 'Notulen Rapat pleno 1', 'Selasa, 10 November 2026', '19.00–21.00', 'Ruang konsistori', 'Laporan seksi',
            'Ruth Hutapea', '<strong>pukul 19.00</strong>', 'Ibadah dimulai pukul 19.00', 'Kirim liturgi &lt;ke&gt; pendeta', '15 Nov 2026', 'Belum Selesai'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_there_is_no_pdf_before_the_minutes_are_written(): void
    {
        $this->actingAs($this->member)->get(route('events.meetings.pdf', [$this->event, $this->meeting]))->assertNotFound();
    }

    public function test_people_outside_the_event_cannot_download_it(): void
    {
        $this->writeMinutes();

        $this->actingAs(User::factory()->create())->get(route('events.meetings.pdf', [$this->event, $this->meeting]))->assertNotFound();
    }
}
