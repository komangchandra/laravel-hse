<?php

use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamSessionController;
use App\Http\Controllers\ExamSessionCategoryController;
use App\Http\Controllers\ExamTokenController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\ManpowerController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\QuestionCategoryController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SimperCategoryController;
use App\Http\Controllers\SimperController;
// use App\Models\Simper;

Route::get('/', function () {
    return view('home');
});

Route::middleware('auth')->group(function () {
    // General Access
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('dashboard/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('dashboard/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User/Role Listing (Accessible to anyone with 'user.view' or 'role.view' permissions)
    Route::get('dashboard/users', [UserController::class, 'index'])->name('users.index');
    Route::get('dashboard/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('dashboard/my-activity-logs', [AnalyticsController::class, 'userLogs'])->name('logs.self');
    
    // Self Logs (Assuming you have a LogController)
});

// Admin-Only Actions (Creating, Editing, Deleting)
Route::middleware(['auth', 'role:developer'])->group(function () {
    // We use 'except' because 'index' is already handled above
    Route::resource('dashboard/users', UserController::class)->except(['index']);
    Route::resource('dashboard/roles', RoleController::class)->except(['index']);
    Route::resource('dashboard/permissions', PermissionController::class);

    Route::get('dashboard/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('dashboard/admin/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
});

Route::middleware(['auth', 'role:developer|super-admin|owner'])
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        Route::resource('partners', PartnerController::class);
        Route::resource('question-categories', QuestionCategoryController::class);
        Route::resource('questions', QuestionController::class);
        Route::resource('simper-categories', SimperCategoryController::class);
});

Route::middleware('auth')
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {

        Route::resource('manpowers', ManpowerController::class);
        Route::resource('simpers', SimperController::class);

        Route::get(
            'simpers-pengajuan',
            [SimperController::class, 'pengajuan']
        )->name('simpers.pengajuan');

        Route::resource('exam-sessions', ExamSessionController::class);

        Route::get(
            'exam-sessions/{examSession}/categories',
            [ExamSessionCategoryController::class, 'index']
        )->name('exam-sessions.categories.index');

        Route::get(
            'exam-sessions/{examSession}/categories/create',
            [ExamSessionCategoryController::class, 'create']
        )->name('exam-sessions.categories.create');

        Route::post(
            'exam-sessions/{examSession}/categories',
            [ExamSessionCategoryController::class, 'store']
        )->name('exam-sessions.categories.store');

        Route::post(
            'simpers/{simper}/generate-token',
            [ExamTokenController::class, 'generate']
        )->name('simpers.generate-token');

        Route::get('/exam-results', [ExamResultController::class, 'index'])
            ->name('exam-results.index');

        Route::get('/exam-results/{attempt}', [ExamResultController::class, 'show'])
            ->name('exam-results.show');

        Route::get('/exam-results/{attempt}/pdf', [ExamResultController::class, 'pdf'])
            ->name('exam-results.pdf');
});

Route::get('/exam', [ExamController::class, 'login'])
    ->name('exam.login');

Route::post('/exam', [ExamController::class, 'authenticate'])
    ->name('exam.authenticate');

Route::get('/exam/start', [
    ExamController::class,
    'start'
])->name('exam.start');

Route::post('/exam/begin', [
    ExamController::class,
    'begin'
])->name('exam.begin');

Route::get(
    '/exam/{attempt}/question/{number}',
    [ExamController::class, 'question']
)->name('exam.question');

Route::post(
    '/exam/{attempt}/question/{number}',
    [ExamController::class, 'saveAnswer']
)->name('exam.save-answer');

Route::get('/exam/finish/{attempt}', [ExamController::class, 'finish'])
    ->name('exam.finish');

require __DIR__.'/auth.php';
