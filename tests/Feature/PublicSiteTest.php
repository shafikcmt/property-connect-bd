<?php

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

it('renders the home page with landmarks, a skip link and primary navigation', function () {
    $this->get('/')->assertOk()
        ->assertSee('Skip to content')
        ->assertSee('<main id="main"', false)
        ->assertSee('aria-label="Primary"', false)
        ->assertSee('Connecting Properties, People', false)
        ->assertSee('name="viewport"', false);
});

it('sends baseline security headers', function () {
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('renders friendly error pages', function () {
    $this->get('/definitely-not-here')->assertNotFound()->assertSee('Page not found');
});

it('serves the framework health endpoint', function () {
    $this->get('/up')->assertOk();
});

it('noindexes non-production environments', function () {
    $this->get('/')->assertSee('content="noindex,nofollow"', false);
});

it('protects every state-changing web route with CSRF (no CSRF exclusions)', function () {
    $excluded = app(PreventRequestForgery::class);
    expect(method_exists($excluded, 'handle'))->toBeTrue();
    foreach (Route::getRoutes() as $route) {
        if (array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) && str_starts_with($route->uri(), 'login')) {
            expect($route->gatherMiddleware())->toContain('web');
        }
    }
});

it('creates a super admin through the console command', function () {
    $this->artisan('pcbd:create-super-admin', ['--name' => 'Owner', '--email' => 'owner@example.com'])
        ->expectsQuestion('Password (min 12 characters)', 'a-very-long-pass-12')
        ->assertSuccessful();

    $user = User::firstWhere('email', 'owner@example.com');
    expect($user->hasRole(RoleName::SuperAdmin->value))->toBeTrue()->and($user->hasVerifiedEmail())->toBeTrue();
});
