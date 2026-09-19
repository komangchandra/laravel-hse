<?php

namespace App\Policies;

use App\Models\PermitIssuance;
use App\Models\User;

class PermitIssuancePolicy
{
    public function view(User $user, PermitIssuance $issuance): bool
    {
        return $user->hasValidOrganizationRole()
            && $user->can('permit-card.view')
            && $user->canAccessOrganizationId($issuance->partner_id);
    }

    public function download(User $user, PermitIssuance $issuance): bool
    {
        return $this->view($user, $issuance)
            && $user->can('permit-card.download')
            && $issuance->isPrintable();
    }

    public function revoke(User $user, PermitIssuance $issuance): bool
    {
        return $this->view($user, $issuance)
            && $user->hasRole('ktt')
            && $user->can('permit-card.revoke')
            && $user->partner_id === $issuance->owner_id
            && $issuance->status === 'active';
    }
}
