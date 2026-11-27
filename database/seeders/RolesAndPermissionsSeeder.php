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
            RoleName::Admin->value => [Permissions::USERS_MANAGE, Permissions::SETTINGS_MANAGE, Permissions::AUDIT_VIEW, ...Permissions::listingsFull(), Permissions::LEADS_MANAGE, Permissions::LEADS_ASSIGN, Permissions::CONTENT_MANAGE],
            RoleName::PropertyManager->value => Permissions::listingsFull(),
            RoleName::SalesManager->value => [Permissions::LISTINGS_VIEW, Permissions::LEADS_MANAGE, Permissions::LEADS_ASSIGN],
            RoleName::SalesAgent->value => [Permissions::LISTINGS_VIEW, Permissions::LEADS_WORK],
            RoleName::ContentManager->value => [Permissions::CONTENT_MANAGE],
            RoleName::Buyer->value => [],
            // Developer staff: ERP only, never the platform's listings, leads or settings.
            RoleName::DeveloperOwner->value => [Permissions::ERP_VIEW, Permissions::ERP_SELL, Permissions::ERP_COLLECT, Permissions::ERP_MANAGE],
            RoleName::DeveloperAccountant->value => [Permissions::ERP_VIEW, Permissions::ERP_COLLECT],
            RoleName::DeveloperSales->value => [Permissions::ERP_VIEW, Permissions::ERP_SELL],
        ];

        foreach ($matrix as $name => $permissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
