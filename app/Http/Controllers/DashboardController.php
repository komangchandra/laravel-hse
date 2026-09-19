<?php

namespace App\Http\Controllers;

use App\Enums\PermitApplicationStatus as Status;
use App\Models\ApplicationReview;
use App\Models\AuditLog;
use App\Models\Partner;
use App\Models\PermitApplication;
use App\Models\PermitIssuance;
use App\Models\User;
use App\Models\UserLog;
use App\Models\WorkflowNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $ownerId = $user->isDeveloper() && $request->filled('owner_id') ? $request->integer('owner_id') : $user->ownerOrganizationId();
        $applications = PermitApplication::query()
            ->when($ownerId, fn (Builder $query) => $query->where('owner_id', $ownerId))
            ->when($user->hasRole('safety_mitra'), fn (Builder $query) => $query->where('partner_id', $user->partner_id));
        $issuances = PermitIssuance::query()
            ->when($ownerId, fn (Builder $query) => $query->where('owner_id', $ownerId))
            ->when($user->hasRole('safety_mitra'), fn (Builder $query) => $query->where('partner_id', $user->partner_id));

        $metrics = match (true) {
            $user->hasRole('safety_mitra') => $this->safetyMetrics($applications, $issuances),
            $user->hasRole('hse_owner') => $this->hseMetrics($applications),
            $user->hasRole('ktt') => $this->kttMetrics($applications, $issuances, $user->id),
            default => $this->developerMetrics($applications, $issuances),
        };

        $recentNotifications = WorkflowNotification::query()->where('user_id', $user->id)->latest()->limit(8)->get();
        $recentAudit = AuditLog::query()
            ->when(! $user->isDeveloper(), fn (Builder $query) => $query->where('organization_id', $ownerId))
            ->when($user->hasRole('safety_mitra'), fn (Builder $query) => $query->where('actor_id', $user->id))
            ->latest('occurred_at')->limit(8)->get();
        $recentUserActivity = UserLog::query()
            ->when(! $user->isDeveloper(), function (Builder $query) use ($user) {
                if ($user->hasRole('safety_mitra')) {
                    $query->where('user_id', (string) $user->id);
                } else {
                    $query->whereIn('user_id', User::visibleTo($user)->select('id'));
                }
            })->latest()->limit(8)->get();

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'recentNotifications' => $recentNotifications,
            'recentAudit' => $recentAudit,
            'recentUserActivity' => $recentUserActivity,
            'owners' => $user->isDeveloper() ? Partner::owners()->orderBy('legal_name')->get() : collect(),
            'selectedOwnerId' => $ownerId,
        ]);
    }

    private function safetyMetrics(Builder $applications, Builder $issuances): array
    {
        return [
            $this->metric('Draft', (clone $applications)->where('status', Status::Draft->value)->count(), 'secondary', ['status' => Status::Draft->value]),
            $this->metric('Perlu revisi', (clone $applications)->whereIn('status', [Status::HseRevisionRequired->value, Status::KttRevisionRequired->value])->count(), 'warning', ['status_group' => 'revision']),
            $this->metric('Sedang diproses', (clone $applications)->whereNotIn('status', [Status::Draft->value, Status::HseRevisionRequired->value, Status::KttRevisionRequired->value, Status::Issued->value, Status::Expired->value, Status::Revoked->value, Status::Cancelled->value, Status::ExamFailedFinal->value])->count(), 'primary', ['status_group' => 'processing']),
            $this->metric('Jadwal/hasil ujian', (clone $applications)->whereIn('status', [Status::ExamScheduled->value, Status::ExamInProgress->value, Status::ExamRetryRequired->value, Status::ExamPassed->value, Status::ExamFailedFinal->value])->count(), 'info', ['status_group' => 'exam']),
            $this->metric('Permit aktif', (clone $issuances)->where('status', 'active')->count(), 'success', ['status' => Status::Issued->value]),
            $this->metric('Segera kedaluwarsa', (clone $issuances)->where('status', 'active')->whereBetween('expires_at', [today(), today()->addDays(30)])->count(), 'danger', ['status' => Status::Issued->value]),
        ];
    }

    private function hseMetrics(Builder $applications): array
    {
        return [
            $this->metric('Antrean review HSE', (clone $applications)->where('status', Status::HseReview->value)->count(), 'primary', ['queue' => 'review']),
            $this->metric('Menunggu sesi/retry', (clone $applications)->whereIn('status', [Status::WaitingExamSetup->value, Status::ExamRetryRequired->value])->count(), 'warning', ['status_group' => 'exam_setup']),
            $this->metric('Ujian gagal', (clone $applications)->whereIn('status', [Status::ExamRetryRequired->value, Status::ExamFailedFinal->value])->count(), 'danger', ['status_group' => 'exam_failed']),
            $this->metric('Ujian lulus', (clone $applications)->where('status', Status::ExamPassed->value)->count(), 'success', ['status' => Status::ExamPassed->value]),
            $this->metric('Siap/menunggu KTT', (clone $applications)->where('status', Status::KttReview->value)->count(), 'info', ['status' => Status::KttReview->value]),
        ];
    }

    private function kttMetrics(Builder $applications, Builder $issuances, int $userId): array
    {
        return [
            $this->metric('Antrean persetujuan', (clone $applications)->where('status', Status::KttReview->value)->count(), 'primary', ['queue' => 'ktt']),
            $this->metric('Keputusan saya', ApplicationReview::query()->where('reviewer_id', $userId)->where('stage', 'ktt')->count(), 'info'),
            $this->metric('Permit aktif', (clone $issuances)->where('status', 'active')->count(), 'success', ['status' => Status::Issued->value]),
            $this->metric('Dicabut/kedaluwarsa', (clone $issuances)->whereIn('status', ['revoked', 'expired', 'replaced'])->count(), 'danger', ['status_group' => 'inactive_permit']),
        ];
    }

    private function developerMetrics(Builder $applications, Builder $issuances): array
    {
        return [
            $this->metric('Total pengajuan', (clone $applications)->count(), 'secondary'),
            $this->metric('Antrean HSE', (clone $applications)->where('status', Status::HseReview->value)->count(), 'primary', ['status' => Status::HseReview->value]),
            $this->metric('Antrean KTT', (clone $applications)->where('status', Status::KttReview->value)->count(), 'warning', ['status' => Status::KttReview->value]),
            $this->metric('Permit aktif', (clone $issuances)->where('status', 'active')->count(), 'success', ['status' => Status::Issued->value]),
        ];
    }

    private function metric(string $label, int $count, string $color, array $query = []): array
    {
        return ['label' => $label, 'count' => $count, 'color' => $color, 'url' => route('dashboard.permit-applications.index', $query)];
    }
}
