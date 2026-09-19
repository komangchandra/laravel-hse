<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\ExamSessionCategoryController;
use App\Http\Controllers\ExamSessionController;
use App\Http\Controllers\ExamTokenController;
use App\Http\Controllers\ManpowerController;
use App\Http\Controllers\ManpowerDocumentController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PermitApplicationController;
use App\Http\Controllers\PermitCardController;
use App\Http\Controllers\PermitVerificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionCategoryController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SimperCategoryController;
use App\Http\Controllers\SimperController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkflowNotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'));

Route::middleware(['auth', 'account.active'])->group(function () {
    Route::get('dashboard/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('dashboard/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('dashboard/my-activity-logs', [AnalyticsController::class, 'userLogs'])->name('logs.self');
    Route::get('dashboard/notifications', [WorkflowNotificationController::class, 'index'])->name('dashboard.notifications.index');
    Route::post('dashboard/notifications/read-all', [WorkflowNotificationController::class, 'readAll'])->name('dashboard.notifications.read-all');
    Route::post('dashboard/notifications/{notification}/read', [WorkflowNotificationController::class, 'read'])->name('dashboard.notifications.read');
    Route::get('dashboard/audit-logs', [AuditLogController::class, 'index'])->name('dashboard.audit-logs.index');
});

Route::middleware(['auth', 'account.active', 'tenant.access'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('dashboard/users', [UserController::class, 'index'])->name('users.index');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('partners', [PartnerController::class, 'index'])->name('partners.index');

        Route::get('manpowers/{manpower}/photo', [ManpowerController::class, 'photo'])->name('manpowers.photo');
        Route::get('manpowers/{manpower}/document', [ManpowerController::class, 'document'])->name('manpowers.document');
        Route::post('manpowers/{manpower}/restore', [ManpowerController::class, 'restore'])->name('manpowers.restore');
        Route::post('manpowers/{manpower}/documents', [ManpowerDocumentController::class, 'store'])->name('manpowers.documents.store');
        Route::get('manpowers/{manpower}/documents/{document}', [ManpowerDocumentController::class, 'download'])->name('manpowers.documents.download');
        Route::patch('manpowers/{manpower}/documents/{document}/verify', [ManpowerDocumentController::class, 'verify'])->name('manpowers.documents.verify');
        Route::delete('manpowers/{manpower}/documents/{document}', [ManpowerDocumentController::class, 'destroy'])->name('manpowers.documents.destroy');
        Route::resource('manpowers', ManpowerController::class);

        Route::post('permit-applications/{permitApplication}/submit', [PermitApplicationController::class, 'submit'])->name('permit-applications.submit');
        Route::post('permit-applications/{permitApplication}/cancel', [PermitApplicationController::class, 'cancel'])->name('permit-applications.cancel');
        Route::post('permit-applications/{permitApplication}/hse-approve', [PermitApplicationController::class, 'approveHse'])->name('permit-applications.hse-approve');
        Route::post('permit-applications/{permitApplication}/hse-reject', [PermitApplicationController::class, 'rejectHse'])->name('permit-applications.hse-reject');
        Route::post('permit-applications/{permitApplication}/submit-ktt', [PermitApplicationController::class, 'submitToKtt'])->name('permit-applications.submit-ktt');
        Route::post('permit-applications/{permitApplication}/ktt-approve', [PermitApplicationController::class, 'approveKtt'])->name('permit-applications.ktt-approve');
        Route::post('permit-applications/{permitApplication}/ktt-reject', [PermitApplicationController::class, 'rejectKtt'])->name('permit-applications.ktt-reject');
        Route::get('permit-applications/{permitApplication}/documents/{documentSnapshot}', [PermitApplicationController::class, 'documentSnapshot'])->name('permit-applications.documents.show');
        Route::resource('permit-applications', PermitApplicationController::class)
            ->parameters(['permit-applications' => 'permitApplication'])->except(['destroy']);
        Route::get('permit-cards/{issuance}', [PermitCardController::class, 'show'])->name('permit-cards.show');
        Route::get('permit-cards/{issuance}/pdf', [PermitCardController::class, 'pdf'])->name('permit-cards.pdf');
        Route::post('permit-cards/{issuance}/revoke', [PermitCardController::class, 'revoke'])->name('permit-cards.revoke');

        Route::get('simpers-pengajuan', [SimperController::class, 'pengajuan'])->name('simpers.pengajuan');
        Route::resource('simpers', SimperController::class);
        Route::post('simpers/{simper}/generate-token', [ExamTokenController::class, 'generate'])->name('simpers.generate-token');

        Route::resource('question-categories', QuestionCategoryController::class)->except(['show']);
        Route::get('questions/{question}/photo', [QuestionController::class, 'photo'])->name('questions.photo');
        Route::resource('questions', QuestionController::class);
        Route::resource('simper-categories', SimperCategoryController::class)->except(['show']);

        Route::resource('exam-sessions', ExamSessionController::class);
        Route::post('exam-sessions/{examSession}/activate', [ExamSessionController::class, 'activate'])
            ->name('exam-sessions.activate');
        Route::post('exam-sessions/{examSession}/retry', [ExamSessionController::class, 'scheduleRetry'])
            ->name('exam-sessions.retry');
        Route::post('exam-sessions/{examSession}/token', [ExamTokenController::class, 'generateForSession'])
            ->name('exam-sessions.generate-token');
        Route::get('exam-sessions/{examSession}/categories/create', [ExamSessionCategoryController::class, 'create'])
            ->name('exam-sessions.categories.create');
        Route::post('exam-sessions/{examSession}/categories', [ExamSessionCategoryController::class, 'store'])
            ->name('exam-sessions.categories.store');

        Route::get('exam-results', [ExamResultController::class, 'index'])->name('exam-results.index');
        Route::get('exam-results/{attempt}', [ExamResultController::class, 'show'])->name('exam-results.show');
        Route::get('exam-results/{attempt}/pdf', [ExamResultController::class, 'pdf'])->name('exam-results.pdf');
    });
});

Route::middleware(['auth', 'account.active', 'tenant.access', 'role:developer'])->group(function () {
    Route::resource('dashboard/users', UserController::class)->except(['index', 'show']);
    Route::resource('dashboard/roles', RoleController::class)->except(['show']);
    Route::resource('dashboard/permissions', PermissionController::class)->except(['show']);

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::resource('partners', PartnerController::class)->except(['index', 'show']);
    });

    Route::get('dashboard/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('dashboard/admin/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
});

Route::get('/exam', [ExamController::class, 'login'])->name('exam.login');
Route::get('/permit/verify/{publicId}', [PermitVerificationController::class, 'show'])
    ->whereUuid('publicId')->name('permits.verify');
Route::post('/exam', [ExamController::class, 'authenticate'])->name('exam.authenticate');
Route::get('/exam/start', [ExamController::class, 'start'])->name('exam.start');
Route::post('/exam/begin', [ExamController::class, 'begin'])->name('exam.begin');
Route::get('/exam/question-assets/{attemptQuestion}', [ExamController::class, 'questionPhoto'])->name('exam.question-photo');
Route::get('/exam/{attempt}/question/{number}', [ExamController::class, 'question'])->name('exam.question');
Route::post('/exam/{attempt}/question/{number}', [ExamController::class, 'saveAnswer'])->name('exam.save-answer');
Route::post('/exam/finish/{attempt}', [ExamController::class, 'finish'])->name('exam.finish');

require __DIR__.'/auth.php';
