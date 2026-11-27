<?php

namespace Tests\Feature\Services;

use App\Http\Middleware\AssignCorrelationId;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_records_the_actor_action_and_subject(): void
    {
        $actor = User::factory()->create(['name' => 'Maria Sitompul']);
        $subject = User::factory()->create();
        $actor->assignRole(Role::create(['name' => 'Organization Admin']));

        $entry = $this->app->make(AuditLogger::class)->record(
            action: 'users.updated',
            auditable: $subject,
            reason: 'Koreksi data',
            eventId: 12,
            organizationId: 3,
            actor: $actor,
        );

        $this->assertModelExists($entry);
        $this->assertSame($actor->id, $entry->actor_user_id);
        $this->assertSame('Maria Sitompul', $entry->actor_name);
        $this->assertSame('Organization Admin', $entry->effective_role);
        $this->assertSame('users.updated', $entry->action);
        $this->assertTrue($entry->auditable->is($subject));
        $this->assertSame('Koreksi data', $entry->reason);
        $this->assertSame(12, $entry->event_id);
        $this->assertSame(3, $entry->organization_id);
        $this->assertNotNull($entry->created_at);
    }

    public function test_it_uses_the_authenticated_user_when_no_actor_is_given(): void
    {
        $this->actingAs($user = User::factory()->create());

        $entry = $this->app->make(AuditLogger::class)->record('dashboard.viewed');

        $this->assertSame($user->id, $entry->actor_user_id);
        $this->assertNull($entry->effective_role);
        $this->assertNull($entry->auditable_type);
    }

    public function test_it_records_system_actions_without_an_actor(): void
    {
        $entry = $this->app->make(AuditLogger::class)->record('system.maintenance');

        $this->assertNull($entry->actor_user_id);
        $this->assertNull($entry->actor_name);
        $this->assertNull($entry->ip_address);
    }

    public function test_an_explicit_effective_role_overrides_the_organization_roles(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole(Role::create(['name' => 'Organization Admin']));

        $entry = $this->app->make(AuditLogger::class)->record('budget.approved', effectiveRole: 'Ketua', actor: $actor);

        $this->assertSame('Ketua', $entry->effective_role);
    }

    public function test_it_keeps_only_the_attributes_that_changed(): void
    {
        $entry = $this->app->make(AuditLogger::class)->record(
            action: 'users.updated',
            before: ['name' => 'Lama', 'email' => 'sama@example.com', 'phone' => '0811'],
            after: ['name' => 'Baru', 'email' => 'sama@example.com', 'status' => 'active'],
        );

        $this->assertSame(['name' => 'Lama', 'phone' => '0811'], $entry->before);
        $this->assertSame(['name' => 'Baru', 'status' => 'active'], $entry->after);
    }

    public function test_it_keeps_the_whole_payload_for_a_creation_or_deletion(): void
    {
        $logger = $this->app->make(AuditLogger::class);

        $created = $logger->record('users.created', after: ['name' => 'Baru']);
        $deleted = $logger->record('users.deleted', before: ['name' => 'Lama']);

        $this->assertNull($created->before);
        $this->assertSame(['name' => 'Baru'], $created->after);
        $this->assertSame(['name' => 'Lama'], $deleted->before);
        $this->assertNull($deleted->after);
    }

    public function test_it_redacts_sensitive_attributes_at_any_depth(): void
    {
        $entry = $this->app->make(AuditLogger::class)->record(
            action: 'users.updated',
            before: ['password' => 'old-secret', 'profile' => ['Two_Factor_Secret' => 'abc', 'city' => 'Medan']],
            after: ['password' => 'new-secret', 'profile' => ['Two_Factor_Secret' => 'xyz', 'city' => 'Medan']],
        );

        $this->assertSame('[REDACTED]', $entry->before['password']);
        $this->assertSame('[REDACTED]', $entry->after['password']);
        $this->assertSame(['Two_Factor_Secret' => '[REDACTED]', 'city' => 'Medan'], $entry->after['profile']);

        $stored = $entry->getRawOriginal('before').$entry->getRawOriginal('after');

        foreach (['old-secret', 'new-secret', 'abc', 'xyz'] as $sensitiveValue) {
            $this->assertStringNotContainsString($sensitiveValue, $stored);
        }
    }

    public function test_entries_written_in_one_request_share_a_correlation_id(): void
    {
        Context::add(AuditLogger::CORRELATION_KEY, $correlationId = (string) Str::uuid());
        $logger = $this->app->make(AuditLogger::class);

        $first = $logger->record('first.action');
        $second = $logger->record('second.action');

        $this->assertSame($correlationId, $first->correlation_id);
        $this->assertSame($correlationId, $second->correlation_id);
    }

    public function test_it_generates_a_correlation_id_outside_a_request(): void
    {
        Context::forget(AuditLogger::CORRELATION_KEY);
        $logger = $this->app->make(AuditLogger::class);

        $first = $logger->record('first.action');
        $second = $logger->record('second.action');

        $this->assertTrue(Str::isUuid($first->correlation_id));
        $this->assertSame($first->correlation_id, $second->correlation_id);
    }

    public function test_a_request_gets_a_correlation_id_that_matches_its_audit_entries(): void
    {
        Route::get('/_test/audit', fn (AuditLogger $logger) => $logger->record('test.action')->correlation_id);

        $response = $this->get('/_test/audit');

        $response->assertOk();
        $this->assertTrue(Str::isUuid($response->headers->get(AssignCorrelationId::HEADER)));
        $this->assertSame($response->headers->get(AssignCorrelationId::HEADER), $response->getContent());
        $this->assertSame('127.0.0.1', AuditLog::query()->sole()->ip_address);
    }

    public function test_each_request_gets_its_own_correlation_id(): void
    {
        $first = $this->get('/login')->headers->get(AssignCorrelationId::HEADER);
        $second = $this->get('/login')->headers->get(AssignCorrelationId::HEADER);

        $this->assertNotSame($first, $second);
    }

    public function test_entries_cannot_be_modified(): void
    {
        $entry = AuditLog::factory()->create();

        $this->expectException(LogicException::class);

        $entry->update(['action' => 'tampered']);
    }

    public function test_entries_cannot_be_deleted(): void
    {
        $entry = AuditLog::factory()->create();

        try {
            $entry->delete();
            $this->fail('Deleting an audit entry should not be possible.');
        } catch (LogicException) {
            $this->assertModelExists($entry);
        }
    }

    public function test_an_entry_survives_the_deletion_of_its_actor(): void
    {
        $actor = User::factory()->create(['name' => 'Yohanes']);
        $entry = $this->app->make(AuditLogger::class)->record('users.updated', actor: $actor);

        $actor->delete();

        $this->assertSame($actor->id, $entry->fresh()->actor_user_id);
        $this->assertSame('Yohanes', $entry->fresh()->actor_name);
        $this->assertNull($entry->fresh()->actor);
    }

    public function test_it_refuses_a_subject_that_is_not_a_registered_polymorphic_type(): void
    {
        $this->expectException(ClassMorphViolationException::class);

        $this->app->make(AuditLogger::class)->record('permissions.updated', Permission::create(['name' => 'unmapped.view', 'guard_name' => 'web']));
    }
}
