<?php

namespace Tests\Feature\Notifications;

use App\Actions\Events\RevokeMembership;
use App\Enums\CommitteeRoleKind;
use App\Models\Division;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * The lonceng and the Notifikasi page: a person sees and opens only their own notices.
 */
class NotificationInboxTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private User $worker;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-12-01 05:00:00');
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->worker = User::factory()->create();
        $this->seat($this->worker, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
        $this->other = User::factory()->create();
        $this->seat($this->other, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
    }

    private function taskFor(User $owner, string $title = 'Cetak liturgi'): Task
    {
        return $this->makeTask($this->event, ['title' => $title, 'division_id' => $this->acara->id, 'owner_membership_id' => $this->membershipOf($owner, $this->event)->id]);
    }

    private function noticeOf(User $user): DatabaseNotification
    {
        return $user->notifications()->firstOrFail();
    }

    public function test_the_lonceng_shows_the_unread_count_and_escapes_titles(): void
    {
        $this->taskFor($this->worker, '<b>Sound</b>');
        $this->taskFor($this->worker, 'Konsumsi');

        $this->actingAs($this->worker)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('data-bell-count>2<', false)
            ->assertSee('Tugas baru untuk Anda: &lt;b&gt;Sound&lt;/b&gt;', false)
            ->assertDontSee('<b>Sound</b>', false);
    }

    public function test_opening_a_notice_marks_it_read_and_goes_to_its_page(): void
    {
        $task = $this->taskFor($this->worker);
        $notice = $this->noticeOf($this->worker);

        $this->actingAs($this->worker)->get(route('notifications.open', $notice->id))
            ->assertRedirect(route('events.tasks.detail', [$this->event, $task]));

        $this->assertNotNull($notice->fresh()->read_at);
    }

    public function test_another_persons_notice_cannot_be_opened(): void
    {
        $this->taskFor($this->worker);

        $this->actingAs($this->other)->get(route('notifications.open', $this->noticeOf($this->worker)->id))->assertNotFound();

        $this->assertNull($this->noticeOf($this->worker)->read_at);
    }

    public function test_a_notice_pointing_off_site_goes_to_the_list_instead(): void
    {
        $id = (string) Str::uuid();
        DatabaseNotification::query()->forceCreate([
            'id' => $id,
            'type' => 'task.assigned',
            'notifiable_type' => $this->worker->getMorphClass(),
            'notifiable_id' => $this->worker->id,
            'event_id' => $this->event->id,
            'data' => ['title' => 'Coba', 'path' => '//evil.example/login'],
        ]);

        $this->actingAs($this->worker)->get(route('notifications.open', $id))->assertRedirect(route('notifications.index'));
    }

    public function test_mark_all_read_touches_only_ones_own_notices(): void
    {
        $this->taskFor($this->worker);
        $this->taskFor($this->other, 'Sewa tenda');

        $this->actingAs($this->worker)->postJson(route('notifications.read_all'))->assertOk()->assertJsonPath('data.unread', 0);

        $this->assertNotNull($this->noticeOf($this->worker)->read_at);
        $this->assertNull($this->noticeOf($this->other)->read_at);
        $this->actingAs($this->worker)->get(route('notifications.index'))->assertSee('Semua notifikasi sudah dibaca.');
    }

    public function test_notices_of_an_event_one_was_removed_from_disappear(): void
    {
        $this->taskFor($this->worker);
        $notice = $this->noticeOf($this->worker);

        $this->app->make(RevokeMembership::class)->handle($this->membershipOf($this->worker, $this->event), 'Pindah tugas pelayanan', $this->organizer);

        $this->actingAs($this->worker)->get(route('notifications.index', ['tampil' => 'semua']))->assertOk()->assertDontSee('Cetak liturgi');
        $this->actingAs($this->worker)->get(route('notifications.open', $notice->id))->assertNotFound();
    }
}
