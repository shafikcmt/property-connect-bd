<?php

declare(strict_types=1);

namespace App\Services\Erp;

use App\Enums\RoleName;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Keeps accounts consistent (ADR-0019): a developer's staff member must be linked to a developer and hold only
 * developer roles; platform staff and buyers must not be linked to one.
 */
class UserDeveloperLink
{
    /** @param array<int, int|string> $roleIds */
    public static function validate(array $roleIds, ?int $developerId): void
    {
        $names = Role::query()->whereIn('id', $roleIds)->pluck('name')->all();
        $erp = array_values(array_intersect($names, RoleName::erpRoles()));
        $other = array_values(array_diff($names, RoleName::erpRoles()));

        if ($erp !== [] && $developerId === null) {
            throw ValidationException::withMessages(['data.developer_id' => 'Choose the developer company for a developer role.']);
        }
        if ($erp !== [] && array_diff($other, [RoleName::Buyer->value]) !== []) {
            throw ValidationException::withMessages(['data.roles' => 'Developer roles cannot be mixed with platform staff roles.']);
        }
        if ($erp === [] && $developerId !== null) {
            throw ValidationException::withMessages(['data.developer_id' => 'Only developer roles can be linked to a developer company.']);
        }
    }
}
