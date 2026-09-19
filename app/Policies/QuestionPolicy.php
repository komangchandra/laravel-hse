<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasValidOrganizationRole() && $user->can('question.view');
    }

    public function view(User $user, Question $question): bool
    {
        return $this->viewAny($user)
            && ($question->category?->owner_id === null || $question->category?->owner_id === $user->ownerOrganizationId());
    }

    public function create(User $user): bool
    {
        return $user->hasRole('hse_owner') && $user->can('question.create');
    }

    public function update(User $user, Question $question): bool
    {
        return $this->owned($user, $question) && $user->can('question.update');
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->owned($user, $question) && $user->can('question.delete');
    }

    private function owned(User $user, Question $question): bool
    {
        return $user->hasRole('hse_owner')
            && $question->category?->owner_id !== null
            && $question->category?->owner_id === $user->ownerOrganizationId();
    }
}
