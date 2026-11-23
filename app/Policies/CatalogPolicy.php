<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/** Locations, property types and amenities: viewable by listing staff, editable with catalog.manage. */
class CatalogPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can(Permissions::LISTINGS_VIEW) || $u->can(Permissions::CATALOG_MANAGE);
    }

    public function view(User $u, Model $m): bool
    {
        return $this->viewAny($u);
    }

    public function create(User $u): bool
    {
        return $u->can(Permissions::CATALOG_MANAGE);
    }

    public function update(User $u, Model $m): bool
    {
        return $u->can(Permissions::CATALOG_MANAGE);
    }

    public function delete(User $u, Model $m): bool
    {
        return $u->can(Permissions::CATALOG_MANAGE);
    }
}
