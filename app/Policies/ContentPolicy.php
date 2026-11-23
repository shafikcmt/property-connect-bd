<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

class ContentPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can(Permissions::CONTENT_MANAGE);
    }

    public function view(User $u, Model $m): bool
    {
        return $this->viewAny($u);
    }

    public function create(User $u): bool
    {
        return $this->viewAny($u);
    }

    public function update(User $u, Model $m): bool
    {
        return $this->viewAny($u);
    }

    public function delete(User $u, Model $m): bool
    {
        return $this->viewAny($u);
    }

    public function restore(User $u, Model $m): bool
    {
        return $this->viewAny($u);
    }
}
