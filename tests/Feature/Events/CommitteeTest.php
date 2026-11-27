<?php

namespace Tests\Feature\Events;

use App\Enums\CommitteeRoleKind;
use App\Enums\EventStatus;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\CommitteeRole;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\EventRoleAssignment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\TestCase;

class CommitteeTest extends TestCase
{
    use BuildsCommittees;
    use LazilyRefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCommittees();
        $this->event = $this->makeEvent();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function assign(User $actor, User $person, CommitteeRoleKind $kind, array $overrides = [])
    {
        return $this->actingAs($actor)->postJson(route('events.assignments.insert', $this->event), [
            'user_id' => $person->id,
            'committee_role_id' => $this->role($kind)->id,
            'is_primary' => '1',
            ...$overrides,
        ]);
    }

    private function membershipOf(User $person): EventMembership
    {
        return $this->event->memberships()->where('user_id', $person->id)->sole();
    }

    public function test_a_new_committee_member_is_invited_and_the_assignment_is_audited(): void
    {
        $secretary = User::factory()->create();

        $this->assign($this->organizer, $secretary, CommitteeRoleKind::Secretary, ['job_description' => 'Notulen rapat pleno.'])
            ->assertCreated();

        $membership = $this->membershipOf($secretary);
        $this->assertSame(MembershipStatus::Invited, $membership->status);
        $this->assertSame($this->organizer->id, $membership->invited_by);
        $assignment = $membership->activeAssignments()->sole();
        $this->assertSame('Notulen rapat pleno.', $assignment->job_description);
        $this->assertTrue(AuditLog::query()->where('action', 'committee.assigned')->where('event_id', $this->event->id)->exists());
    }

    public function test_the_person_accepts_or_declines_their_own_invitation(): void
    {
        $secretary = User::factory()->create();
        $treasurer = User::factory()->create();
        $this->assign($this->organizer, $secretary, CommitteeRoleKind::Secretary)->assertCreated();
        $this->assign($this->organizer, $treasurer, CommitteeRoleKind::Treasurer)->assertCreated();

        $this->actingAs($secretary)->postJson(route('events.invitation.respond', [$this->event, 'accept']))
            ->assertOk()
            ->assertJsonPath('data.url', route('events.overview', $this->event));
        $this->actingAs($treasurer)->postJson(route('events.invitation.respond', [$this->event, 'decline']))->assertOk();

        $this->assertSame(MembershipStatus::Active, $this->membershipOf($secretary)->status);
        $this->assertNull($this->membershipOf($secretary)->confirmed_by);
        $this->assertSame(MembershipStatus::Declined, $this->membershipOf($treasurer)->status);
        $this->assertSame(0, $this->membershipOf($treasurer)->activeAssignments()->count());

        $this->actingAs($secretary)->get(route('events.overview', $this->event))->assertOk();
        $this->actingAs($treasurer)->get(route('events.overview', $this->event))->assertNotFound();
        $this->actingAs($secretary)->postJson(route('events.invitation.respond', [$this->event, 'accept']))->assertNotFound();
        $this->actingAs(User::factory()->create())->postJson(route('events.invitation.respond', [$this->event, 'accept']))->assertNotFound();
    }

    public function test_a_manager_can_confirm_on_behalf_of_someone_without_login(): void
    {
        $person = User::factory()->withoutLogin()->create();
        $this->assign($this->organizer, $person, CommitteeRoleKind::Treasurer)->assertCreated();
        $membership = $this->membershipOf($person);

        $this->actingAs($this->organizer)->postJson(route('events.memberships.confirm', [$this->event, $membership]))->assertOk();

        $this->assertSame(MembershipStatus::Active, $membership->fresh()->status);
        $this->assertSame($this->organizer->id, $membership->fresh()->confirmed_by);
        $this->assertTrue(AuditLog::query()->where('action', 'committee.membership_accepted')->sole()->after['on_behalf']);
    }

    public function test_confirming_at_assignment_time_activates_the_membership_at_once(): void
    {
        $chair = User::factory()->create();

        $this->assign($this->organizer, $chair, CommitteeRoleKind::Chair, ['confirm_now' => '1'])->assertCreated();

        $this->assertSame(MembershipStatus::Active, $this->membershipOf($chair)->status);
        $this->actingAs($chair)->get(route('events.overview', $this->event))->assertOk();
    }

