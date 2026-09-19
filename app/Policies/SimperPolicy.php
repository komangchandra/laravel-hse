<?php

namespace App\Policies;

use App\Models\Simper;
use App\Models\User;

class SimperPolicy
{
    private const EDITABLE_STATUSES = ['pengajuan', 'draft', 'hse_revision_required', 'ktt_revision_required'];

    public function viewAny(User $user): bool
    {
        return $user->hasValidOrganizationRole() && $user->can('simper.view');
    }

    public function view(User $user, Simper $simper): bool
    {
        return $this->viewAny($user) && $user->canAccessOrganizationId($simper->partner_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('safety_mitra') && $user->partner?->isPartner() && $user->can('simper.create');
    }

    public function update(User $user, Simper $simper): bool
    {
        return $this->ownsEditable($user, $simper) && $user->can('simper.update');
    }

    public function delete(User $user, Simper $simper): bool
    {
        return $this->ownsEditable($user, $simper) && $user->can('simper.delete');
    }

    public function generateExamToken(User $user, Simper $simper): bool
    {
        return $this->view($user, $simper)
            && $user->hasRole('hse_owner')
            && $user->can('exam-token.generate');
    }

    private function ownsEditable(User $user, Simper $simper): bool
    {
        return $user->hasRole('safety_mitra')
            && $user->partner_id === $simper->partner_id
            && in_array($simper->status, self::EDITABLE_STATUSES, true);
    }
}
