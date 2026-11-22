<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Idempotent: safe to run on every deploy. See docs/07. */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $matrix = [
            // Super Admin receives everything via Gate::before in AppServiceProvider.
            RoleName::SuperAdmin->value => Permissions::all(),
            RoleName::Admin->value => [Permissions::USERS_MANAGE, Permissions::SETTINGS_MANAGE, Permissions::AUDIT_VIEW],
            RoleName::PropertyManager->value => [],
            RoleName::SalesManager->value => [],
            RoleName::SalesAgent->value => [],
            RoleName::ContentManager->value => [],
            RoleName::Buyer->value => [],
        ];

        foreach ($matrix as $name => $permissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
