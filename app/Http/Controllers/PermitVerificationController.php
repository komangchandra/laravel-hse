<?php

namespace App\Http\Controllers;

use App\Models\PermitIssuance;
use App\Services\KttApprovalClaimService;
use DomainException;
use Illuminate\View\View;

class PermitVerificationController extends Controller
{
    public function __construct(private readonly KttApprovalClaimService $claims) {}

    public function show(string $publicId): View
    {
        $issuance = PermitIssuance::query()->where('public_id', $publicId)->firstOrFail();
        $snapshot = $issuance->print_snapshot;

        try {
            $claim = $this->claims->verify(data_get($snapshot, 'ktt_approval.approval_claim_token', ''));
            $validClaim = (int) $claim['application_id'] === $issuance->permit_application_id
                && (int) $claim['owner_id'] === $issuance->owner_id
                && hash_equals((string) $claim['application_number'], (string) $snapshot['application_number'])
                && hash_equals((string) $claim['approved_at'], (string) data_get($snapshot, 'ktt_approval.approved_at'));
        } catch (DomainException) {
            $claim = null;
            $validClaim = false;
        }

        return view('permits.verify', compact('issuance', 'snapshot', 'claim', 'validClaim'));
    }
}
