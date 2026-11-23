<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/** Inquiries contain buyer PII: only staff with leads.manage may see or change them (assignment-scoped access arrives in Phase 8). */
class LeadPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can(Permissions::LEADS_MANAGE);
    }

    public function view(User $u, Model $m): bool
    {
        return $this->viewAny($u);
    }

    public function create(User $u): bool
    {
        return false;
    }

    public function update(User $u, Model $m): bool
    {
        return $this->viewAny($u);
    }

    public function delete(User $u, Model $m): bool
    {
        return false;
    }
}
