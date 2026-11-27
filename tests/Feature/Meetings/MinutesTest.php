<?php

namespace Tests\Feature\Meetings;

use App\Actions\Meetings\SaveMeeting;
use App\Enums\CommitteeRoleKind;
use App\Enums\TaskStatus;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * Minutes are written in one go and are readable by everyone once saved; follow-ups
 * become tasks exactly once (AC-05).
 */
class MinutesTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private User $secretary;

    private User $member;

    private Meeting $meeting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->secretary = User::factory()->create();
        $this->seat($this->secretary, $this->event, CommitteeRoleKind::Secretary);
        $this->member = User::factory()->create(['name' => 'Priskila Gultom']);
        $this->seat($this->member, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
        $this->meeting = $this->app->make(SaveMeeting::class)->handle($this->event, new Meeting, [
            'title' => 'Rapat pleno 1', 'meets_on' => '2026-12-01', 'starts_at' => '19:00',
        ], $this->organizer);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function save(User $author, array $overrides = [])
    {
        return $this->actingAs($author)->postJson(route('events.meetings.minutes', [$this->event, $this->meeting]), [
            'minutes' => '<p>Rapat membahas <strong>susunan acara</strong>.</p>',
            'minutes_version' => 0,
            'decisions' => ['Ibadah dimulai pukul 19.00', '  ', 'Drama anak 12 menit'],
            'action_items' => [
                ['title' => 'Kirim rundown ke pemusik', 'owner_membership_id' => $this->membershipOf($this->member, $this->event)->id, 'due_on' => '2026-12-05'],
                ['title' => '', 'owner_membership_id' => '', 'due_on' => ''],
            ],
            ...$overrides,
        ]);
    }

    public function test_saving_the_minutes_publishes_them_and_turns_follow_ups_into_tasks(): void
    {
        $this->save($this->secretary)->assertOk()->assertJsonPath('data.tasks_created', 1);

        $meeting = $this->meeting->fresh();
        $this->assertSame('<p>Rapat membahas <strong>susunan acara</strong>.</p>', $meeting->minutes);
        $this->assertSame(1, $meeting->minutes_version);
        $this->assertSame(['Ibadah dimulai pukul 19.00', 'Drama anak 12 menit'], $meeting->decisions->pluck('body')->all());

        $task = Task::query()->sole();
        $this->assertSame('Kirim rundown ke pemusik', $task->title);
        $this->assertSame(TaskStatus::Todo, $task->status);
        $this->assertNull($task->division_id);
        $this->assertSame('2026-12-05', $task->due_on->toDateString());
        $this->assertSame($this->membershipOf($this->member, $this->event)->id, $task->owner_membership_id);
        $this->assertTrue($task->actionItem->meeting->is($meeting));
        $this->assertSame(1, $meeting->minutesVersions()->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'meetings.minutes-saved')->sole()->after['tasks_created']);
    }

    public function test_follow_ups_of_a_division_meeting_become_tasks_of_that_division(): void
    {
        $this->meeting->forceFill(['division_id' => $this->acara->id])->save();

        $this->save($this->organizer)->assertOk();

        $this->assertSame($this->acara->id, Task::query()->sole()->division_id);
    }

    public function test_saving_the_same_form_twice_creates_no_duplicate_tasks(): void
    {
        $this->save($this->secretary)->assertOk();

        $this->save($this->secretary)
            ->assertStatus(422)
            ->assertJsonPath('errors.minutes.0', 'Notulen ini sudah disimpan atau diubah sejak Anda membukanya. Tutup form, muat ulang halaman, lalu periksa kembali.');

        $this->assertSame(1, Task::query()->count());
        $this->assertSame(1, $this->meeting->fresh()->minutes_version);
    }

    public function test_correcting_the_minutes_keeps_earlier_tasks_and_adds_only_new_follow_ups(): void
    {
        $this->save($this->secretary)->assertOk();

        $this->save($this->secretary, [
            'minutes' => 'Rapat membahas susunan acara dan konsumsi.',
            'minutes_version' => 1,
            'decisions' => ['Ibadah dimulai pukul 19.00'],
            'action_items' => [['title' => 'Pesan konsumsi petugas', 'owner_membership_id' => null, 'due_on' => null]],
        ])->assertOk()->assertJsonPath('data.tasks_created', 1);

        $meeting = $this->meeting->fresh();
        $this->assertSame(['Kirim rundown ke pemusik', 'Pesan konsumsi petugas'], $meeting->actionItems->pluck('title')->all());
        $this->assertSame(2, Task::query()->count());
        $this->assertSame(['Ibadah dimulai pukul 19.00'], $meeting->decisions->pluck('body')->all());
        $this->assertSame([2, 1], $meeting->minutesVersions->pluck('version_no')->all());
    }

    public function test_the_minutes_need_text_and_follow_ups_need_a_description(): void
    {
        $this->save($this->secretary, [
            'minutes' => '',
            'action_items' => [['title' => '', 'owner_membership_id' => $this->membershipOf($this->member, $this->event)->id, 'due_on' => '']],
        ])->assertStatus(422)
            ->assertJsonPath('errors.minutes.0', 'Tuliskan isi notulen.')
            ->assertJsonPath('errors', fn ($errors) => ($errors['action_items.0.title'][0] ?? null) === 'Tuliskan apa yang harus ditindaklanjuti.');

        $this->assertSame(0, Task::query()->count());
    }

    public function test_only_people_who_run_the_meeting_write_its_minutes(): void
    {
        $this->save($this->member)->assertForbidden();

        $this->assertSame(0, $this->meeting->fresh()->minutes_version);
    }

    public function test_every_committee_member_reads_the_minutes_and_follows_the_task_link(): void
    {
        $this->save($this->secretary)->assertOk();
        $memberOfAnotherDivision = User::factory()->create();
        $this->seat($memberOfAnotherDivision, $this->event, CommitteeRoleKind::DivisionMember, $this->makeDivision($this->event, 'Seksi Konsumsi'));

        $this->actingAs($memberOfAnotherDivision)->get(route('events.meetings.detail', [$this->event, $this->meeting]))
            ->assertOk()
            ->assertSee('<p>Rapat membahas <strong>susunan acara</strong>.</p>', false)
            ->assertSee('Drama anak 12 menit')
            ->assertSee(route('events.tasks.detail', [$this->event, Task::query()->sole()]), false)
            ->assertDontSee('data-minutes-open', false);
    }

    public function test_formatting_is_kept_and_anything_unsafe_is_removed(): void
    {
        $this->save($this->secretary, [
            'minutes' => '<h2 style="text-align: center">Ringkasan</h2><p onclick="alert(1)" style="color: red; background: url(https://jahat.test/x.png)">Teks <em>miring</em></p>'
                .'<script>alert(1)</script><img src="x" onerror="alert(1)"><a href="javascript:alert(1)">tautan</a>'
                .'<table><tr><td colspan="2">sel</td></tr></table><ul><li>poin</li></ul>',
        ])->assertOk();

        $minutes = $this->meeting->fresh()->minutes;
        $this->assertStringContainsString('<h2 style="text-align: center">Ringkasan</h2>', $minutes);
        $this->assertStringContainsString('<p style="color: red">Teks <em>miring</em></p>', $minutes);
        $this->assertStringContainsString('<td colspan="2">sel</td>', $minutes);
        $this->assertStringContainsString('<ul><li>poin</li></ul>', $minutes);
        foreach (['<script', 'onclick', 'onerror', '<img', 'javascript:', 'url('] as $unsafe) {
            $this->assertStringNotContainsString($unsafe, $minutes);
        }
    }

    public function test_an_editor_with_only_empty_paragraphs_counts_as_empty(): void
    {
        $this->save($this->secretary, ['minutes' => '<p>&nbsp;</p><p><br></p>'])
            ->assertStatus(422)
            ->assertJsonPath('errors.minutes.0', 'Tuliskan isi notulen.');
    }

    public function test_minutes_written_before_the_editor_still_read_as_paragraphs(): void
    {
        $this->meeting->forceFill(['minutes' => "Baris satu\nbaris dua\n\nParagraf <dua>", 'minutes_version' => 1])->save();

        $this->actingAs($this->member)->get(route('events.meetings.detail', [$this->event, $this->meeting]))
            ->assertSee('<p>Baris satu<br />'."\n".'baris dua</p><p>Paragraf &lt;dua&gt;</p>', false);
    }
}
