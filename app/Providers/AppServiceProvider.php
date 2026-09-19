<?php

namespace App\Providers;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\Manpower;
use App\Models\ManpowerDocument;
use App\Models\Partner;
use App\Models\PermitApplication;
use App\Models\PermitIssuance;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\Simper;
use App\Models\SimperCategory;
use App\Models\User;
use App\Policies\ExamAttemptPolicy;
use App\Policies\ExamSessionPolicy;
use App\Policies\ManpowerDocumentPolicy;
use App\Policies\ManpowerPolicy;
use App\Policies\PartnerPolicy;
use App\Policies\PermitApplicationPolicy;
use App\Policies\PermitIssuancePolicy;
use App\Policies\QuestionCategoryPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\SimperCategoryPolicy;
use App\Policies\SimperPolicy;
use App\Policies\UserPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider; // Import this!
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers()->symbols());

        Gate::before(fn (User $user) => $user->isDeveloper() ? true : null);
        Gate::policy(Partner::class, PartnerPolicy::class);
        Gate::policy(PermitApplication::class, PermitApplicationPolicy::class);
        Gate::policy(PermitIssuance::class, PermitIssuancePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Manpower::class, ManpowerPolicy::class);
        Gate::policy(ManpowerDocument::class, ManpowerDocumentPolicy::class);
        Gate::policy(Simper::class, SimperPolicy::class);
        Gate::policy(ExamAttempt::class, ExamAttemptPolicy::class);
        Gate::policy(ExamSession::class, ExamSessionPolicy::class);
        Gate::policy(QuestionCategory::class, QuestionCategoryPolicy::class);
        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(SimperCategory::class, SimperCategoryPolicy::class);
        Paginator::useBootstrapFive();
    }
}
