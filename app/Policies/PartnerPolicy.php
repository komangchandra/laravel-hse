<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;

class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasValidOrganizationRole() && $user->can('partner.view');
    }

    public function view(User $user, Partner $partner): bool
    {
        return $this->viewAny($user) && $user->canAccessOrganizationId($partner->id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Partner $partner): bool
    {
        return false;
    }

    public function delete(User $user, Partner $partner): bool
    {
        return false;
    }
}
