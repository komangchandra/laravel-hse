<?php

namespace App\Policies;

use App\Models\Manpower;
use App\Models\ManpowerDocument;
use App\Models\User;

class ManpowerDocumentPolicy
{
    public function view(User $user, ManpowerDocument $document): bool
    {
        return $user->can('view', $document->manpower);
    }

    public function create(User $user, Manpower $manpower): bool
    {
        return ! $manpower->trashed() && $user->can('update', $manpower);
    }

    public function delete(User $user, ManpowerDocument $document): bool
    {
        return ! $document->manpower->trashed()
            && $document->verification_status !== 'verified'
            && $user->can('update', $document->manpower);
    }

    public function verify(User $user, ManpowerDocument $document): bool
    {
        return false;
    }
}
