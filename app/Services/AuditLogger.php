<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

class AuditLogger
{
    public const CORRELATION_KEY = 'correlation_id';

    public const IP_ADDRESS_KEY = 'ip_address';

    private const REDACTED = '[REDACTED]';

    /**
     * Write one append-only audit entry.
     *
     * When both payloads are given only the attributes that differ are kept.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function record(
        string $action,
        ?Model $auditable = null,
        array $before = [],
        array $after = [],
        ?string $reason = null,
        ?int $eventId = null,
        ?int $organizationId = null,
        ?string $effectiveRole = null,
        ?User $actor = null,
    ): AuditLog {
        $actor ??= Auth::user();

        if ($before !== [] && $after !== []) {
            [$before, $after] = $this->onlyChanged($before, $after);
        }

        return AuditLog::create([
            'organization_id' => $organizationId,
            'event_id' => $eventId,
            'actor_user_id' => $actor?->getKey(),
            'actor_name' => $actor?->name,
            'effective_role' => $effectiveRole ?? $this->organizationRolesOf($actor),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'before' => $before === [] ? null : $this->redact($before),
            'after' => $after === [] ? null : $this->redact($after),
            'reason' => $reason,
            'correlation_id' => $this->correlationId(),
            'ip_address' => Context::getHidden(self::IP_ADDRESS_KEY),
        ]);
    }

    /**
     * The identifier shared by every audit entry and log line of the current request or job.
     */
    public function correlationId(): string
    {
        if (! Context::has(self::CORRELATION_KEY)) {
            Context::add(self::CORRELATION_KEY, (string) Str::uuid());
        }

        return Context::get(self::CORRELATION_KEY);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function onlyChanged(array $before, array $after): array
    {
        $changedBefore = [];
        $changedAfter = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            $hasBefore = array_key_exists($key, $before);
            $hasAfter = array_key_exists($key, $after);

            if ($hasBefore && $hasAfter && $before[$key] === $after[$key]) {
                continue;
            }

            if ($hasBefore) {
                $changedBefore[$key] = $before[$key];
            }

            if ($hasAfter) {
                $changedAfter[$key] = $after[$key];
            }
        }

        return [$changedBefore, $changedAfter];
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    private function redact(array $payload): array
    {
        $redactedKeys = array_map(Str::lower(...), config('organizer.audit.redacted_keys', []));

        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array(Str::lower($key), $redactedKeys, true)) {
                $payload[$key] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->redact($value);
            }
        }

        return $payload;
    }

    private function organizationRolesOf(?User $actor): ?string
    {
        if ($actor === null) {
            return null;
        }

        $roleNames = $actor->getRoleNames();

        return $roleNames->isEmpty() ? null : $roleNames->sort()->implode(', ');
    }
}
