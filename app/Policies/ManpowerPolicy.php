<?php

namespace App\Policies;

use App\Models\Manpower;
use App\Models\User;

class ManpowerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasValidOrganizationRole() && $user->can('manpower.view');
    }

    public function view(User $user, Manpower $manpower): bool
    {
        return $this->viewAny($user) && $user->canAccessOrganizationId($manpower->partner_id);
    }

    public function viewDocument(User $user, Manpower $manpower): bool
    {
        return $this->view($user, $manpower);
    }

    public function create(User $user): bool
    {
        return $user->can('manpower.create') && (
            ($user->hasRole('safety_mitra') && $user->partner?->isPartner())
            || ($user->hasRole('hse_owner') && $user->partner?->isOwner())
        );
    }

    public function update(User $user, Manpower $manpower): bool
    {
        return $this->owns($user, $manpower) && $user->can('manpower.update');
    }

    public function delete(User $user, Manpower $manpower): bool
    {
        return $this->owns($user, $manpower) && $user->can('manpower.delete');
    }

    private function owns(User $user, Manpower $manpower): bool
    {
        return $user->partner_id === $manpower->partner_id && (
            ($user->hasRole('safety_mitra') && $user->partner?->isPartner())
            || ($user->hasRole('hse_owner') && $user->partner?->isOwner())
        );
    }
}
