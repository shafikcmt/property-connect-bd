<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * Inquiries contain buyer PII. Managers (leads.manage) see and change every lead; agents (leads.work) only the
 * leads assigned to them; assigning needs leads.assign (docs/13 §8).
 */
class LeadPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can(Permissions::LEADS_MANAGE) || $u->can(Permissions::LEADS_WORK);
    }

    public function view(User $u, Model $m): bool
    {
        return $u->can(Permissions::LEADS_MANAGE) || ($u->can(Permissions::LEADS_WORK) && $m instanceof Lead && $m->assigned_to === $u->id);
    }

    public function create(User $u): bool
    {
        return false;
    }

    public function update(User $u, Model $m): bool
    {
        return $this->view($u, $m);
    }

    public function assign(User $u, Model $m): bool
    {
        return $u->can(Permissions::LEADS_ASSIGN);
    }

    public function delete(User $u, Model $m): bool
    {
        return false;
    }
}
