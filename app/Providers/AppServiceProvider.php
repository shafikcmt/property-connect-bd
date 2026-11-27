<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\Amenity;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\DeedBatch;
use App\Models\DeedEntry;
use App\Models\Developer;
use App\Models\Faq;
use App\Models\InstalmentPlanItem;
use App\Models\Lead;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\Property;
use App\Models\PropertyReport;
use App\Models\PropertyType;
use App\Models\Shareholder;
use App\Models\ShareHolding;
use App\Models\ShareInstalment;
use App\Models\SiteVisit;
use App\Models\User;
use App\Policies\CatalogPolicy;
use App\Policies\ContentPolicy;
use App\Policies\ErpManagePolicy;
use App\Policies\ErpPaymentPolicy;
use App\Policies\ErpPlanItemPolicy;
use App\Policies\ErpSalesPolicy;
use App\Policies\ErpViewOnlyPolicy;
use App\Policies\LeadPolicy;
use App\Policies\ListingPolicy;
use App\Policies\SiteVisitPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Surface N+1 queries during development and tests (docs/09 §16).
        Model::preventLazyLoading(! $this->app->isProduction());

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->numbers()->uncompromised()
            : Password::min(10)->letters()->numbers());

        // Super Admin implicitly holds every permission (docs/07).
        Gate::before(fn (?User $user) => $user?->hasRole(RoleName::SuperAdmin->value) ? true : null);
        Gate::policy(User::class, UserPolicy::class);
        foreach ([Developer::class, Project::class, Property::class] as $model) {
            Gate::policy($model, ListingPolicy::class);
        }
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(SiteVisit::class, SiteVisitPolicy::class);
        // Developer ERP (ADR-0019): who may see and who may change each kind of record.
        Gate::policy(ProjectShare::class, ErpManagePolicy::class);
        Gate::policy(DeedBatch::class, ErpManagePolicy::class);
        Gate::policy(DeedEntry::class, ErpManagePolicy::class);
        Gate::policy(InstalmentPlanItem::class, ErpPlanItemPolicy::class);
        Gate::policy(Shareholder::class, ErpSalesPolicy::class);
        Gate::policy(ShareHolding::class, ErpSalesPolicy::class);
        Gate::policy(ShareInstalment::class, ErpViewOnlyPolicy::class);
        Gate::policy(Payment::class, ErpPaymentPolicy::class);
        foreach ([Faq::class, BlogPost::class, BlogCategory::class] as $model) {
            Gate::policy($model, ContentPolicy::class);
        }
        Gate::policy(PropertyReport::class, ListingPolicy::class);
        foreach ([Location::class, PropertyType::class, Amenity::class] as $model) {
            Gate::policy($model, CatalogPolicy::class);
        }

        // Error pages render before the session middleware runs, so $errors may be absent.
        View::composer('partials.footer', function ($view) {
            if (! array_key_exists('errors', $view->getData())) {
                $view->with('errors', new ViewErrorBag);
            }
        });

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('inquiry', fn (Request $r) => [
            Limit::perHour(10)->by('ip:'.$r->ip()),
            Limit::perHour(4)->by('phone:'.preg_replace('/\D/', '', (string) $r->input('phone', $r->ip()))),
        ]);
        RateLimiter::for('register', fn (Request $r) => Limit::perHour(5)->by($r->ip()));
        RateLimiter::for('password-reset', fn (Request $r) => Limit::perHour(5)->by($r->ip()));
        RateLimiter::for('verification', fn (Request $r) => Limit::perMinute(6)->by($r->user()?->id ?: $r->ip()));
    }
}
