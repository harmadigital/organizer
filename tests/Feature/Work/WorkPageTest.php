<?php

namespace Tests\Feature\Work;

use App\Enums\CommitteeRoleKind;
use App\Enums\TaskStatus;
use App\Models\Division;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

class WorkPageTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private Division $konsumsi;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-12-10 09:00:00');
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->konsumsi = $this->makeDivision($this->event, 'Seksi Konsumsi');
        $this->member = User::factory()->create();
        $this->seat($this->member, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
    }

    /**
     * @return list<string>
     */
    private function visibleTitles(User $user): array
    {
        return collect($this->actingAs($user)->getJson(route('events.work.data', $this->event))->assertOk()->json('data.tasks'))
            ->pluck('title')
            ->sort()
            ->values()
            ->all();
    }

    public function test_committee_members_open_the_work_tab_and_outsiders_do_not_find_it(): void
    {
        $this->actingAs($this->member)->get(route('events.work', $this->event))
            ->assertOk()
            ->assertSee('Program &amp; Tugas', false)
            ->assertSee('assets/js/events/work.js', false)
            ->assertDontSee('data-task-add', false);

        $this->actingAs(User::factory()->create())->get(route('events.work', $this->event))->assertNotFound();
    }

    public function test_every_committee_member_sees_every_task_whatever_their_division(): void
    {
        $this->makeTask($this->event, ['title' => 'Rundown', 'division_id' => $this->acara->id]);
        $this->makeTask($this->event, ['title' => 'Menu makan', 'division_id' => $this->konsumsi->id]);
        $this->makeTask($this->event, ['title' => 'Koordinasi umum']);

        $this->assertSame(['Koordinasi umum', 'Menu makan', 'Rundown'], $this->visibleTitles($this->member));
        $this->assertSame(['Koordinasi umum', 'Menu makan', 'Rundown'], $this->visibleTitles($this->organizer));
    }

    public function test_a_task_of_another_division_can_be_opened_but_not_changed(): void
    {
        $task = $this->makeTask($this->event, ['title' => 'Menu makan', 'division_id' => $this->konsumsi->id]);

        $this->actingAs($this->member)->get(route('events.tasks.detail', [$this->event, $task]))
            ->assertOk()
            ->assertDontSee('data-task-edit', false)
            ->assertDontSee('data-task-complete', false)
            ->assertSee('data-comment-form', false);
    }

    public function test_the_task_page_opens_on_details_while_open_and_on_results_once_finished(): void
    {
        $task = $this->makeTask($this->event, ['division_id' => $this->acara->id]);
        $activePane = fn () => preg_match('/class="tab-pane fade show active" id="tab_(\w+)"/', $this->actingAs($this->member)->get(route('events.tasks.detail', [$this->event, $task]))->getContent(), $match) ? $match[1] : null;

        $this->assertSame('rincian', $activePane());

        $task->forceFill(['status' => TaskStatus::Completed, 'result' => 'Beres'])->save();

        $this->assertSame('hasil', $activePane());
    }

    public function test_the_task_page_shows_only_a_way_back_instead_of_the_event_header(): void
    {
        $task = $this->makeTask($this->event, ['division_id' => $this->acara->id]);

        $this->actingAs($this->member)->get(route('events.tasks.detail', [$this->event, $task]))
            ->assertOk()
            ->assertSee('data-back-to-work', false)
            ->assertSee(route('events.work', $this->event), false)
            ->assertDontSee('ui-page-header', false)
            ->assertDontSee('tidak dapat diubah');

        $this->event->forceFill(['status' => 'completed'])->save();

        $this->actingAs($this->member)->get(route('events.tasks.detail', [$this->event, $task]))
            ->assertSee('sehingga data dan susunan panitianya tidak dapat diubah', false);
    }

    public function test_someone_who_has_not_accepted_their_invitation_does_not_see_the_work(): void
    {
        $invited = User::factory()->create();
        $this->seat($invited, $this->event, CommitteeRoleKind::DivisionMember, $this->acara, accepted: false);

        $this->actingAs($invited)->get(route('events.work', $this->event))->assertNotFound();
    }

    public function test_the_summary_counts_progress_open_overdue_and_finished_work(): void
    {
        $this->makeTask($this->event, ['title' => 'Lewat', 'due_on' => '2026-12-09', 'weight' => 2]);
        $this->makeTask($this->event, ['title' => 'Hari ini', 'due_on' => '2026-12-10', 'weight' => 2]);
        $done = $this->makeTask($this->event, ['title' => 'Beres', 'due_on' => '2026-12-01', 'weight' => 3]);
        $done->forceFill(['status' => TaskStatus::Completed])->save();

        $summary = $this->actingAs($this->organizer)->getJson(route('events.work.data', $this->event))->json('data.summary');

        $this->assertSame(['percent' => 42, 'done' => 3, 'total' => 7], $summary['progress']);
        $this->assertSame(2, $summary['open']);
        $this->assertSame(1, $summary['overdue']);
        $this->assertSame(1, $summary['completed']);
    }

    public function test_each_task_says_whether_the_viewer_may_finish_it(): void
    {
        $own = $this->makeTask($this->event, ['title' => 'Milik sendiri', 'division_id' => $this->acara->id, 'owner_membership_id' => $this->membershipOf($this->member, $this->event)->id]);
        $this->makeTask($this->event, ['title' => 'Milik orang lain', 'division_id' => $this->acara->id]);

        $tasks = collect($this->actingAs($this->member)->getJson(route('events.work.data', $this->event))->json('data.tasks'))->keyBy('title');

        $this->assertTrue($tasks['Milik sendiri']['can_complete']);
        $this->assertFalse($tasks['Milik orang lain']['can_complete']);
        $this->assertTrue($tasks['Milik sendiri']['is_mine']);
        $this->assertSame(route('events.tasks.detail', [$this->event, $own]), $tasks['Milik sendiri']['url']);
    }

    public function test_a_program_offers_adding_tasks_only_to_those_who_manage_its_division(): void
    {
        $coordinator = User::factory()->create();
        $this->seat($coordinator, $this->event, CommitteeRoleKind::DivisionCoordinator, $this->acara);
        $this->makeProgram($this->acara, ['title' => 'Liturgi']);

        $forCoordinator = $this->actingAs($coordinator)->getJson(route('events.work.data', $this->event))->assertOk()->json('data');
        $this->assertTrue($forCoordinator['programs'][0]['can_add_tasks']);
        $this->assertTrue($forCoordinator['can_create_tasks']);
        $this->assertSame('2026-12-10', $forCoordinator['today']);

        $forMember = $this->actingAs($this->member)->getJson(route('events.work.data', $this->event))->assertOk()->json('data');
        $this->assertFalse($forMember['programs'][0]['can_add_tasks']);
        $this->assertFalse($forMember['can_create_tasks']);
    }

    public function test_only_people_who_plan_work_can_search_committee_members(): void
    {
        $this->actingAs($this->member)->getJson(route('events.work.member_options', $this->event))->assertForbidden();

        $this->actingAs($this->organizer)->getJson(route('events.work.member_options', [$this->event, 'search' => 'zzz-tidak-ada']))
            ->assertOk()
            ->assertJsonPath('data.results', []);
    }

    public function test_task_text_is_escaped_on_the_task_page(): void
    {
        $task = $this->makeTask($this->event, [
            'title' => '<script>alert("judul")</script>',
            'description' => '<img src=x onerror=alert(1)>',
            'division_id' => $this->acara->id,
        ]);

        $html = $this->actingAs($this->member)->get(route('events.tasks.detail', [$this->event, $task]))->assertOk()->getContent();

        $this->assertStringContainsString('&lt;script&gt;alert', $html);
        $this->assertStringNotContainsString('<script>alert("judul")</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
    }

    public function test_the_overview_shows_work_progress_to_people_who_may_see_the_work(): void
    {
        $finished = $this->makeTask($this->event, ['division_id' => $this->acara->id]);
        $finished->forceFill(['status' => TaskStatus::Completed])->save();
        $this->makeTask($this->event, ['division_id' => $this->acara->id]);

        $this->actingAs($this->member)->get(route('events.overview', $this->event))
            ->assertOk()
            ->assertSee('Progres Kerja')
            ->assertSee('data-work-percent>50%', false);
    }
}
