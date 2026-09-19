<?php

namespace App\Services;

use App\Enums\PermitApplicationStatus as ApplicationStatus;
use App\Enums\PermitApplicationType;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\PermitApplication;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ExamSessionConfigurationService
{
    public function __construct(private readonly PermitApplicationWorkflow $workflow) {}

    /** @param array<string, mixed> $data */
    public function create(PermitApplication $application, User $actor, array $data): ExamSession
    {
        return DB::transaction(function () use ($application, $actor, $data) {
            $current = PermitApplication::query()->lockForUpdate()->findOrFail($application->id);
            $this->ensureConfigurable($current);

            if ($current->examSession()->exists()) {
                throw ValidationException::withMessages([
                    'session' => 'Pengajuan ini sudah memiliki konfigurasi sesi ujian.',
                ]);
            }

            $session = ExamSession::create([
                'permit_application_id' => $current->id,
                'owner_id' => $current->owner_id,
                'created_by' => $actor->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'scheduled_start_at' => $data['scheduled_start_at'],
                'scheduled_end_at' => $data['scheduled_end_at'],
                'duration' => $data['duration'],
                'passing_score' => $data['passing_score'],
                'max_attempts' => $data['max_attempts'],
                'status' => 'draft',
                'is_active' => false,
            ]);

            $this->replaceBlueprint($session, $current, $data['blueprints']);

            return $session->fresh(['application', 'blueprints.simperCategory', 'blueprints.questionCategory']);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function update(ExamSession $session, array $data): ExamSession
    {
        return DB::transaction(function () use ($session, $data) {
            $current = ExamSession::query()->with('application')->lockForUpdate()->findOrFail($session->id);
            $this->ensureConfigurable($current->application);
            if ($current->examAttempts()->where('status', 'in_progress')->exists()
                || ($current->status === 'active' && $current->application->status !== ApplicationStatus::ExamRetryRequired)) {
                throw ValidationException::withMessages(['session' => 'Sesi aktif atau attempt yang sedang berjalan tidak dapat diubah.']);
            }

            $current->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'scheduled_start_at' => $data['scheduled_start_at'],
                'scheduled_end_at' => $data['scheduled_end_at'],
                'duration' => $data['duration'],
                'passing_score' => $data['passing_score'],
                'max_attempts' => $data['max_attempts'],
                'status' => 'draft',
                'is_active' => false,
                'activated_at' => null,
            ]);
            $current->tokens()->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $this->replaceBlueprint($current, $current->application, $data['blueprints']);

            return $current->fresh(['application', 'blueprints.simperCategory', 'blueprints.questionCategory']);
        }, 3);
    }

    public function activate(ExamSession $session, User $actor): ExamSession
    {
        return DB::transaction(function () use ($session, $actor) {
            $current = ExamSession::query()
                ->with(['application', 'blueprints.questionCategory'])
                ->lockForUpdate()->findOrFail($session->id);
            $application = PermitApplication::query()->lockForUpdate()->findOrFail($current->permit_application_id);
            $this->ensureConfigurable($application);
            $this->validateBusinessRules($current, $application);

            $categoryTotals = $current->blueprints
                ->groupBy('question_category_id')
                ->map(fn ($rows) => (int) $rows->sum('question_count'));

            foreach ($categoryTotals as $categoryId => $required) {
                $available = Question::query()->where('category_id', $categoryId)
                    ->validForExam()->count();
                if ($available < $required) {
                    $name = $current->blueprints->firstWhere('question_category_id', $categoryId)?->questionCategory?->name;
                    throw ValidationException::withMessages([
                        'blueprints' => "Stok soal pilihan ganda valid kategori {$name} kurang: tersedia {$available}, dibutuhkan {$required}.",
                    ]);
                }
            }

            $current->categories()->sync($categoryTotals->map(fn ($count) => ['question_count' => $count])->all());
            $current->update([
                'status' => 'active',
                'is_active' => true,
                'activated_at' => now(),
            ]);

            $this->workflow->scheduleExam($application, $actor, ['exam_session_id' => $current->id]);

            return $current->fresh(['application', 'blueprints.simperCategory', 'blueprints.questionCategory']);
        }, 3);
    }

    /** @return array{0: ExamToken, 1: string} */
    public function issueToken(ExamSession $session, User $actor): array
    {
        return DB::transaction(function () use ($session, $actor) {
            $current = ExamSession::query()->with('application')->lockForUpdate()->findOrFail($session->id);
            $application = PermitApplication::query()->lockForUpdate()->findOrFail($current->permit_application_id);
            if ($current->status !== 'active' || ! $current->is_active
                || $application->status !== ApplicationStatus::ExamScheduled) {
                throw ValidationException::withMessages(['token' => 'Token hanya dapat dibuat untuk sesi aktif yang sudah dijadwalkan.']);
            }
            if ($current->scheduled_end_at->isPast()) {
                throw ValidationException::withMessages(['token' => 'Jadwal sesi telah berakhir.']);
            }
            if ($application->examAttempts()->count() >= $current->max_attempts) {
                throw ValidationException::withMessages(['token' => 'Batas attempt pengajuan telah tercapai.']);
            }
            if ($application->examAttempts()->whereIn('status', ['pending', 'in_progress'])->exists()) {
                throw ValidationException::withMessages(['token' => 'Pengajuan masih memiliki attempt aktif yang harus dilanjutkan.']);
            }

            // Generating a token is the HSE's explicit action to open participant access.
            // This also repairs sessions created with the former one-hour-ahead UI default.
            if ($current->scheduled_start_at->isFuture()) {
                $current->update(['scheduled_start_at' => now()]);
            }

            $current->tokens()->whereNull('used_at')->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            do {
                $plainToken = strtoupper(Str::random(8));
                $digest = ExamToken::digest($plainToken);
            } while (ExamToken::query()->where('token', $digest)->exists());

            $expiresAt = now()->addMinutes(60);
            if ($expiresAt->greaterThan($current->scheduled_end_at)) {
                $expiresAt = $current->scheduled_end_at->copy();
            }

            $token = ExamToken::create([
                'permit_application_id' => $application->id,
                'exam_session_id' => $current->id,
                'created_by' => $actor->id,
                'token' => $digest,
                'display_token' => $plainToken,
                'expired_at' => $expiresAt,
            ]);

            return [$token, $plainToken];
        }, 3);
    }

    /** @param array{scheduled_start_at:string, scheduled_end_at:string, reason:string} $data */
    public function scheduleRetry(ExamSession $session, User $actor, array $data): ExamSession
    {
        return DB::transaction(function () use ($session, $actor, $data) {
            $current = ExamSession::query()->with(['application', 'categories'])
                ->lockForUpdate()->findOrFail($session->id);
            $application = PermitApplication::query()->lockForUpdate()->findOrFail($current->permit_application_id);
            if ($application->status !== ApplicationStatus::ExamRetryRequired) {
                throw ValidationException::withMessages(['retry' => 'Pengajuan tidak sedang menunggu ujian ulang.']);
            }
            $attempts = $application->examAttempts()->orderBy('attempt_number')->get();
            if ($attempts->isEmpty() || $attempts->count() >= $current->max_attempts
                || $attempts->last()->status !== 'completed' || $attempts->last()->is_passed) {
                throw ValidationException::withMessages(['retry' => 'Jatah atau hasil attempt tidak memenuhi syarat ujian ulang.']);
            }
            $startsAt = Carbon::parse($data['scheduled_start_at']);
            $endsAt = Carbon::parse($data['scheduled_end_at']);
            if (! $startsAt->lt($endsAt) || $endsAt->isPast()) {
                throw ValidationException::withMessages(['scheduled_end_at' => 'Jadwal ujian ulang tidak valid atau sudah berakhir.']);
            }

            foreach ($current->categories as $category) {
                $available = Question::query()->where('category_id', $category->id)->validForExam()->count();
                if ($available < (int) $category->pivot->question_count) {
                    throw ValidationException::withMessages([
                        'retry' => "Stok soal valid kategori {$category->name} tidak mencukupi untuk ujian ulang.",
                    ]);
                }
            }

            $current->tokens()->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $current->update([
                'scheduled_start_at' => $startsAt,
                'scheduled_end_at' => $endsAt,
                'status' => 'active',
                'is_active' => true,
                'activated_at' => now(),
            ]);
            $this->workflow->scheduleExam($application, $actor, [
                'exam_session_id' => $current->id,
                'retry_attempt_number' => $attempts->count() + 1,
            ], trim($data['reason']));

            return $current->fresh(['application', 'categories']);
        }, 3);
    }

    /** @param array<int, array<string, int>> $blueprints */
    private function replaceBlueprint(ExamSession $session, PermitApplication $application, array $blueprints): void
    {
        $allowedSimperIds = collect($application->submitted_snapshot['categories'] ?? [])->pluck('id')->map(fn ($id) => (int) $id);
        $mappedSimperIds = collect();
        $seen = [];

        $session->blueprints()->delete();
        foreach ($blueprints as $row) {
            $simperCategoryId = (int) $row['simper_category_id'];
            $questionCategoryId = (int) $row['question_category_id'];
            $key = $simperCategoryId.':'.$questionCategoryId;
            if (! $allowedSimperIds->contains($simperCategoryId) || isset($seen[$key])) {
                throw ValidationException::withMessages(['blueprints' => 'Mapping kategori SIMPER pada blueprint tidak valid atau duplikat.']);
            }
            $validQuestionCategory = QuestionCategory::query()->whereKey($questionCategoryId)
                ->where(fn ($query) => $query->whereNull('owner_id')->orWhere('owner_id', $application->owner_id))
                ->exists();
            if (! $validQuestionCategory) {
                throw ValidationException::withMessages(['blueprints' => 'Kategori soal tidak tersedia untuk owner pengajuan.']);
            }

            $seen[$key] = true;
            $mappedSimperIds->push($simperCategoryId);
            $session->blueprints()->create([
                'simper_category_id' => $simperCategoryId,
                'question_category_id' => $questionCategoryId,
                'question_count' => (int) $row['question_count'],
            ]);
        }

        if ($allowedSimperIds->isEmpty() || $allowedSimperIds->diff($mappedSimperIds->unique())->isNotEmpty()) {
            throw ValidationException::withMessages(['blueprints' => 'Setiap kategori SIMPER pengajuan wajib mempunyai mapping kategori soal.']);
        }
    }

    private function validateBusinessRules(ExamSession $session, PermitApplication $application): void
    {
        if ($session->duration !== 60 || $session->passing_score !== 80 || $session->max_attempts !== 2) {
            throw ValidationException::withMessages(['session' => 'Aturan sesi wajib 60 menit, passing grade 80%, dan maksimal dua attempt.']);
        }
        if (! $session->scheduled_start_at || ! $session->scheduled_end_at
            || ! $session->scheduled_start_at->lt($session->scheduled_end_at)
            || $session->scheduled_end_at->isPast()) {
            throw ValidationException::withMessages(['scheduled_end_at' => 'Jadwal sesi tidak valid atau sudah berakhir.']);
        }
        if ($session->owner_id !== $application->owner_id || $session->permit_application_id !== $application->id) {
            throw ValidationException::withMessages(['session' => 'Sesi bukan milik owner dan pengajuan yang sama.']);
        }
        if ($session->blueprints->isEmpty()) {
            throw ValidationException::withMessages(['blueprints' => 'Blueprint soal belum dikonfigurasi.']);
        }
    }

    private function ensureConfigurable(PermitApplication $application): void
    {
        if ($application->type !== PermitApplicationType::MinePermitSimper
            || ! in_array($application->status, [ApplicationStatus::WaitingExamSetup, ApplicationStatus::ExamRetryRequired], true)) {
            throw ValidationException::withMessages([
                'application' => 'Sesi hanya dapat dikonfigurasi untuk pengajuan gabungan yang telah lolos review HSE atau menunggu retry.',
            ]);
        }
    }
}
