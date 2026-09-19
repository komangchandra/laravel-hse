<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasValidOrganizationRole() && $user->can('user.view');
    }

    public function view(User $user, User $subject): bool
    {
        return $this->viewAny($user) && $user->canAccessOrganizationId($subject->partner_id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $subject): bool
    {
        return false;
    }

    public function delete(User $user, User $subject): bool
    {
        return false;
    }
}
