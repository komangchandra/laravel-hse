<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;

class ExamAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasValidOrganizationRole() && $user->can('exam-result.view');
    }

    public function view(User $user, ExamAttempt $attempt): bool
    {
        $organizationId = $attempt->application?->partner_id ?? $attempt->simper?->partner_id;

        return $this->viewAny($user) && $organizationId !== null
            && $user->canAccessOrganizationId($organizationId);
    }

    public function download(User $user, ExamAttempt $attempt): bool
    {
        return $this->view($user, $attempt) && $user->can('exam-result.pdf');
    }
}