    public function test_a_singleton_role_has_one_primary_holder_and_may_have_assistants(): void
    {
        $treasurer = User::factory()->create();
        $assistant = User::factory()->create();
        $this->assign($this->organizer, $treasurer, CommitteeRoleKind::Treasurer)->assertCreated();

        $this->assign($this->organizer, $assistant, CommitteeRoleKind::Treasurer)
            ->assertStatus(422)
            ->assertJsonPath('errors.is_primary.0', 'Jabatan Bendahara sudah memiliki pemegang utama. Tugaskan orang ini sebagai pendamping, atau akhiri jabatan pemegang utama terlebih dahulu.');

        $this->assign($this->organizer, $assistant, CommitteeRoleKind::Treasurer, ['is_primary' => '0'])->assertCreated();

        $this->assertFalse($this->membershipOf($assistant)->activeAssignments()->sole()->is_primary);
    }

    public function test_the_database_refuses_a_second_primary_holder_even_if_the_check_is_bypassed(): void
    {
        $this->seat(User::factory()->create(), $this->event, CommitteeRoleKind::Chair);
        $other = EventMembership::factory()->create(['event_id' => $this->event->id]);
        $duplicate = new EventRoleAssignment([
            'event_id' => $this->event->id,
            'event_membership_id' => $other->id,
            'committee_role_id' => $this->role(CommitteeRoleKind::Chair)->id,
            'is_primary' => true,
        ]);
        $duplicate->refreshUniquenessKeys(true);

        $this->expectException(UniqueConstraintViolationException::class);

        $duplicate->save();
    }

    public function test_the_same_role_cannot_be_given_twice_to_the_same_person(): void
    {
        $member = User::factory()->create();
        $division = $this->makeDivision($this->event);
        $this->assign($this->organizer, $member, CommitteeRoleKind::DivisionMember, ['division_id' => $division->id])->assertCreated();

        $this->assign($this->organizer, $member, CommitteeRoleKind::DivisionMember, ['division_id' => $division->id])
            ->assertStatus(422)
            ->assertJsonPath('errors.committee_role_id.0', 'Orang ini sudah memegang jabatan tersebut.')
            ->assertJsonPath('data', 'Orang ini sudah memegang jabatan tersebut.')
            ->assertJsonPath('status', false);
    }

