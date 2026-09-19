<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\PermitApplication;
use App\Services\ExamSessionConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamSessionController extends Controller
{
    public function __construct(private readonly ExamSessionConfigurationService $configuration) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ExamSession::class);
        $examSessions = ExamSession::visibleTo($request->user())
            ->with(['owner', 'application.manpower'])->latest()->paginate(10);

        return view('dashboard.exam-sessions.index', compact('examSessions'));
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', ExamSession::class);

        return redirect()->route('dashboard.permit-applications.index', ['status' => 'waiting_exam_setup'])
            ->with('success', 'Pilih pengajuan yang telah lolos review HSE untuk membuat sesi ujian.');
    }

    public function store(Request $request): RedirectResponse
    {
        $applicationId = $request->validate([
            'permit_application_id' => ['required', 'integer', 'exists:permit_applications,id'],
        ])['permit_application_id'];
        $application = PermitApplication::findOrFail($applicationId);
        $this->authorize('configureExam', $application);
        $session = $this->configuration->create($application, $request->user(), $this->validated($request));

        if ($request->boolean('activate')) {
            $session = $this->configuration->activate($session, $request->user());
        }

        return redirect()->route('dashboard.permit-applications.show', $application)
            ->with('success', $session->is_active ? 'Sesi ujian berhasil diaktifkan.' : 'Draft sesi ujian berhasil disimpan.');
    }

    public function show(ExamSession $examSession): View
    {
        $this->authorize('view', $examSession);
        $examSession->load([
            'categories', 'owner', 'application.manpower', 'creator',
            'blueprints.simperCategory', 'blueprints.questionCategory', 'tokens.creator',
        ]);

        return view('dashboard.exam-sessions.show', compact('examSession'));
    }

    public function edit(ExamSession $examSession): RedirectResponse
    {
        $this->authorize('update', $examSession);
        abort_unless($examSession->permit_application_id, 404);

        return redirect()->route('dashboard.permit-applications.show', $examSession->permit_application_id);
    }

    public function update(Request $request, ExamSession $examSession): RedirectResponse
    {
        $this->authorize('update', $examSession);
        $session = $this->configuration->update($examSession, $this->validated($request));

        return redirect()->route('dashboard.permit-applications.show', $session->permit_application_id)
            ->with('success', 'Konfigurasi sesi ujian berhasil diperbarui.');
    }

    public function activate(Request $request, ExamSession $examSession): RedirectResponse
    {
        $this->authorize('update', $examSession);
        $session = $this->configuration->activate($examSession, $request->user());

        return redirect()->route('dashboard.permit-applications.show', $session->permit_application_id)
            ->with('success', 'Sesi ujian berhasil diaktifkan dan pengajuan telah dijadwalkan.');
    }

    public function scheduleRetry(Request $request, ExamSession $examSession): RedirectResponse
    {
        $this->authorize('scheduleRetry', $examSession);
        $validated = $request->validate([
            'scheduled_start_at' => ['required', 'date'],
            'scheduled_end_at' => ['required', 'date', 'after:scheduled_start_at'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $session = $this->configuration->scheduleRetry($examSession, $request->user(), $validated);

        return redirect()->route('dashboard.permit-applications.show', $session->permit_application_id)
            ->with('success', 'Ujian ulang berhasil dijadwalkan. Buat token baru untuk peserta.');
    }

    public function destroy(ExamSession $examSession): RedirectResponse
    {
        $this->authorize('delete', $examSession);
        if ($examSession->is_active || $examSession->examAttempts()->exists()) {
            return back()->withErrors(['exam_session' => 'Sesi aktif atau yang sudah memiliki histori ujian tidak dapat dihapus.']);
        }
        $applicationId = $examSession->permit_application_id;
        $examSession->delete();

        return $applicationId
            ? redirect()->route('dashboard.permit-applications.show', $applicationId)->with('success', 'Draft sesi ujian dihapus.')
            : redirect()->route('dashboard.exam-sessions.index')->with('success', 'Sesi ujian dihapus.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'scheduled_start_at' => ['required', 'date'],
            'scheduled_end_at' => ['required', 'date', 'after:scheduled_start_at'],
            'duration' => ['required', 'integer', Rule::in([60])],
            'passing_score' => ['required', 'integer', Rule::in([80])],
            'max_attempts' => ['required', 'integer', Rule::in([2])],
            'blueprints' => ['required', 'array', 'min:1'],
            'blueprints.*.simper_category_id' => ['required', 'integer', 'exists:simper_categories,id'],
            'blueprints.*.question_category_id' => ['required', 'integer', 'exists:question_categories,id'],
            'blueprints.*.question_count' => ['required', 'integer', 'min:1', 'max:500'],
            'activate' => ['nullable', 'boolean'],
        ]);
    }
}
