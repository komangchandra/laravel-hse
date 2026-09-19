<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Simper;
use App\Services\ExamSessionConfigurationService;
use App\Services\OperationalAuditService;
use App\Services\WorkflowNotificationService;
use Illuminate\Http\Request;

class ExamTokenController extends Controller
{
    public function __construct(
        private readonly ExamSessionConfigurationService $configuration,
        private readonly WorkflowNotificationService $notifications,
        private readonly OperationalAuditService $audit,
    ) {}

    public function generate(Simper $simper)
    {
        $this->authorize('generateExamToken', $simper);

        return back()->withErrors([
            'token' => 'Token ujian sekarang dibuat dari sesi pada detail pengajuan.',
        ]);
    }

    public function generateForSession(Request $request, ExamSession $examSession)
    {
        $this->authorize('issueToken', $examSession);
        [$token, $plainToken] = $this->configuration->issueToken($examSession, $request->user());
        $application = $examSession->application()->firstOrFail();
        $this->notifications->examTokenAvailable($application, $examSession->id, $token->expired_at->toIso8601String());
        $this->audit->record(
            'exam_token.issued',
            $examSession,
            $request->user(),
            $application->owner_id,
            null,
            null,
            ['application_id' => $application->id, 'expires_at' => $token->expired_at->toIso8601String()],
        );

        return redirect()->route('dashboard.permit-applications.show', $application)
            ->with('success', 'Token ujian berhasil dibuat dan dapat dilihat oleh HSE serta Safety Mitra pada detail pengajuan.')
            ->with('issued_exam_token', $plainToken)
            ->with('issued_exam_token_expires_at', $token->expired_at->format('d-m-Y H:i'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ExamToken $examToken)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExamToken $examToken)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExamToken $examToken)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExamToken $examToken)
    {
        //
    }
}
