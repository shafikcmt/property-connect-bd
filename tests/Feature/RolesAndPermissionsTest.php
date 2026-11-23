<?php

use App\Enums\RoleName;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

it('seeds every role idempotently', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::count())->toBe(count(RoleName::cases()));
    foreach (RoleName::cases() as $role) {
        expect(Role::where('name', $role->value)->exists())->toBeTrue();
    }
});

it('gives Super Admin everything and Admin no role management', function () {
    $super = userWithRole(RoleName::SuperAdmin);
    $admin = userWithRole(RoleName::Admin);

    expect($super->can(Permissions::ROLES_MANAGE))->toBeTrue()
        ->and($admin->can(Permissions::USERS_MANAGE))->toBeTrue()
        ->and($admin->can(Permissions::ROLES_MANAGE))->toBeFalse();
});

it('gives non-admin roles no system-administration permissions', function (RoleName $role) {
    $user = userWithRole($role);
    foreach ([Permissions::USERS_MANAGE, Permissions::ROLES_MANAGE, Permissions::SETTINGS_MANAGE, Permissions::AUDIT_VIEW] as $permission) {
        expect($user->can($permission))->toBeFalse();
    }
})->with([RoleName::PropertyManager, RoleName::SalesManager, RoleName::SalesAgent, RoleName::ContentManager, RoleName::Buyer]);

it('scopes listing permissions by role', function () {
    expect(userWithRole(RoleName::PropertyManager)->can(Permissions::LISTINGS_PUBLISH))->toBeTrue()
        ->and(userWithRole(RoleName::SalesAgent)->can(Permissions::LISTINGS_VIEW))->toBeTrue()
        ->and(userWithRole(RoleName::SalesAgent)->can(Permissions::LISTINGS_MANAGE))->toBeFalse()
        ->and(userWithRole(RoleName::ContentManager)->can(Permissions::LISTINGS_VIEW))->toBeFalse()
        ->and(userWithRole(RoleName::Buyer)->can(Permissions::LISTINGS_VIEW))->toBeFalse();
});

it('only lets Super Admin manage Super Admin accounts', function () {
    $target = userWithRole(RoleName::SuperAdmin);

    expect(userWithRole(RoleName::Admin)->can('update', $target))->toBeFalse()
        ->and(userWithRole(RoleName::SuperAdmin)->can('update', $target))->toBeTrue()
        ->and(userWithRole(RoleName::Admin)->can('update', userWithRole(RoleName::SalesAgent)))->toBeTrue();
});

it('forbids deleting oneself', function () {
    $admin = userWithRole(RoleName::Admin);
    expect($admin->can('delete', $admin))->toBeFalse();
});
