<?php

namespace Tests\Feature\Events;

use App\Enums\CommitteeRoleKind;
use App\Enums\EventStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\TestCase;

class EventStatusTest extends TestCase
{
    use BuildsCommittees;
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCommittees();
    }

    private function changeStatus(Event $event, string $status, ?string $reason = null, ?User $actor = null)
    {
        return $this->actingAs($actor ?? $this->organizer)->postJson(route('events.status', $event), ['status' => $status, 'reason' => $reason]);
    }

    public function test_an_event_cannot_be_activated_while_a_required_role_is_empty(): void
    {
        $event = $this->makeEvent();
        $this->seat(User::factory()->create(), $event, CommitteeRoleKind::Chair);
        $this->seat(User::factory()->create(), $event, CommitteeRoleKind::Secretary);

        $this->changeStatus($event, 'active')
            ->assertStatus(422)
            ->assertJsonPath('errors.status.0', 'Event belum dapat diaktifkan. Jabatan wajib yang belum terisi oleh panitia yang sudah bersedia: Bendahara, Komite / Pengawas.');

        $this->assertSame(EventStatus::Draft, $event->fresh()->status);
    }

    public function test_pending_invitations_do_not_count_towards_the_gate(): void
    {
        $event = $this->makeEvent();
        $people = $this->fillRequiredRoles($event);
        $treasurerMembership = $event->memberships()->where('user_id', $people['treasurer']->id)->sole();
        $treasurerMembership->update(['status' => 'invited']);

        $this->changeStatus($event, 'active')
            ->assertStatus(422)
            ->assertJsonPath('errors.status.0', 'Event belum dapat diaktifkan. Jabatan wajib yang belum terisi oleh panitia yang sudah bersedia: Bendahara.');
    }

    public function test_an_event_without_a_deputy_can_be_activated_once_required_roles_are_filled(): void
    {
        $event = $this->makeEvent();
        $this->fillRequiredRoles($event);

        $this->changeStatus($event, 'active', 'Pembentukan panitia selesai.')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $event->refresh();
        $this->assertSame(EventStatus::Active, $event->status);
        $this->assertNotNull($event->activated_at);
        $this->assertSame(0, $event->assignments()->whereHas('committeeRole', fn ($role) => $role->where('kind', 'deputy'))->count());

        $entry = $event->statusHistories()->first();
        $this->assertSame(EventStatus::Draft, $entry->from_status);
        $this->assertSame(EventStatus::Active, $entry->to_status);
        $this->assertSame($this->organizer->id, $entry->actor_user_id);
        $this->assertTrue(AuditLog::query()->where('action', 'events.status_changed')->where('event_id', $event->id)->exists());
    }

    public function test_cancelling_and_going_back_need_a_reason(): void
    {
        $event = $this->makeEvent();

        $this->changeStatus($event, 'cancelled')
            ->assertStatus(422)
            ->assertJsonPath('errors.reason.0', 'Alasan wajib diisi untuk perubahan status ini.');

        $this->changeStatus($event, 'cancelled', 'Ditunda karena renovasi gedung.')->assertOk();
        $this->assertNotNull($event->fresh()->closed_at);

        $this->changeStatus($event, 'draft')->assertStatus(422);
        $this->changeStatus($event, 'draft', 'Renovasi selesai lebih cepat.')->assertOk();

        $this->assertSame(EventStatus::Draft, $event->fresh()->status);
        $this->assertNull($event->fresh()->closed_at);
    }

    public function test_transitions_that_skip_steps_are_refused(): void
    {
        $event = $this->makeEvent();

        $this->changeStatus($event, 'completed')
            ->assertStatus(422)
            ->assertJsonPath('errors.status.0', 'Event berstatus Draf tidak dapat diubah menjadi Selesai.');
        $this->changeStatus($event, 'archived')->assertStatus(422);
        $this->changeStatus($event, 'launched')->assertStatus(422);
    }

    public function test_a_completed_event_can_be_archived_and_an_archive_is_final(): void
    {
        $event = Event::factory()->withStatus(EventStatus::Completed)->create(['organization_id' => $this->organization->id]);

        $this->changeStatus($event, 'archived')->assertOk();
        $this->changeStatus($event, 'active', 'Salah klik')->assertStatus(422);
    }

    public function test_the_chair_cannot_change_the_status_but_can_change_the_phase(): void
    {
        $event = $this->makeEvent();
        $people = $this->fillRequiredRoles($event);
        $chair = $people['chair'];

        $this->changeStatus($event, 'active', actor: $chair)->assertForbidden();

        $this->actingAs($chair)->postJson(route('events.phase', $event), ['phase' => 'planning', 'note' => 'Mulai menyusun RAB.'])
            ->assertOk()
            ->assertJsonPath('data.phase', 'planning');

        $entry = $event->statusHistories()->first();
        $this->assertSame('planning', $entry->to_phase->value);
        $this->assertSame('Mulai menyusun RAB.', $entry->reason);

        $this->actingAs($people['supervisor'])->postJson(route('events.phase', $event), ['phase' => 'preparation'])->assertForbidden();
        $this->actingAs($chair)->postJson(route('events.phase', $event), ['phase' => 'party'])->assertStatus(422);
    }
}
