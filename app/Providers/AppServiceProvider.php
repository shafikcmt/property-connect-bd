<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
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

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('register', fn (Request $r) => Limit::perHour(5)->by($r->ip()));
        RateLimiter::for('password-reset', fn (Request $r) => Limit::perHour(5)->by($r->ip()));
        RateLimiter::for('verification', fn (Request $r) => Limit::perMinute(6)->by($r->user()?->id ?: $r->ip()));
    }
}
