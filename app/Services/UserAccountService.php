<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class UserAccountService
{
    /** @return array<string, mixed> */
    public function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'partner_id' => $user->partner_id,
            'roles' => $user->getRoleNames()->sort()->values()->all(),
            'is_active' => (bool) $user->is_active,
        ];
    }

    public function ensureCriticalOwnerRoleRemains(User $target, array $newRoles, ?int $newPartnerId, bool $newIsActive): void
    {
        if (! $target->is_active || ! $target->partner?->isOwner()) {
            return;
        }

        foreach (['ktt', 'hse_owner'] as $criticalRole) {
            if (! $target->hasRole($criticalRole)) {
                continue;
            }

            $targetStillCoversRole = $newIsActive
                && $newPartnerId === $target->partner_id
                && in_array($criticalRole, $newRoles, true);

            if ($targetStillCoversRole) {
                continue;
            }

            $hasReplacement = User::query()
                ->whereKeyNot($target->id)
                ->where('partner_id', $target->partner_id)
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('name', $criticalRole))
                ->exists();

            if (! $hasReplacement) {
                $label = $criticalRole === 'ktt' ? 'KTT' : 'HSE Owner';
                throw ValidationException::withMessages([
                    'roles' => "Akun ini adalah {$label} aktif terakhir. Buat atau aktifkan pengganti terlebih dahulu.",
                ]);
            }
        }
    }

    public function revokeSessions(User $user): void
    {
        $user->increment('session_version');

        $table = (string) config('session.table', 'sessions');
        if (Schema::hasTable($table)) {
            DB::table($table)->where('user_id', $user->id)->delete();
        }
    }

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    public function audit(User $actor, User $target, string $action, ?array $before, ?array $after): void
    {
        UserLog::create([
            'user_id' => $actor->id,
            'email' => $actor->email,
            'name' => $actor->name,
            'mobile' => $actor->mobile,
            'action' => $action,
            'meta' => [
                'target_user_id' => $target->id,
                'target_email' => $target->email,
                'before' => $before,
                'after' => $after,
            ],
        ]);
    }
}
