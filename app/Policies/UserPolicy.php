<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;
use App\Support\Permissions;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permissions::USERS_MANAGE);
    }

    public function view(User $actor, User $target): bool
    {
        return $this->manages($actor, $target);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permissions::USERS_MANAGE);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->manages($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->isNot($target) && $this->manages($actor, $target);
    }

    public function restore(User $actor, User $target): bool
    {
        return $this->manages($actor, $target);
    }

    private function manages(User $actor, User $target): bool
    {
        if (! $actor->can(Permissions::USERS_MANAGE)) {
            return false;
        }

        // Only a Super Admin may touch Super Admin accounts.
        return ! $target->hasRole(RoleName::SuperAdmin->value) || $actor->hasRole(RoleName::SuperAdmin->value);
    }
}