    public function test_the_role_and_the_division_must_match(): void
    {
        $person = User::factory()->create();
        $division = $this->makeDivision($this->event);
        $otherEventDivision = $this->makeDivision($this->makeEvent(['name' => 'Lain']), 'Seksi Lain');

        $this->assign($this->organizer, $person, CommitteeRoleKind::DivisionCoordinator)
            ->assertStatus(422)
            ->assertJsonPath('errors.division_id.0', 'Jabatan Koordinator Seksi harus dikaitkan dengan seksi.');
        $this->assign($this->organizer, $person, CommitteeRoleKind::Secretary, ['division_id' => $division->id])
            ->assertStatus(422)
            ->assertJsonPath('errors.division_id.0', 'Jabatan Sekretaris berlaku untuk seluruh event, bukan untuk satu seksi.');
        $this->assign($this->organizer, $person, CommitteeRoleKind::DivisionCoordinator, ['division_id' => $otherEventDivision->id])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['division_id']]);
    }

    public function test_inactive_people_and_inactive_or_foreign_roles_cannot_be_assigned(): void
    {
        $this->assign($this->organizer, User::factory()->inactive()->create(), CommitteeRoleKind::Secretary)
            ->assertStatus(422)
            ->assertJsonPath('errors.user_id.0', 'User tidak ditemukan atau sudah nonaktif.');

        $this->role(CommitteeRoleKind::Deputy)->update(['is_active' => false]);
        $this->assign($this->organizer, User::factory()->create(), CommitteeRoleKind::Deputy)->assertStatus(422);

        $foreignRole = CommitteeRole::factory()->create();
        $this->actingAs($this->organizer)->postJson(route('events.assignments.insert', $this->event), [
            'user_id' => User::factory()->create()->id,
            'committee_role_id' => $foreignRole->id,
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['committee_role_id']]);
    }

    public function test_a_supervisor_cannot_also_run_the_committee_and_the_other_way_round(): void
    {
        $supervisor = User::factory()->create();
        $secretary = User::factory()->create();
        $this->seat($supervisor, $this->event, CommitteeRoleKind::Supervisor);
        $this->seat($secretary, $this->event, CommitteeRoleKind::Secretary);

        $this->assign($this->organizer, $supervisor, CommitteeRoleKind::Treasurer)
            ->assertStatus(422)
            ->assertJsonPath('errors.committee_role_id.0', 'Orang ini adalah pengawas event sehingga tidak dapat sekaligus menjadi pelaksana.');
        $this->assign($this->organizer, $secretary, CommitteeRoleKind::Supervisor)
            ->assertStatus(422)
            ->assertJsonPath('errors.committee_role_id.0', 'Orang ini sudah menjadi pelaksana dalam panitia sehingga tidak dapat sekaligus menjadi pengawas.');
    }

    public function test_the_chair_fills_the_committee_but_the_organization_appoints_chair_and_supervisors(): void
    {
        $chair = User::factory()->create();
        $this->seat($chair, $this->event, CommitteeRoleKind::Chair);

        $this->assign($chair, User::factory()->create(), CommitteeRoleKind::Secretary)->assertCreated();
        $this->assign($chair, User::factory()->create(), CommitteeRoleKind::Supervisor)->assertForbidden();
        $this->assign($chair, User::factory()->create(), CommitteeRoleKind::Chair, ['is_primary' => '0'])->assertForbidden();
        $this->assign($this->organizer, User::factory()->create(), CommitteeRoleKind::Supervisor)->assertCreated();
    }

    public function test_a_coordinator_adds_members_to_their_own_division_only(): void
    {
        $program = $this->makeDivision($this->event, 'Seksi Acara');
        $music = $this->makeDivision($this->event, 'Sub-seksi Musik', $program);
        $catering = $this->makeDivision($this->event, 'Seksi Konsumsi');
        $coordinator = User::factory()->create();
        $this->seat($coordinator, $this->event, CommitteeRoleKind::DivisionCoordinator, $program);

        $this->assign($coordinator, User::factory()->create(), CommitteeRoleKind::DivisionMember, ['division_id' => $music->id])->assertCreated();
        $this->assign($coordinator, User::factory()->create(), CommitteeRoleKind::DivisionMember, ['division_id' => $catering->id])->assertForbidden();
        $this->assign($coordinator, User::factory()->create(), CommitteeRoleKind::DivisionCoordinator, ['division_id' => $music->id])->assertForbidden();
        $this->assign($coordinator, User::factory()->create(), CommitteeRoleKind::Treasurer)->assertForbidden();
    }

    public function test_a_plain_member_cannot_change_the_committee(): void
    {
        $division = $this->makeDivision($this->event);
        $member = User::factory()->create();
        $this->seat($member, $this->event, CommitteeRoleKind::DivisionMember, $division);

        $this->assign($member, User::factory()->create(), CommitteeRoleKind::DivisionMember, ['division_id' => $division->id])->assertForbidden();
        $this->actingAs($member)->get(route('events.committee', $this->event))
            ->assertOk()
            ->assertDontSee('data-assignment-add', false)
            ->assertDontSee('data-assignment-end', false);
    }

    public function test_ending_a_role_keeps_the_history_and_frees_the_primary_seat(): void
    {
        $treasurer = User::factory()->create();
        $assignment = $this->seat($treasurer, $this->event, CommitteeRoleKind::Treasurer);

        $this->actingAs($this->organizer)->postJson(route('events.assignments.end', [$this->event, $assignment]), ['reason' => ''])
            ->assertStatus(422)
            ->assertJsonPath('errors.reason.0', 'Kolom alasan wajib diisi.');
        $this->actingAs($this->organizer)->postJson(route('events.assignments.end', [$this->event, $assignment]), ['reason' => 'Pindah tugas ke luar kota.'])
            ->assertOk();

        $assignment->refresh();
        $this->assertNotNull($assignment->ends_at);
        $this->assertSame('Pindah tugas ke luar kota.', $assignment->ended_reason);
        $this->assertNull($assignment->active_key);

        $this->assign($this->organizer, User::factory()->create(), CommitteeRoleKind::Treasurer)->assertCreated();
        $this->actingAs($treasurer)->get(route('events.overview', $this->event))->assertNotFound();
    }

    public function test_revoking_a_membership_ends_every_role_and_access_at_once(): void
    {
        $division = $this->makeDivision($this->event);
        $person = User::factory()->create();
        $this->seat($person, $this->event, CommitteeRoleKind::Secretary);
        $this->seat($person, $this->event, CommitteeRoleKind::DivisionMember, $division);
        $this->actingAs($person)->get(route('events.overview', $this->event))->assertOk();

        $this->actingAs($this->organizer)
            ->postJson(route('events.memberships.revoke', [$this->event, $this->membershipOf($person)]), ['reason' => 'Mengundurkan diri.'])
            ->assertOk();

        $membership = $this->membershipOf($person);
        $this->assertSame(MembershipStatus::Revoked, $membership->status);
        $this->assertSame(0, $membership->activeAssignments()->count());
        $this->assertSame(2, $membership->assignments()->count());
        $this->actingAs($person)->get(route('events.overview', $this->event))->assertNotFound();
        $this->actingAs($person)->getJson(route('events.committee', $this->event))->assertNotFound();
    }

    public function test_a_revoked_person_can_be_invited_again(): void
    {
        $person = User::factory()->create();
        $this->seat($person, $this->event, CommitteeRoleKind::Secretary);
        $this->actingAs($this->organizer)->postJson(route('events.memberships.revoke', [$this->event, $this->membershipOf($person)]), ['reason' => 'Salah orang.'])->assertOk();

        $this->assign($this->organizer, $person, CommitteeRoleKind::Secretary)->assertCreated();

        $membership = $this->membershipOf($person);
        $this->assertSame(MembershipStatus::Invited, $membership->status);
        $this->assertNull($membership->revoked_at);
        $this->assertSame(1, $membership->activeAssignments()->count());
    }

    public function test_nobody_removes_themselves_or_ends_their_own_role(): void
    {
        $chair = User::factory()->create();
        $assignment = $this->seat($chair, $this->event, CommitteeRoleKind::Chair);

        $this->actingAs($chair)->postJson(route('events.assignments.end', [$this->event, $assignment]), ['reason' => 'x'])
            ->assertStatus(409)
            ->assertJsonPath('data', 'Anda tidak dapat mengakhiri jabatan Anda sendiri. Minta pengurus lain untuk melakukannya.');
        $this->actingAs($chair)->postJson(route('events.memberships.revoke', [$this->event, $this->membershipOf($chair)]), ['reason' => 'x'])
            ->assertStatus(409);
    }

    public function test_the_committee_cannot_remove_the_chair_or_a_supervisor(): void
    {
        $chair = User::factory()->create();
        $supervisor = User::factory()->create();
        $chairAssignment = $this->seat($chair, $this->event, CommitteeRoleKind::Chair);
        $supervisorAssignment = $this->seat($supervisor, $this->event, CommitteeRoleKind::Supervisor);
        $secretary = User::factory()->create();
        $this->seat($secretary, $this->event, CommitteeRoleKind::Secretary);
        $this->event->roleRules()->first();
        $this->role(CommitteeRoleKind::Secretary)->abilities()->create(['ability' => 'committee.manage']);

        $this->actingAs($secretary)->postJson(route('events.assignments.end', [$this->event, $supervisorAssignment]), ['reason' => 'x'])->assertForbidden();
        $this->actingAs($secretary)->postJson(route('events.memberships.revoke', [$this->event, $this->membershipOf($chair)]), ['reason' => 'x'])->assertForbidden();
        $this->assertTrue($chairAssignment->fresh()->isActive());
    }

    public function test_a_closed_event_keeps_its_committee_as_it_was(): void
    {
        $person = User::factory()->create();
        $assignment = $this->seat($person, $this->event, CommitteeRoleKind::Secretary);
        $this->event->forceFill(['status' => EventStatus::Completed])->save();

        $this->assign($this->organizer, User::factory()->create(), CommitteeRoleKind::Treasurer)->assertForbidden();
        $this->actingAs($this->organizer)->postJson(route('events.assignments.end', [$this->event, $assignment]), ['reason' => 'x'])->assertForbidden();
        $this->actingAs($this->organizer)->postJson(route('events.divisions.insert', $this->event), ['name' => 'Baru'])->assertForbidden();
    }

    public function test_the_tree_and_chart_show_nested_divisions_and_assistants(): void
    {
        $program = $this->makeDivision($this->event, 'Seksi Acara');
        $music = $this->makeDivision($this->event, 'Sub-seksi Musik', $program);
        $this->seat(User::factory()->create(['name' => 'Bendahara Utama']), $this->event, CommitteeRoleKind::Treasurer);
        $this->seat(User::factory()->create(['name' => 'Asisten Kas']), $this->event, CommitteeRoleKind::Treasurer, isPrimary: false);
        $this->seat(User::factory()->create(['name' => 'Pemain Keyboard']), $this->event, CommitteeRoleKind::DivisionMember, $music);
        $this->seat(User::factory()->create(['name' => 'Pengawas Satu']), $this->event, CommitteeRoleKind::Supervisor);

        $html = $this->actingAs($this->organizer)->get(route('events.committee', $this->event))->assertOk()->getContent();
        $chart = str($html)->after('id="kt_modal_committee_chart"');
        $tree = str($html)->between('data-committee-tree', 'id="kt_modal_committee_chart"');

        $this->assertTrue($chart->contains('Mengawasi seluruh panitia'));
        $this->assertTrue($chart->contains('Asisten Kas'));
        $this->assertTrue($chart->contains('(pendamping)'));
        $this->assertTrue($chart->contains('1 sub-seksi'));
        $this->assertTrue($chart->contains('Pemain Keyboard'));
        $this->assertMatchesRegularExpression('/data-division-block="'.$program->id.'".*data-division-block="'.$music->id.'".*Pemain Keyboard/s', $tree->value());
        $this->assertTrue($tree->contains('Belum ada panitia di seksi ini.'));
    }

    public function test_the_assignment_can_be_edited_and_the_primary_flag_moves_safely(): void
    {
        $first = $this->seat(User::factory()->create(), $this->event, CommitteeRoleKind::Treasurer);
        $second = $this->seat(User::factory()->create(), $this->event, CommitteeRoleKind::Treasurer, isPrimary: false);

        $this->actingAs($this->organizer)->postJson(route('events.assignments.update', [$this->event, $second]), ['is_primary' => '1', 'job_description' => 'Kas kecil'])
            ->assertStatus(422)
            ->assertJsonPath('errors.is_primary.0', 'Jabatan ini sudah memiliki pemegang utama.');

        $this->actingAs($this->organizer)->postJson(route('events.assignments.update', [$this->event, $first]), ['is_primary' => '0', 'job_description' => 'Rekonsiliasi bank'])->assertOk();
        $this->actingAs($this->organizer)->postJson(route('events.assignments.update', [$this->event, $second]), ['is_primary' => '1', 'job_description' => 'Kas kecil'])->assertOk();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame('Kas kecil', $second->fresh()->job_description);
        $this->actingAs($this->organizer)->getJson(route('events.assignments.show', [$this->event, $second]))
            ->assertOk()
            ->assertJsonPath('data.is_singleton', true);
    }

    public function test_the_committee_page_shows_the_structure_and_org_chart(): void
    {
        $chair = User::factory()->create(['name' => 'Yohanes Sitorus']);
        $this->seat($chair, $this->event, CommitteeRoleKind::Chair);
        $this->seat(User::factory()->create(['name' => 'Maria Lestari']), $this->event, CommitteeRoleKind::Secretary, accepted: false);
        $program = $this->makeDivision($this->event, 'Seksi Acara');
        $this->seat(User::factory()->create(['name' => 'Samuel Tanoto']), $this->event, CommitteeRoleKind::DivisionCoordinator, $program);

        $this->actingAs($this->organizer)->get(route('events.committee', $this->event))
            ->assertOk()
            ->assertSee('data-bs-target="#kt_modal_committee_chart"', false)
            ->assertSee('class="modal-dialog modal-fullscreen"', false)
            ->assertSeeInOrder(['Susunan Panitia', 'Lihat Bagan', 'data-committee-tree', 'Pengurus Inti', 'Ketua', 'Yohanes Sitorus', 'Seksi', 'Seksi Acara', 'Koordinator Seksi', 'Samuel Tanoto', 'id="kt_modal_committee_chart"', 'Ketua', 'Yohanes Sitorus', 'Seksi Acara', 'Samuel Tanoto'], false)
            ->assertSee('Maria Lestari')
            ->assertSee('Menunggu konfirmasi')
            ->assertSee('Belum terisi.')
            ->assertSee('data-assignment-add', false)
            ->assertSee('data-division-templates', false)
            ->assertSee('data-assignable-roles', false);
    }
}
