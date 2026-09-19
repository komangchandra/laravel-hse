<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OperationalAuditService
{
    /** @param array<string, mixed> $metadata */
    public function record(string $action, Model $subject, ?User $actor, ?int $organizationId, ?string $fromStatus = null, ?string $toStatus = null, array $metadata = []): AuditLog
    {
        $hasHttpRequest = app()->bound('request') && request()->route() !== null;

        return AuditLog::create([
            'actor_id' => $actor?->id,
            'actor_organization_id' => $actor?->partner_id,
            'organization_id' => $organizationId,
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'ip_address' => $hasHttpRequest ? request()->ip() : null,
            'user_agent' => $hasHttpRequest ? mb_substr((string) request()->userAgent(), 0, 1000) : null,
            'metadata' => $this->sanitize($metadata),
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function sanitize(array $metadata): array
    {
        $blocked = ['password', 'token', 'secret', 'file', 'document', 'answer', 'approval_claim'];
        $clean = [];
        foreach ($metadata as $key => $value) {
            $normalized = strtolower((string) $key);
            if (collect($blocked)->contains(fn (string $term) => str_contains($normalized, $term))) {
                continue;
            }
            $clean[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $clean;
    }
}
