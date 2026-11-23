<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/** Developers, projects and properties share one permission set (docs/07). */
class ListingPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can(Permissions::LISTINGS_VIEW) || $u->can(Permissions::LISTINGS_MANAGE);
    }

    public function view(User $u, Model $m): bool
    {
        return $this->viewAny($u);
    }

    public function create(User $u): bool
    {
        return $u->can(Permissions::LISTINGS_MANAGE);
    }

    public function update(User $u, Model $m): bool
    {
        return $u->can(Permissions::LISTINGS_MANAGE);
    }

    public function delete(User $u, Model $m): bool
    {
        return $u->can(Permissions::LISTINGS_MANAGE);
    }

    public function restore(User $u, Model $m): bool
    {
        return $u->can(Permissions::LISTINGS_MANAGE);
    }

    public function publish(User $u, Model $m): bool
    {
        return $u->can(Permissions::LISTINGS_PUBLISH);
    }

    public function verify(User $u, Model $m): bool
    {
        return $u->can(Permissions::LISTINGS_VERIFY);
    }
}
