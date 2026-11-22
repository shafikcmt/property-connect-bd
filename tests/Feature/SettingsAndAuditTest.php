<?php

use App\Enums\RoleName;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('returns defaults, stores and caches settings', function () {
    $s = app(SettingsService::class);

    expect($s->get('general.site_name'))->toBe('Property Connect BD')
        ->and($s->get('contact.whatsapp'))->toBeNull();

    $s->set('contact.whatsapp', '+8801700000000');
    expect($s->get('contact.whatsapp'))->toBe('+8801700000000');

    $s->set('contact.whatsapp', null);
    expect($s->get('contact.whatsapp'))->toBeNull();
});

it('lets Admin save settings and audits changed keys without values', function () {
    $this->actingAs(userWithRole(RoleName::Admin));

    Livewire::test(ManageSettings::class)
        ->fillForm(['contact__phone' => '+8801711111111', 'general__site_name' => 'Property Connect BD', 'general__tagline' => 'x', 'leads__sla_minutes' => 30])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(SettingsService::class)->get('contact.phone'))->toBe('+8801711111111');

    $log = AuditLog::where('event', 'settings.updated')->latest('id')->first();
    expect($log->new_values['keys'])->toContain('contact.phone')
        ->and(json_encode($log->new_values))->not->toContain('+8801711111111');
});

it('denies the settings page to roles without settings.manage', function (RoleName $role) {
    $this->actingAs(userWithRole($role))->get('/admin/settings')->assertForbidden();
})->with([RoleName::ContentManager, RoleName::SalesAgent, RoleName::PropertyManager]);

it('audits staff logins but not buyer logins, and never stores raw IPs', function () {
    $staff = userWithRole(RoleName::SalesAgent, ['email' => 's@example.com']);
    $buyer = userWithRole(RoleName::Buyer, ['email' => 'b@example.com']);

    $this->post('/login', ['email' => 's@example.com', 'password' => 'password-1234']);
    $this->post('/logout');
    $this->post('/login', ['email' => 'b@example.com', 'password' => 'password-1234']);

    expect(AuditLog::where('event', 'auth.login')->where('user_id', $staff->id)->count())->toBe(1)
        ->and(AuditLog::where('event', 'auth.login')->where('user_id', $buyer->id)->count())->toBe(0);

    $entry = app(AuditLogger::class)->log('test.event', $staff);
    expect($entry->ip_hash)->not->toContain('127.0.0.1');
});

it('shows the audit viewer to Admin only', function () {
    $this->actingAs(userWithRole(RoleName::SalesManager))->get('/admin/audit-logs')->assertForbidden();
    $this->actingAs(userWithRole(RoleName::Admin))->get('/admin/audit-logs')->assertOk();
});

it('lets Admin list users but not hand out the Super Admin role', function () {
    $admin = userWithRole(RoleName::Admin);
    $superRole = Role::findByName(RoleName::SuperAdmin->value);
    $agentRole = Role::findByName(RoleName::SalesAgent->value);
    $this->actingAs($admin);

    Livewire::test(ListUsers::class)->assertOk();

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Evil', 'email' => 'evil@example.com', 'password' => 'long-enough-pass-1', 'status' => 'active', 'roles' => [$superRole->id]])
        ->call('create')
        ->assertHasFormErrors(['roles']);
    expect(User::where('email', 'evil@example.com')->exists())->toBeFalse();

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Agent', 'email' => 'agent@example.com', 'password' => 'long-enough-pass-1', 'status' => 'active', 'roles' => [$agentRole->id]])
        ->call('create')
        ->assertHasNoFormErrors();
    expect(User::firstWhere('email', 'agent@example.com')->hasRole('sales-agent'))->toBeTrue()
        ->and(User::firstWhere('email', 'agent@example.com')->isActive())->toBeTrue()
        ->and(AuditLog::where('event', 'user.created')->exists())->toBeTrue();
});

it('denies the user list to non-managers', function () {
    $this->actingAs(userWithRole(RoleName::ContentManager))->get('/admin/users')->assertForbidden();
});
