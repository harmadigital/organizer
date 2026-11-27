<?php

namespace Tests\Feature\Master;

use App\Models\Organization;
use App\Models\Venue;

class VenueTest extends MasterRecordTestCase
{
    protected function routePrefix(): string
    {
        return 'master.venue';
    }

    protected function permissionPrefix(): string
    {
        return 'venues';
    }

    protected function auditSubject(): string
    {
        return 'venues';
    }

    protected function modelClass(): string
    {
        return Venue::class;
    }

    protected function pageHeading(): string
    {
        return 'Tambah Venue';
    }

    protected function validPayload(): array
    {
        return ['name' => 'Aula Serbaguna', 'address' => 'Jl. Merdeka No. 10', 'capacity' => 350, 'notes' => 'Parkir terbatas', 'is_active' => true];
    }

    public function test_two_venues_cannot_share_a_name(): void
    {
        $this->createRecord(['name' => 'Gedung Gereja']);
        $other = $this->createRecord(['name' => 'Lainnya']);
        $manager = $this->manager();

        $this->actingAs($manager)->postJson($this->url('insert'), [...$this->validPayload(), 'name' => 'Gedung Gereja'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Nama venue sudah digunakan.');

        $this->actingAs($manager)->postJson($this->url('update', [$other]), [...$this->validPayload(), 'name' => 'Gedung Gereja'])
            ->assertStatus(422);

        $this->actingAs($manager)->postJson($this->url('update', [$other]), [...$this->validPayload(), 'name' => 'Lainnya'])
            ->assertOk();
    }

    public function test_a_name_used_by_another_organization_is_still_available(): void
    {
        $this->createRecord(['name' => 'Gedung Gereja'], Organization::factory()->create());

        $this->actingAs($this->manager())->postJson($this->url('insert'), [...$this->validPayload(), 'name' => 'Gedung Gereja'])
            ->assertCreated();
    }

    public function test_capacity_is_optional_but_must_be_a_positive_whole_number(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->postJson($this->url('insert'), [...$this->validPayload(), 'name' => 'Tanpa Kapasitas', 'capacity' => null])
            ->assertCreated();
        $this->assertNull(Venue::query()->where('name', 'Tanpa Kapasitas')->sole()->capacity);

        foreach ([0, -5, 'banyak', 12.5] as $invalidCapacity) {
            $this->actingAs($manager)->postJson($this->url('insert'), [...$this->validPayload(), 'name' => 'Kapasitas Salah', 'capacity' => $invalidCapacity])
                ->assertStatus(422)
                ->assertJsonStructure(['errors' => ['capacity']]);
        }

        $this->assertSame(1, Venue::query()->count());
    }
}
