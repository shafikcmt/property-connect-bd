<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** The instalment plan is only a template copied onto each sale, so removing a line never changes a sale already made. */
class ErpPlanItemPolicy extends ErpManagePolicy
{
    public function delete(User $u, Model $m): bool
    {
        return $this->update($u, $m);
    }
}
