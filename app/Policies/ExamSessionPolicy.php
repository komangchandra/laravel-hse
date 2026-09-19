<?php

namespace App\Policies;

use App\Enums\PermitApplicationStatus as Status;
use App\Models\ExamSession;
use App\Models\User;

class ExamSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasValidOrganizationRole() && $user->can('exam-session.view');
    }

    public function view(User $user, ExamSession $session): bool
    {
        return $this->viewAny($user) && $session->owner_id === $user->ownerOrganizationId();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('hse_owner') && $user->can('exam-session.create');
    }

    public function update(User $user, ExamSession $session): bool
    {
        return $this->view($user, $session) && $user->hasRole('hse_owner')
            && $user->can('exam-session.update')
            && (! $session->application || $user->can('configureExam', $session->application));
    }

    public function delete(User $user, ExamSession $session): bool
    {
        return $this->view($user, $session) && $user->hasRole('hse_owner') && $user->can('exam-session.delete');
    }

    public function issueToken(User $user, ExamSession $session): bool
    {
        return $this->view($user, $session)
            && $user->hasRole('hse_owner')
            && $user->can('exam-token.generate')
            && $session->is_active
            && $session->application?->status === Status::ExamScheduled;
    }

    public function scheduleRetry(User $user, ExamSession $session): bool
    {
        return $this->view($user, $session)
            && $user->hasRole('hse_owner')
            && $user->can('permit-application.configure-exam')
            && $session->application?->status === Status::ExamRetryRequired;
    }
}
