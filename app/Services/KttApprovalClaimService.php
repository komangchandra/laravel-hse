<?php

namespace App\Services;

use App\Models\PermitApplication;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use JsonException;

class KttApprovalClaimService
{
    private const PURPOSE = 'ktt_approval';

    /**
     * @return array{claim: array<string, int|string>, token: string}
     */
    public function issue(PermitApplication $application, User $reviewer, Carbon $approvedAt): array
    {
        $claim = [
            'purpose' => self::PURPOSE,
            'application_id' => $application->id,
            'application_number' => $application->application_number,
            'owner_id' => $application->owner_id,
            'decision' => 'approved',
            'approved_at' => $approvedAt->toIso8601String(),
            'submission_version' => $application->submission_version,
            'application_version' => $application->version + 1,
            'reviewer_id' => $reviewer->id,
            'reviewer_name' => $reviewer->name,
            'key_version' => (string) config('permit.approval_key_version', 'v1'),
        ];

        try {
            $encoded = json_encode($claim, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new DomainException('Klaim persetujuan KTT tidak dapat dibuat.', previous: $exception);
        }

        return ['claim' => $claim, 'token' => Crypt::encryptString($encoded)];
    }

    /** @return array<string, int|string> */
    public function verify(string $token): array
    {
        try {
            $claim = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException $exception) {
            throw new DomainException('Klaim persetujuan KTT tidak valid atau telah dimanipulasi.', previous: $exception);
        }

        if (! is_array($claim)
            || ($claim['purpose'] ?? null) !== self::PURPOSE
            || ($claim['decision'] ?? null) !== 'approved'
            || ! isset(
                $claim['application_id'],
                $claim['application_number'],
                $claim['owner_id'],
                $claim['approved_at'],
                $claim['submission_version'],
                $claim['application_version'],
                $claim['reviewer_id'],
                $claim['reviewer_name'],
                $claim['key_version'],
            )) {
            throw new DomainException('Struktur klaim persetujuan KTT tidak valid.');
        }

        return $claim;
    }
}
