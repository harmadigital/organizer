<?php

namespace Tests\Feature\Master;

use App\Models\EventCategory;
use App\Models\Organization;

class EventCategoryTest extends MasterRecordTestCase
{
    protected function routePrefix(): string
    {
        return 'master.event_category';
    }

    protected function permissionPrefix(): string
    {
        return 'event-categories';
    }

    protected function auditSubject(): string
    {
        return 'event_categories';
    }

    protected function modelClass(): string
    {
        return EventCategory::class;
    }

    protected function pageHeading(): string
    {
        return 'Tambah Kategori';
    }

    protected function validPayload(): array
    {
        return ['name' => 'Perayaan Hari Raya', 'description' => 'Natal, Paskah, dan hari raya gerejawi lain.', 'is_active' => true];
    }

    public function test_two_categories_cannot_share_a_name(): void
    {
        $this->createRecord(['name' => 'Retret']);
        $other = $this->createRecord(['name' => 'Lainnya']);
        $manager = $this->manager();

        $this->actingAs($manager)->postJson($this->url('insert'), [...$this->validPayload(), 'name' => 'Retret'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Nama kategori sudah digunakan.');

        $this->actingAs($manager)->postJson($this->url('update', [$other]), [...$this->validPayload(), 'name' => 'Retret'])
            ->assertStatus(422);

        $this->actingAs($manager)->postJson($this->url('update', [$other]), [...$this->validPayload(), 'name' => 'Lainnya'])
            ->assertOk();
    }

    public function test_a_name_used_by_another_organization_is_still_available(): void
    {
        $this->createRecord(['name' => 'Retret'], Organization::factory()->create());

        $this->actingAs($this->manager())->postJson($this->url('insert'), [...$this->validPayload(), 'name' => 'Retret'])
            ->assertCreated();
    }
}
