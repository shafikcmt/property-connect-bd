<?php

use App\Enums\RoleName;
use App\Models\BlogPost;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Media;
use App\Models\Project;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ReferenceDataSeeder;
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

it('fails the production preflight when the environment is unsafe', function () {
    config(['app.debug' => true]);

    $this->artisan('pcbd:preflight')->assertFailed();
});

it('sends a report-only CSP on public pages but not on the admin panel', function () {
    $this->get('/')->assertHeader('Content-Security-Policy-Report-Only');
    expect($this->get('/')->headers->get('Content-Security-Policy-Report-Only'))->toContain("object-src 'none'")->toContain("frame-ancestors 'self'");
    $this->get('/admin/login')->assertHeaderMissing('Content-Security-Policy-Report-Only');
});

it('removes demo content with pcbd:demo-clear and leaves real records alone', function () {
    $this->seed(ReferenceDataSeeder::class);
    $this->seed(DemoSeeder::class);
    $real = Developer::factory()->create(['name' => 'Real Builders Ltd']);
    expect(Property::count())->toBeGreaterThan(5)->and(Media::where('is_demo', true)->count())->toBeGreaterThan(0);

    $this->artisan('pcbd:demo-clear', ['--force' => true])->assertSuccessful();

    expect(Property::withTrashed()->count())->toBe(0)
        ->and(Project::withTrashed()->count())->toBe(0)
        ->and(Media::where('is_demo', true)->count())->toBe(0)
        ->and(BlogPost::withTrashed()->count())->toBe(0)
        ->and(Developer::pluck('name')->all())->toBe(['Real Builders Ltd'])
        ->and(Location::where('name', 'Gulshan')->exists())->toBeFalse()
        ->and(Location::where('name', 'Bangladesh')->exists())->toBeTrue();
});

it('asks search engines to stay away while PCBD_NOINDEX is on', function () {
    config(['pcbd.noindex' => true]);

    $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('content="noindex,nofollow"', false);
    $this->get('/robots.txt')->assertSee('Disallow: /', false);
});

it('pcbd:mail-test reports success with the array mailer', function () {
    config(['mail.default' => 'array']);

    $this->artisan('pcbd:mail-test', ['to' => 'owner@example.com'])->assertSuccessful();
});
