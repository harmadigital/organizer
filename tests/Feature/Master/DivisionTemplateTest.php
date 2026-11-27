<?php

namespace Tests\Feature\Master;

use App\Models\DivisionTemplate;
use App\Models\Organization;

class DivisionTemplateTest extends MasterRecordTestCase
{
    protected function routePrefix(): string
    {
        return 'master.division_template';
    }

    protected function permissionPrefix(): string
    {
        return 'division-templates';
    }

    protected function auditSubject(): string
    {
        return 'division_templates';
    }

    protected function modelClass(): string
    {
        return DivisionTemplate::class;
    }

    protected function pageHeading(): string
    {
        return 'Tambah Template Seksi';
    }

    protected function validPayload(): array
    {
        return [
            'name' => 'Seksi Acara',
            'description' => 'Menyusun dan menjalankan susunan acara.',
            'default_responsibilities' => "Menyusun rundown\nMengoordinasikan pengisi acara",
            'is_active' => true,
        ];
    }

    public function test_two_templates_cannot_share_a_name(): void
    {
        $this->createRecord(['name' => 'Seksi Konsumsi']);
        $other = $this->createRecord(['name' => 'Lainnya']);
        $manager = $this->manager();

        $this->actingAs($manager)->postJson($this->url('insert'), [...$this->validPayload(), 'name' => 'Seksi Konsumsi'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'nama template seksi sudah digunakan.');

        $this->actingAs($manager)->postJson($this->url('update', [$other]), [...$this->validPayload(), 'name' => 'Seksi Konsumsi'])
            ->assertStatus(422);

        $this->actingAs($manager)->postJson($this->url('update', [$other]), [...$this->validPayload(), 'name' => 'Lainnya'])
            ->assertOk();
    }

    public function test_a_name_used_by_another_organization_is_still_available(): void
    {
        $this->createRecord(['name' => 'Seksi Konsumsi'], Organization::factory()->create());

        $this->actingAs($this->manager())->postJson($this->url('insert'), [...$this->validPayload(), 'name' => 'Seksi Konsumsi'])
            ->assertCreated();
    }

    public function test_default_responsibilities_keep_their_line_breaks(): void
    {
        $this->actingAs($this->manager())->postJson($this->url('insert'), $this->validPayload())->assertCreated();

        $this->assertSame(
            "Menyusun rundown\nMengoordinasikan pengisi acara",
            DivisionTemplate::query()->sole()->default_responsibilities,
        );
    }
}
