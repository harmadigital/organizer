<?php

namespace Tests\Feature\Notifications;

use App\Actions\Work\PostTaskComment;
use App\Actions\Work\SaveTask;
use App\Enums\CommitteeRoleKind;
use App\Models\Division;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * Notices about tasks: a new owner or collaborator is told, and so is everyone involved when
 * someone comments. The person who acted is never told about their own action.
 */
class TaskNoticesTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private User $coordinator;

    private User $worker;

    private User $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-12-01 05:00:00');
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->coordinator = User::factory()->create(['name' => 'Ruth']);
        $this->seat($this->coordinator, $this->event, CommitteeRoleKind::DivisionCoordinator, $this->acara);
        $this->worker = User::factory()->create(['name' => 'Priskila']);
        $this->seat($this->worker, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
        $this->helper = User::factory()->create(['name' => 'Daniel']);
        $this->seat($this->helper, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function noticesOf(User $user, string $type): Collection
    {
        return $user->notifications()->where('type', $type)->get();
    }

    /**
     * @param  list<User>  $collaborators
     */
    private function assign(Task $task, User $owner, array $collaborators, array $attributes = []): Task
    {
        return $this->app->make(SaveTask::class)->handle($this->event, $task, [
            'title' => 'Jadwal petugas ibadah',
            'division_id' => $this->acara->id,
            'priority' => 'medium',
            'weight' => 1,
            'due_on' => '2026-12-15',
            ...$attributes,
            'owner_membership_id' => $this->membershipOf($owner, $this->event)->id,
        ], array_map(fn (User $person) => $this->membershipOf($person, $this->event)->id, $collaborators), $this->coordinator);
    }

    public function test_the_new_owner_and_collaborators_are_told_but_not_the_person_who_assigned(): void
    {
        $task = $this->assign(new Task, $this->worker, [$this->helper]);

        $notice = $this->noticesOf($this->worker, 'task.assigned')->sole();
        $this->assertSame('Tugas baru untuk Anda: Jadwal petugas ibadah', $notice->data['title']);
        $this->assertSame('Tenggat Selasa, 15 Desember 2026', $notice->data['line']);
        $this->assertSame(route('events.tasks.detail', [$this->event, $task], absolute: false), $notice->data['path']);
        $this->assertCount(1, $this->noticesOf($this->helper, 'task.assigned'));
        $this->assertCount(0, $this->coordinator->notifications);
    }

    public function test_changing_a_task_tells_only_people_newly_added(): void
    {
        $task = $this->assign(new Task, $this->worker, []);
        $newcomer = User::factory()->create();
        $this->seat($newcomer, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);

        $this->assign($task, $this->worker, [$newcomer], ['title' => 'Jadwal petugas ibadah (revisi)']);

        $this->assertCount(1, $this->noticesOf($this->worker, 'task.assigned'));
        $this->assertCount(1, $this->noticesOf($newcomer, 'task.assigned'));
    }

    public function test_people_still_invited_or_without_a_login_are_not_told(): void
    {
        $invited = User::factory()->create();
        $this->seat($invited, $this->event, CommitteeRoleKind::DivisionMember, $this->acara, accepted: false);
        $noLogin = User::factory()->withoutLogin()->create();
        $this->seat($noLogin, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);

        $this->assign(new Task, $noLogin, [$invited]);

        $this->assertCount(0, $this->noticesOf($invited, 'task.assigned'));
        $this->assertCount(0, $noLogin->notifications);
    }

    public function test_a_comment_tells_creator_owner_collaborators_and_earlier_commenters_but_not_its_author(): void
    {
        $this->actingAs($this->coordinator);
        $task = $this->assign(new Task, $this->worker, [$this->helper]);
        $earlier = User::factory()->create();
        $this->seat($earlier, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
        $postComment = $this->app->make(PostTaskComment::class);
        $postComment->handle($task, $earlier, 'Saya bisa bantu cetak.');

        $postComment->handle($task, $this->worker, 'Rahasia: honor petugas Rp 500.000');

        foreach ([$this->coordinator, $this->helper, $earlier] as $person) {
            $notice = $this->noticesOf($person, 'task.commented')->firstWhere('data.title', 'Priskila mengomentari tugas: Jadwal petugas ibadah');
            $this->assertNotNull($notice);
            $this->assertStringEndsWith('#komentar', $notice->data['path']);
        }
        $this->assertCount(1, $this->noticesOf($this->worker, 'task.commented'));
        $this->assertStringNotContainsString('honor', json_encode(DatabaseNotification::query()->pluck('data')));
    }

    public function test_opening_the_task_marks_its_notices_read(): void
    {
        $task = $this->assign(new Task, $this->worker, []);

        $this->actingAs($this->worker)->get(route('events.tasks.detail', [$this->event, $task]))->assertOk();

        $this->assertNotNull($this->noticesOf($this->worker, 'task.assigned')->sole()->read_at);
    }

    public function test_nothing_is_sent_for_an_event_that_is_over(): void
    {
        $this->event->forceFill(['status' => 'completed'])->save();

        $this->assign(new Task, $this->worker, [$this->helper]);

        $this->assertCount(0, $this->worker->notifications);
    }

    public function test_an_invited_person_is_told_once_even_when_given_two_roles(): void
    {
        $person = User::factory()->create();
        $konsumsi = $this->makeDivision($this->event, 'Seksi Konsumsi');

        $this->seat($person, $this->event, CommitteeRoleKind::DivisionMember, $this->acara, accepted: false);
        $this->seat($person, $this->event, CommitteeRoleKind::DivisionMember, $konsumsi, accepted: false, isPrimary: false);

        $notice = $this->noticesOf($person, 'committee.invited')->sole();
        $this->assertStringStartsWith('Anda diundang menjadi', $notice->data['title']);
        $this->assertSame(route('events.index', absolute: false), $notice->data['path']);
        $this->assertCount(0, $this->noticesOf($this->worker, 'committee.invited'));
    }
}
