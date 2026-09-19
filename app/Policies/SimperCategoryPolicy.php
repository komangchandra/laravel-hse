<?php

namespace App\Policies;

use App\Models\SimperCategory;
use App\Models\User;

class SimperCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasValidOrganizationRole() && $user->can('simper-category.view');
    }

    public function view(User $user, SimperCategory $category): bool
    {
        return $this->viewAny($user)
            && ($category->owner_id === null || $category->owner_id === $user->ownerOrganizationId());
    }

    public function create(User $user): bool
    {
        return $user->hasRole('hse_owner') && $user->can('simper-category.create');
    }

    public function update(User $user, SimperCategory $category): bool
    {
        return $this->owned($user, $category) && $user->can('simper-category.update');
    }

    public function delete(User $user, SimperCategory $category): bool
    {
        return $this->owned($user, $category) && $user->can('simper-category.delete');
    }

    private function owned(User $user, SimperCategory $category): bool
    {
        return $user->hasRole('hse_owner') && $category->owner_id !== null && $category->owner_id === $user->ownerOrganizationId();
    }
}
