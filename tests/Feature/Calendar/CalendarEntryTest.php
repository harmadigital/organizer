<?php

namespace Tests\Feature\Calendar;

use App\Actions\Calendar\SaveCalendarEntry;
use App\Enums\CommitteeRoleKind;
use App\Enums\EventStatus;
use App\Models\AuditLog;
use App\Models\CalendarEntry;
use App\Models\Division;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsCommittees;
use Tests\TestCase;

/**
 * Kegiatan that committee members add to the event calendar themselves.
 */
class CalendarEntryTest extends TestCase
{
    use BuildsCommittees;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private Division $perlengkapan;

    private User $member;

    private User $otherMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-27 10:00:00');
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->perlengkapan = $this->makeDivision($this->event, 'Seksi Perlengkapan');
        $this->member = User::factory()->create(['name' => 'Anggota Acara']);
        $this->seat($this->member, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
        $this->otherMember = User::factory()->create(['name' => 'Anggota Perlengkapan']);
        $this->seat($this->otherMember, $this->event, CommitteeRoleKind::DivisionMember, $this->perlengkapan);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Survei lokasi',
            'starts_on' => '2026-12-05',
            'ends_on' => '',
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'location' => 'Gedung gereja',
            'division_id' => $this->acara->id,
            'notes' => 'Bawa meteran.',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function add(User $actor, array $overrides = []): TestResponse
    {
        return $this->actingAs($actor)->postJson(route('events.calendar.insert', $this->event), $this->payload($overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function change(User $actor, CalendarEntry $entry, array $overrides = []): TestResponse
    {
        return $this->actingAs($actor)->postJson(route('events.calendar.update', [$this->event, $entry]), $this->payload($overrides));
    }

    private function remove(User $actor, CalendarEntry $entry): TestResponse
    {
        return $this->actingAs($actor)->postJson(route('events.calendar.delete', [$this->event, $entry]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function entryBy(User $author, array $attributes = []): CalendarEntry
    {
        return $this->app->make(SaveCalendarEntry::class)->handle($this->event, new CalendarEntry, [
            'title' => 'Latihan drama anak',
            'starts_on' => '2026-12-19',
            'division_id' => $this->acara->id,
            ...$attributes,
        ], $author);
    }

    public function test_any_active_member_adds_a_kegiatan_and_it_is_audited(): void
    {
        $this->add($this->member, ['division_id' => $this->perlengkapan->id])->assertCreated();

        $entry = CalendarEntry::query()->sole();
        $this->assertSame($this->member->id, $entry->user_id);
        $this->assertSame($this->perlengkapan->id, $entry->division_id);
        $this->assertSame('2026-12-05', $entry->ends_on->toDateString());
        $this->assertSame('09:00:00', $entry->starts_at);

        $audit = AuditLog::query()->where('action', 'calendar-entries.created')->sole();
        $this->assertSame($this->event->id, $audit->event_id);
        $this->assertSame($this->member->id, $audit->actor_user_id);
    }

    public function test_a_kegiatan_without_a_start_time_is_all_day(): void
    {
        $this->add($this->member, ['starts_at' => '', 'ends_at' => ''])->assertCreated();

        $item = collect($this->actingAs($this->member)->getJson(route('events.calendar.feed', [$this->event, 'start' => '2026-11-30', 'end' => '2027-01-11']))->json('data.items'))
            ->firstWhere('type', 'entry');

        $this->assertTrue($item['all_day']);
        $this->assertSame('Seharian', $item['time_label']);
        $this->assertSame(['2026-12-05', '2026-12-06'], [$item['start'], $item['end']]);
    }

    public function test_the_end_time_must_be_after_the_start_time(): void
    {
        $this->add($this->member, ['starts_at' => '11:00', 'ends_at' => '09:00'])
            ->assertStatus(422)
            ->assertJsonPath('errors.ends_at.0', 'Jam selesai harus setelah jam mulai.');
    }

    public function test_an_end_time_needs_a_start_time(): void
    {
        $this->add($this->member, ['starts_at' => '', 'ends_at' => '11:00'])
            ->assertStatus(422)
            ->assertJsonPath('errors.starts_at.0', 'Isi jam mulai bila jam selesai diisi.');
    }

    public function test_the_end_date_cannot_be_before_the_start_date(): void
    {
        $this->add($this->member, ['ends_on' => '2026-12-04', 'starts_at' => '', 'ends_at' => ''])
            ->assertStatus(422)
            ->assertJsonPath('errors.ends_on.0', 'Sampai tanggal tidak boleh sebelum tanggal.');
    }

    public function test_a_kegiatan_may_last_at_most_31_days(): void
    {
        $this->add($this->member, ['starts_on' => '2026-12-01', 'ends_on' => '2027-01-01', 'starts_at' => '', 'ends_at' => ''])
            ->assertStatus(422)
            ->assertJsonPath('errors.ends_on.0', 'Kegiatan paling lama 31 hari.');

        $this->add($this->member, ['starts_on' => '2026-12-01', 'ends_on' => '2026-12-31', 'starts_at' => '', 'ends_at' => ''])->assertCreated();
    }

    public function test_a_multi_day_kegiatan_cannot_have_times(): void
    {
        $this->add($this->member, ['ends_on' => '2026-12-06'])
            ->assertStatus(422)
            ->assertJsonPath('errors.starts_at.0', 'Kegiatan beberapa hari dicatat seharian; kosongkan jam.');

        $this->assertDatabaseCount('calendar_entries', 0);
    }

    public function test_a_kegiatan_changed_to_several_days_without_the_time_fields_becomes_all_day(): void
    {
        $entry = $this->entryBy($this->member, ['starts_on' => '2026-12-05', 'starts_at' => '09:00', 'ends_at' => '11:00']);

        $this->actingAs($this->member)->postJson(route('events.calendar.update', [$this->event, $entry]), [
            'title' => 'Survei lokasi',
            'starts_on' => '2026-12-05',
            'ends_on' => '2026-12-07',
        ])->assertOk();

        $entry->refresh();
        $this->assertSame('2026-12-07', $entry->ends_on->toDateString());
        $this->assertNull($entry->starts_at);
        $this->assertNull($entry->ends_at);
    }

    public function test_the_author_changes_and_deletes_their_own_kegiatan(): void
    {
        $entry = $this->entryBy($this->member);

        $this->actingAs($this->member)->getJson(route('events.calendar.show', [$this->event, $entry]))
            ->assertOk()
            ->assertJsonPath('data.title', 'Latihan drama anak')
            ->assertJsonPath('data.ends_on', null)
            ->assertJsonPath('data.starts_at', null);

        $this->change($this->member, $entry, ['title' => 'Latihan drama anak (ulang)', 'starts_on' => '2026-12-20', 'ends_on' => '2026-12-21', 'starts_at' => '', 'ends_at' => ''])->assertOk();

        $entry->refresh();
        $this->assertSame('Latihan drama anak (ulang)', $entry->title);
        $this->assertSame('2026-12-21', $entry->ends_on->toDateString());
        $this->assertNull($entry->starts_at);
        $this->assertSame(1, AuditLog::query()->where('action', 'calendar-entries.updated')->count());

        $this->remove($this->member, $entry)->assertOk();

        $this->assertModelMissing($entry);
        $deleted = AuditLog::query()->where('action', 'calendar-entries.deleted')->sole();
        $this->assertSame('Latihan drama anak (ulang)', $deleted->before['title']);
    }

    public function test_another_member_cannot_change_or_delete_someone_elses_kegiatan(): void
    {
        $entry = $this->entryBy($this->member, ['division_id' => $this->perlengkapan->id]);

        $this->actingAs($this->otherMember)->getJson(route('events.calendar.show', [$this->event, $entry]))->assertForbidden();
        $this->change($this->otherMember, $entry, ['title' => 'Diubah orang lain', 'division_id' => $this->perlengkapan->id])->assertForbidden();
        $this->remove($this->otherMember, $entry)->assertForbidden();

        $this->assertSame('Latihan drama anak', $entry->fresh()->title);
    }

    public function test_a_division_coordinator_manages_kegiatan_of_their_division_but_not_of_another(): void
    {
        $coordinator = User::factory()->create();
        $this->seat($coordinator, $this->event, CommitteeRoleKind::DivisionCoordinator, $this->acara);
        $ownDivision = $this->entryBy($this->member);
        $otherDivision = $this->entryBy($this->otherMember, ['division_id' => $this->perlengkapan->id]);

        $this->change($coordinator, $ownDivision, ['title' => 'Dirapikan koordinator'])->assertOk();
        $this->assertSame('Dirapikan koordinator', $ownDivision->fresh()->title);

        $this->change($coordinator, $otherDivision, ['division_id' => $this->perlengkapan->id])->assertForbidden();
        $this->remove($coordinator, $otherDivision)->assertForbidden();
        $this->remove($coordinator, $ownDivision)->assertOk();
    }

    public function test_moving_a_kegiatan_to_another_division_needs_the_right_to_manage_both(): void
    {
        $coordinator = User::factory()->create();
        $this->seat($coordinator, $this->event, CommitteeRoleKind::DivisionCoordinator, $this->acara);
        $entry = $this->entryBy($this->member);

        $this->change($coordinator, $entry, ['division_id' => $this->perlengkapan->id])->assertForbidden();
        $this->assertSame($this->acara->id, $entry->fresh()->division_id);

        $this->change($this->member, $entry, ['division_id' => $this->perlengkapan->id])->assertOk();
        $this->assertSame($this->perlengkapan->id, $entry->fresh()->division_id);
    }

    public function test_the_secretary_manages_core_team_kegiatan(): void
    {
        $secretary = User::factory()->create();
        $this->seat($secretary, $this->event, CommitteeRoleKind::Secretary);
        $core = $this->entryBy($this->member, ['division_id' => null]);

        $this->change($secretary, $core, ['division_id' => '', 'title' => 'Rapat koordinasi umum'])->assertOk();
        $this->remove($secretary, $core)->assertOk();
        $this->assertModelMissing($core);
    }

    public function test_an_organization_admin_with_manage_work_manages_any_kegiatan(): void
    {
        $admin = $this->userWithPermissions('events.view', 'events.manage-work');
        $entry = $this->entryBy($this->member);

        $this->actingAs($admin)->get(route('events.calendar', $this->event))->assertOk()->assertSee('Tambah Kegiatan');
        $this->change($admin, $entry, ['title' => 'Diubah admin'])->assertOk();
        $this->add($admin)->assertCreated();
        $this->remove($admin, $entry)->assertOk();
    }

    public function test_nothing_changes_once_the_event_is_completed(): void
    {
        $entry = $this->entryBy($this->member);
        $this->event->forceFill(['status' => EventStatus::Completed])->save();

        $this->add($this->member)->assertForbidden();
        $this->change($this->member, $entry, ['title' => 'Terlambat'])->assertForbidden();
        $this->remove($this->member, $entry)->assertForbidden();
        $this->actingAs($this->member)->getJson(route('events.calendar.show', [$this->event, $entry]))->assertForbidden();

        $this->actingAs($this->member)->get(route('events.calendar', $this->event))
            ->assertOk()
            ->assertDontSee('data-entry-add', false)
            ->assertDontSee('kt_modal_calendar_entry"', false);

        $item = collect($this->actingAs($this->member)->getJson(route('events.calendar.feed', [$this->event, 'start' => '2026-11-30', 'end' => '2027-01-11']))->json('data.items'))
            ->firstWhere('type', 'entry');
        $this->assertFalse($item['entry']['can_change']);
        $this->assertSame('Latihan drama anak', $entry->fresh()->title);
    }

    public function test_the_division_must_belong_to_the_event(): void
    {
        $foreign = $this->makeDivision($this->makeEvent(['name' => 'Retret Pemuda']), 'Seksi Acara');

        $this->add($this->member, ['division_id' => $foreign->id])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['division_id']]);
    }

    public function test_a_former_member_can_no_longer_change_their_kegiatan(): void
    {
        $entry = $this->entryBy($this->member);
        $this->membershipOf($this->member)->forceFill(['status' => 'revoked'])->save();
        // Still able to open the event through the organization, but no longer its author in charge.
        $this->member->givePermissionTo('events.view');

        $this->change($this->member, $entry, ['title' => 'Masih bisa?'])->assertForbidden();
        $this->remove($this->member, $entry)->assertForbidden();
        $this->assertSame('Latihan drama anak', $entry->fresh()->title);
    }

    private function membershipOf(User $person): EventMembership
    {
        return $this->event->memberships()->where('user_id', $person->id)->sole();
    }
}
