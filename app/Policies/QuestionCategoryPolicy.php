<?php

namespace App\Policies;

use App\Models\QuestionCategory;
use App\Models\User;

class QuestionCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasValidOrganizationRole() && $user->can('question-category.view');
    }

    public function view(User $user, QuestionCategory $category): bool
    {
        return $this->viewAny($user)
            && ($category->owner_id === null || $category->owner_id === $user->ownerOrganizationId());
    }

    public function create(User $user): bool
    {
        return $user->hasRole('hse_owner') && $user->can('question-category.create');
    }

    public function update(User $user, QuestionCategory $category): bool
    {
        return $this->owned($user, $category) && $user->can('question-category.update');
    }

    public function delete(User $user, QuestionCategory $category): bool
    {
        return $this->owned($user, $category) && $user->can('question-category.delete');
    }

    private function owned(User $user, QuestionCategory $category): bool
    {
        return $user->hasRole('hse_owner') && $category->owner_id !== null && $category->owner_id === $user->ownerOrganizationId();
    }
}
