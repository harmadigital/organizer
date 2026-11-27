<?php

namespace Tests\Feature\Work;

use App\Enums\CommitteeRoleKind;
use App\Models\Event;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * Every committee member may comment on any task, without approval.
 */
class TaskCommentTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Task $task;

    private User $otherDivisionMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->task = $this->makeTask($this->event, ['division_id' => $acara->id]);
        $this->otherDivisionMember = User::factory()->create(['name' => 'Anggota Konsumsi']);
        $this->seat($this->otherDivisionMember, $this->event, CommitteeRoleKind::DivisionMember, $this->makeDivision($this->event, 'Seksi Konsumsi'));
    }

    private function comment(User $author, string $body)
    {
        return $this->actingAs($author)->postJson(route('events.tasks.comments.insert', [$this->event, $this->task]), ['body' => $body]);
    }

    public function test_a_member_of_another_division_comments_on_the_task(): void
    {
        $this->comment($this->otherDivisionMember, '  Konsumsi untuk petugas sudah kami siapkan.  ')->assertCreated();

        $comment = TaskComment::query()->sole();
        $this->assertSame('Konsumsi untuk petugas sudah kami siapkan.', $comment->body);
        $this->assertSame($this->otherDivisionMember->id, $comment->user_id);
        $this->assertSame($this->event->id, $comment->event_id);
    }

    public function test_an_empty_comment_is_refused(): void
    {
        $this->comment($this->otherDivisionMember, ' ')
            ->assertStatus(422)
            ->assertJsonPath('errors.body.0', 'Tulis komentar terlebih dahulu.');
    }

    public function test_someone_who_has_not_accepted_their_invitation_cannot_comment(): void
    {
        $invited = User::factory()->create();
        $this->seat($invited, $this->event, CommitteeRoleKind::DivisionMember, $this->task->division, accepted: false);

        $this->comment($invited, 'Halo')->assertNotFound();
        $this->comment(User::factory()->create(), 'Halo')->assertNotFound();

        $this->assertSame(0, TaskComment::query()->count());
    }

    public function test_comments_are_shown_on_the_task_page_escaped(): void
    {
        $this->comment($this->otherDivisionMember, '<script>alert("x")</script> Siap!')->assertCreated();

        $html = $this->actingAs($this->otherDivisionMember)->get(route('events.tasks.detail', [$this->event, $this->task]))->assertOk()->getContent();

        $this->assertStringContainsString('Anggota Konsumsi', $html);
        $this->assertStringContainsString('&lt;script&gt;alert', $html);
        $this->assertStringNotContainsString('<script>alert("x")</script>', $html);
    }

    public function test_the_author_removes_their_own_comment_but_not_someone_elses(): void
    {
        $this->comment($this->otherDivisionMember, 'Komentar saya')->assertCreated();
        $mine = TaskComment::query()->sole();
        $colleague = User::factory()->create();
        $this->seat($colleague, $this->event, CommitteeRoleKind::DivisionMember, $this->task->division);

        $this->actingAs($colleague)->postJson(route('events.tasks.comments.delete', [$this->event, $this->task, $mine]))->assertForbidden();
        $this->assertModelExists($mine);

        $this->actingAs($this->otherDivisionMember)->postJson(route('events.tasks.comments.delete', [$this->event, $this->task, $mine]))->assertOk();
        $this->assertModelMissing($mine);
    }

    public function test_a_task_manager_may_remove_any_comment(): void
    {
        $this->comment($this->otherDivisionMember, 'Tidak pantas')->assertCreated();

        $this->actingAs($this->organizer)->postJson(route('events.tasks.comments.delete', [$this->event, $this->task, TaskComment::query()->sole()]))->assertOk();

        $this->assertSame(0, TaskComment::query()->count());
    }

    public function test_the_work_list_counts_comments(): void
    {
        $this->comment($this->otherDivisionMember, 'Satu')->assertCreated();
        $this->comment($this->otherDivisionMember, 'Dua')->assertCreated();

        $this->actingAs($this->otherDivisionMember)->getJson(route('events.work.data', $this->event))
            ->assertJsonPath('data.tasks.0.comments', 2);
    }
}
