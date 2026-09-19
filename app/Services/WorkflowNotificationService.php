<?php

namespace App\Services;

use App\Models\PermitApplication;
use App\Models\PermitIssuance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowNotificationService
{
    public function applicationTransition(PermitApplication $application, string $action, int $version): void
    {
        [$type, $title, $message, $recipients] = $this->transitionMessage($application, $action);
        $this->send(
            $recipients,
            "application:{$application->id}:version:{$version}:{$action}",
            $type,
            $title,
            $message,
            route('dashboard.permit-applications.show', $application),
            ['application_id' => $application->id, 'application_number' => $application->application_number],
        );
    }

    public function issuance(PermitIssuance $issuance): void
    {
        $application = $issuance->application;
        $recipients = $this->applicantAndOwnerReviewers($application);
        $this->send(
            $recipients,
            "issuance:{$issuance->id}:created",
            'permit_issued',
            'Permit telah diterbitkan',
            "Permit {$issuance->mine_permit_number} telah diterbitkan dan kartu tersedia.",
            route('dashboard.permit-cards.show', $issuance),
            ['issuance_id' => $issuance->id, 'mine_permit_number' => $issuance->mine_permit_number],
        );
    }

    public function examTokenAvailable(PermitApplication $application, int $sessionId, string $expiresAt): void
    {
        $this->send(
            $this->applicantUsers($application),
            "application:{$application->id}:session:{$sessionId}:token-ready:{$expiresAt}",
            'exam_token_ready',
            'Token ujian tersedia',
            "Token ujian untuk {$application->application_number} telah dibuat. Buka detail pengajuan untuk melihat token dan jadwalnya.",
            route('dashboard.permit-applications.show', $application),
            ['application_id' => $application->id, 'exam_session_id' => $sessionId, 'expires_at' => $expiresAt],
        );
    }

    public function expiring(PermitIssuance $issuance): void
    {
        $this->send(
            $this->applicantAndOwnerReviewers($issuance->application),
            "issuance:{$issuance->id}:expiring:{$issuance->expires_at->toDateString()}",
            'permit_expiring',
            'Permit segera kedaluwarsa',
            "Permit {$issuance->mine_permit_number} akan kedaluwarsa pada {$issuance->expires_at->format('d-m-Y')}.",
            route('dashboard.permit-cards.show', $issuance),
            ['issuance_id' => $issuance->id, 'expires_at' => $issuance->expires_at->toDateString()],
        );
    }

    /** @param Collection<int, User> $users
     * @param  array<string, mixed>  $data
     */
    public function send(Collection $users, string $deduplicationKey, string $type, string $title, string $message, ?string $actionUrl = null, array $data = []): void
    {
        $now = now();
        $rows = $users->unique('id')->map(fn (User $user) => [
            'user_id' => $user->id,
            'type' => $type,
            'deduplication_key' => $deduplicationKey,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'data' => json_encode($this->sanitize($data), JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        if ($rows !== []) {
            DB::table('workflow_notifications')->insertOrIgnore($rows);
        }
    }

    /** @return array{string, string, string, Collection<int, User>} */
    private function transitionMessage(PermitApplication $application, string $action): array
    {
        $applicant = $this->applicantUsers($application);
        $hse = User::role('hse_owner')->where('partner_id', $application->owner_id)->where('is_active', true)->get();
        $ktt = User::role('ktt')->where('partner_id', $application->owner_id)->where('is_active', true)->get();

        return match ($action) {
            'submit' => ['application_submitted', 'Pengajuan baru', "{$application->application_number} menunggu review HSE.", $hse],
            'reject_hse' => ['application_rejected', 'Pengajuan dikembalikan HSE', "{$application->application_number} perlu diperbaiki.", $applicant],
            'approve_hse' => $application->type->requiresExam()
                ? ['hse_approved', 'Review HSE disetujui', "{$application->application_number} siap dibuatkan sesi ujian.", $applicant->merge($hse)]
                : ['ktt_queue', 'Pengajuan masuk antrean KTT', "{$application->application_number} siap diputuskan KTT.", $applicant->merge($ktt)],
            'schedule_exam' => ['exam_scheduled', 'Ujian dijadwalkan', "Sesi ujian {$application->application_number} telah dijadwalkan.", $applicant],
            'record_exam_result' => ['exam_result', 'Hasil ujian tersedia', "Hasil ujian {$application->application_number} telah difinalisasi.", $applicant->merge($hse)],
            'submit_to_ktt' => ['ktt_queue', 'Pengajuan masuk antrean KTT', "{$application->application_number} siap diputuskan KTT.", $applicant->merge($ktt)],
            'reject_ktt' => ['application_rejected', 'Pengajuan dikembalikan KTT', "{$application->application_number} perlu diperbaiki.", $applicant->merge($hse)],
            'approve_ktt' => ['ktt_approved', 'Pengajuan disetujui KTT', "{$application->application_number} telah disetujui KTT.", $applicant->merge($hse)],
            default => ['application_status', 'Status pengajuan diperbarui', "Status {$application->application_number} telah diperbarui.", $applicant],
        };
    }

    /** @return Collection<int, User> */
    private function applicantUsers(PermitApplication $application): Collection
    {
        return User::query()->where('is_active', true)->where(function ($query) use ($application) {
            $query->whereKey($application->created_by)
                ->orWhere(fn ($query) => $query->where('partner_id', $application->partner_id)->whereHas('roles', fn ($query) => $query->where('name', 'safety_mitra')));
        })->get();
    }

    /** @return Collection<int, User> */
    private function applicantAndOwnerReviewers(PermitApplication $application): Collection
    {
        return $this->applicantUsers($application)->merge(
            User::query()->where('partner_id', $application->owner_id)->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->whereIn('name', ['hse_owner', 'ktt']))->get()
        );
    }

    /** @return array<string, mixed> */
    private function sanitize(array $data): array
    {
        $blocked = ['password', 'token', 'secret', 'file', 'document', 'answer', 'approval_claim'];

        return collect($data)->reject(function ($value, $key) use ($blocked) {
            $normalized = strtolower((string) $key);

            return collect($blocked)->contains(fn (string $term) => str_contains($normalized, $term));
        })->map(fn ($value) => is_array($value) ? $this->sanitize($value) : $value)->all();
    }
}
