<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SiteVisit;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/** Managers handle every visit; agents the visits assigned to them or attached to their own leads. */
class SiteVisitPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can(Permissions::LEADS_MANAGE) || $u->can(Permissions::LEADS_WORK);
    }

    public function view(User $u, Model $m): bool
    {
        return $u->can(Permissions::LEADS_MANAGE) || ($u->can(Permissions::LEADS_WORK) && $m instanceof SiteVisit && ($m->assigned_to === $u->id || $m->lead?->assigned_to === $u->id));
    }

    public function create(User $u): bool
    {
        return false;
    }

    public function update(User $u, Model $m): bool
    {
        return $this->view($u, $m);
    }

    public function delete(User $u, Model $m): bool
    {
        return false;
    }
}
