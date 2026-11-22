<?php

use App\Enums\RoleName;
use App\Models\User;

it('redirects guests from /admin to the admin login', function () {
    $this->get('/admin')->assertRedirect();
});

it('lets every staff role into the admin panel', function (RoleName $role) {
    $this->actingAs(userWithRole($role))->get('/admin')->assertOk();
})->with([
    RoleName::SuperAdmin, RoleName::Admin, RoleName::PropertyManager,
    RoleName::SalesManager, RoleName::SalesAgent, RoleName::ContentManager,
]);

it('denies buyers and role-less users', function () {
    $this->actingAs(userWithRole(RoleName::Buyer))->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('denies staff with unverified email or a suspended account', function () {
    $this->actingAs(userWithRole(RoleName::Admin, ['email_verified_at' => null]))->get('/admin')->assertForbidden();
    $this->actingAs(userWithRole(RoleName::Admin, ['status' => 'suspended']))->get('/admin')->assertForbidden();
});

it('forces admins to set up two-factor authentication when required', function () {
    config(['pcbd.admin_mfa_required' => true]);

    $this->actingAs(userWithRole(RoleName::Admin))->get('/admin')->assertRedirect();
    $this->actingAs(userWithRole(RoleName::ContentManager))->get('/admin')->assertOk();
});
