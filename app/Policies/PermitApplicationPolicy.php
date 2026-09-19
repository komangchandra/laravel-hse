<?php

namespace App\Policies;

use App\Enums\PermitApplicationStatus as Status;
use App\Models\PermitApplication;
use App\Models\User;

class PermitApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasValidOrganizationRole() && $user->can('permit-application.view');
    }

    public function view(User $user, PermitApplication $application): bool
    {
        return $this->viewAny($user) && $user->canAccessOrganizationId($application->partner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('permit-application.create') && (
            ($user->hasRole('safety_mitra') && $user->partner?->isPartner())
            || ($user->hasRole('hse_owner') && $user->partner?->isOwner())
        );
    }

    public function update(User $user, PermitApplication $application): bool
    {
        $ownsApplication = ($user->hasRole('safety_mitra') && $user->partner?->isPartner())
            || ($user->hasRole('hse_owner') && $user->partner?->isOwner());

        return $ownsApplication && $user->partner_id === $application->partner_id
            && $user->can('permit-application.update')
            && in_array($application->status, [Status::Draft, Status::HseRevisionRequired, Status::KttRevisionRequired], true);
    }

    public function reviewHse(User $user, PermitApplication $application): bool
    {
        return $this->view($user, $application)
            && $user->hasRole('hse_owner')
            && $user->can('permit-application.review-hse')
            && $application->status === Status::HseReview;
    }

    public function configureExam(User $user, PermitApplication $application): bool
    {
        return $this->view($user, $application)
            && $user->hasRole('hse_owner')
            && $user->can('permit-application.configure-exam')
            && $application->type->requiresExam()
            && in_array($application->status, [Status::WaitingExamSetup, Status::ExamRetryRequired], true);
    }

    public function submitToKtt(User $user, PermitApplication $application): bool
    {
        return $this->view($user, $application)
            && $user->hasRole('hse_owner')
            && $user->can('permit-application.submit-ktt')
            && $application->status === Status::ExamPassed;
    }

    public function reviewKtt(User $user, PermitApplication $application): bool
    {
        if ($user->isDeveloper()) {
            return $user->can('permit-application.review-ktt');
        }

        return $this->view($user, $application)
            && $user->hasRole('ktt')
            && $user->can('permit-application.review-ktt')
            && $user->partner_id === $application->owner_id;
    }
}
