<?php

namespace App\Http\Controllers;

use App\Enums\PermitApplicationStatus as Status;
use App\Enums\PermitApplicationType as Type;
use App\Exceptions\StaleApplicationVersionException;
use App\Http\Requests\SavePermitApplicationRequest;
use App\Models\AccessArea;
use App\Models\ApplicationDocument;
use App\Models\Manpower;
use App\Models\Partner;
use App\Models\PermitApplication;
use App\Models\SimperCategory;
use App\Services\PermitApplicationWorkflow;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PermitApplicationController extends Controller
{
    public function __construct(private readonly PermitApplicationWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PermitApplication::class);
        $user = $request->user();
        $query = PermitApplication::query()->visibleTo($user)->with(['manpower', 'partner', 'creator']);

        $isReviewQueue = $request->input('queue') === 'review' && $user->hasRole('hse_owner');
        $isKttQueue = $request->input('queue') === 'ktt' && ($user->hasRole('ktt') || $user->isDeveloper());
        if ($request->input('queue') === 'internal' && $user->hasRole('hse_owner')) {
            $query->where('partner_id', $user->partner_id)->where('created_by', $user->id);
        } elseif ($isReviewQueue) {
            $query->where('status', Status::HseReview->value);
        } elseif ($isKttQueue) {
            $query->when(! $user->isDeveloper(), fn ($query) => $query->where('owner_id', $user->ownerOrganizationId()))
                ->where('status', Status::KttReview->value);
        }

        $search = trim((string) $request->input('search'));
        $query->when($search, fn ($query) => $query->where(function ($query) use ($search) {
            $query->where('application_number', 'like', "%{$search}%")
                ->orWhereHas('manpower', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%"));
        }))->when($request->filled('organization_id'), fn ($query) => $query->where('partner_id', $request->integer('organization_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('status_group'), function ($query) use ($request) {
                $statuses = match ($request->input('status_group')) {
                    'revision' => [Status::HseRevisionRequired, Status::KttRevisionRequired],
                    'processing' => [Status::HseReview, Status::WaitingExamSetup, Status::ExamScheduled, Status::ExamInProgress, Status::ExamRetryRequired, Status::ExamPassed, Status::KttReview, Status::Approved],
                    'exam' => [Status::ExamScheduled, Status::ExamInProgress, Status::ExamRetryRequired, Status::ExamPassed, Status::ExamFailedFinal],
                    'exam_setup' => [Status::WaitingExamSetup, Status::ExamRetryRequired],
                    'exam_failed' => [Status::ExamRetryRequired, Status::ExamFailedFinal],
                    'inactive_permit' => [Status::Expired, Status::Revoked],
                    default => [],
                };
                if ($statuses !== []) {
                    $query->whereIn('status', array_map(fn (Status $status) => $status->value, $statuses));
                }
            })
            ->when($request->filled('age_days'), function ($query) use ($request) {
                $days = max(0, min(3650, $request->integer('age_days')));
                $query->where('submitted_at', '<=', now()->subDays($days));
            })
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('date_to')));

        if ($isReviewQueue || $isKttQueue) {
            $query->orderBy('submitted_at');
        }

        return view('dashboard.permit-applications.index', [
            'applications' => $query->latest()->paginate(15)->withQueryString(),
            'search' => $search,
            'types' => Type::cases(),
            'statuses' => Status::cases(),
            'organizations' => Partner::query()->visibleTo($user)->orderBy('legal_name')->get(),
            'reviewCount' => $user->hasRole('hse_owner')
                ? PermitApplication::query()->visibleTo($user)->where('status', Status::HseReview->value)->count()
                : 0,
            'kttReviewCount' => $user->hasRole('ktt')
                ? PermitApplication::query()->where('owner_id', $user->ownerOrganizationId())
                    ->where('status', Status::KttReview->value)->count()
                : 0,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PermitApplication::class);

        return view('dashboard.permit-applications.create', $this->formOptions($request));
    }

    public function store(SavePermitApplicationRequest $request): RedirectResponse
    {
        $this->authorize('create', PermitApplication::class);
        $manpower = Manpower::findOrFail($request->integer('manpower_id'));
        $type = Type::from($request->string('type')->toString());

        try {
            $application = DB::transaction(function () use ($request, $manpower, $type) {
                $application = $this->workflow->createDraft(
                    $request->user(), $manpower, $type, $request->selectedCategories(),
                    $request->draftData(), $request->input('access_area_ids', []),
                );

                return $request->input('action') === 'submit'
                    ? $this->workflow->submit($application, $request->user(), 'Pengajuan dikirim dari form.')
                    : $application;
            });
        } catch (StaleApplicationVersionException|DomainException $exception) {
            return back()->withInput()->withErrors(['application' => $exception->getMessage()]);
        }

        return redirect()->route('dashboard.permit-applications.show', $application)
            ->with('success', $request->input('action') === 'submit' ? 'Pengajuan berhasil dikirim.' : 'Draft berhasil disimpan.');
    }

    public function show(Request $request, PermitApplication $permitApplication): View
    {
        $this->authorize('view', $permitApplication);
        $permitApplication->load([
            'owner', 'partner', 'manpower.documents', 'creator', 'categories', 'accessAreas',
            'reviews.reviewer', 'statusHistories.actor', 'documentSnapshots',
            'examSession.blueprints.simperCategory', 'examSession.blueprints.questionCategory',
            'examSession.tokens.creator',
            'examAttempts.examSession',
            'issuance',
        ]);

        $questionCategories = \App\Models\QuestionCategory::query()
            ->where(fn ($query) => $query->whereNull('owner_id')->orWhere('owner_id', $permitApplication->owner_id))
            ->orderBy('name')->get();

        $user = $request->user();
        $mayViewExamToken = $user->isDeveloper()
            || ($user->hasRole('safety_mitra') && $user->partner_id === $permitApplication->partner_id)
            || ($user->hasRole('hse_owner') && $user->ownerOrganizationId() === $permitApplication->owner_id);
        $visibleExamToken = $mayViewExamToken
            ? $permitApplication->examSession?->tokens
                ->filter(fn ($token) => ! $token->revoked_at && $token->expired_at?->isFuture() && filled($token->display_token))
                ->sortByDesc('created_at')
                ->first()
            : null;

        return view('dashboard.permit-applications.show', [
            'application' => $permitApplication,
            'questionCategories' => $questionCategories,
            'visibleExamToken' => $visibleExamToken,
        ]);
    }

    public function edit(Request $request, PermitApplication $permitApplication): View
    {
        $this->authorize('update', $permitApplication);
        $permitApplication->load(['categories', 'accessAreas', 'manpower.documents']);

        return view('dashboard.permit-applications.edit', ['application' => $permitApplication] + $this->formOptions($request, $permitApplication));
    }

    public function update(SavePermitApplicationRequest $request, PermitApplication $permitApplication): RedirectResponse
    {
        $this->authorize('update', $permitApplication);
        if ($request->integer('manpower_id') !== $permitApplication->manpower_id || $request->input('type') !== $permitApplication->type->value) {
            throw ValidationException::withMessages(['application' => 'Manpower dan jenis pengajuan tidak dapat diganti setelah draft dibuat.']);
        }

        try {
            $application = DB::transaction(function () use ($request, $permitApplication) {
                $application = $this->workflow->saveDraft(
                    $permitApplication, $request->user(), $request->draftData(), $request->selectedCategories(),
                    $request->input('access_area_ids', []), $request->integer('version'),
                );

                return $request->input('action') === 'submit'
                    ? $this->workflow->submit($application, $request->user(), 'Pengajuan dikirim dari form.')
                    : $application;
            });
        } catch (StaleApplicationVersionException|DomainException $exception) {
            return back()->withInput()->withErrors(['application' => $exception->getMessage()]);
        }

        return redirect()->route('dashboard.permit-applications.show', $application)
            ->with('success', $request->input('action') === 'submit' ? 'Pengajuan berhasil dikirim.' : 'Draft berhasil diperbarui.');
    }

    public function submit(Request $request, PermitApplication $permitApplication): RedirectResponse
    {
        $this->authorize('update', $permitApplication);

        return $this->runTransition(fn () => $this->workflow->submit($permitApplication, $request->user(), 'Pengajuan dikirim.'), 'Pengajuan berhasil dikirim.');
    }

    public function cancel(Request $request, PermitApplication $permitApplication): RedirectResponse
    {
        $this->authorize('update', $permitApplication);
        $validated = $request->validate(['notes' => ['required', 'string', 'max:1000']]);

        return $this->runTransition(fn () => $this->workflow->cancel($permitApplication, $request->user(), $validated['notes']), 'Pengajuan berhasil dibatalkan.');
    }

    public function approveHse(Request $request, PermitApplication $permitApplication): RedirectResponse
    {
        $this->authorizeHseReviewRequest($request, $permitApplication);
        $validated = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->runTransition(fn () => $this->workflow->approveHse(
            $permitApplication, $request->user(), $validated['notes'] ?? null, (int) $validated['version']
        ), 'Review HSE disetujui.');
    }

    public function rejectHse(Request $request, PermitApplication $permitApplication): RedirectResponse
    {
        $this->authorizeHseReviewRequest($request, $permitApplication);
        $validated = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        return $this->runTransition(fn () => $this->workflow->rejectHse(
            $permitApplication, $request->user(), $validated['notes'], (int) $validated['version']
        ), 'Pengajuan dikembalikan untuk revisi.');
    }

    public function submitToKtt(Request $request, PermitApplication $permitApplication): RedirectResponse
    {
        $this->authorize('submitToKtt', $permitApplication);
        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        return $this->runTransition(
            fn () => $this->workflow->submitToKtt($permitApplication, $request->user(), $validated['notes'] ?? null),
            'Hasil ujian tervalidasi dan pengajuan dikirim ke antrean KTT.'
        );
    }

    public function approveKtt(Request $request, PermitApplication $permitApplication): RedirectResponse
    {
        $this->authorize('reviewKtt', $permitApplication);
        $validated = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->runTransition(fn () => $this->workflow->approveKtt(
            $permitApplication, $request->user(), $validated['notes'] ?? null, (int) $validated['version']
        ), 'Pengajuan disetujui KTT dan penerbitan permit telah diminta.');
    }

    public function rejectKtt(Request $request, PermitApplication $permitApplication): RedirectResponse
    {
        $this->authorize('reviewKtt', $permitApplication);
        $validated = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        return $this->runTransition(fn () => $this->workflow->rejectKtt(
            $permitApplication, $request->user(), $validated['notes'], (int) $validated['version']
        ), 'Pengajuan dikembalikan KTT untuk revisi.');
    }

    public function documentSnapshot(PermitApplication $permitApplication, ApplicationDocument $documentSnapshot): StreamedResponse
    {
        abort_unless($documentSnapshot->permit_application_id === $permitApplication->id, 404);
        $this->authorize('view', $permitApplication);
        abort_unless(Storage::disk('local')->exists($documentSnapshot->file_path), 404);

        $metadata = $documentSnapshot->metadata ?? [];
        $name = basename((string) ($metadata['original_name'] ?? 'dokumen-'.$documentSnapshot->id));

        return Storage::disk('local')->response($documentSnapshot->file_path, $name, [
            'Content-Type' => $metadata['mime_type'] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function formOptions(Request $request, ?PermitApplication $application = null): array
    {
        $user = $request->user();
        $manpowers = Manpower::query()->with(['partner', 'documents'])->where('is_active', true)
            ->when(! $user->isDeveloper(), fn ($query) => $query->where('partner_id', $user->partner_id))
            ->orderBy('name')->get();
        $ownerId = $application?->owner_id ?? $user->ownerOrganizationId();

        return [
            'manpowers' => $manpowers,
            'types' => Type::cases(),
            'categories' => SimperCategory::query()->when($ownerId, fn ($query) => $query->where(fn ($query) => $query->whereNull('owner_id')->orWhere('owner_id', $ownerId)))->orderBy('name')->get(),
            'accessAreas' => AccessArea::query()->when($ownerId, fn ($query) => $query->where('owner_id', $ownerId))->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function runTransition(callable $transition, string $message): RedirectResponse
    {
        try {
            $application = $transition();
        } catch (StaleApplicationVersionException|DomainException $exception) {
            return back()->withErrors(['application' => $exception->getMessage()]);
        }

        return redirect()->route('dashboard.permit-applications.show', $application)->with('success', $message);
    }

    private function authorizeHseReviewRequest(Request $request, PermitApplication $application): void
    {
        $this->authorize('view', $application);
        abort_unless(
            $request->user()->hasRole('hse_owner') && $request->user()->can('permit-application.review-hse'),
            403
        );
    }
}
