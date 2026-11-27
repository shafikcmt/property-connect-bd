<?php

declare(strict_types=1);

namespace App\Policies;

use App\Support\Permissions;

/** Shares and the instalment plan: everyone sees them, only the owner changes them. */
class ErpManagePolicy extends ErpPolicy
{
    protected array $read = [Permissions::ERP_VIEW];

    protected array $write = [Permissions::ERP_MANAGE];
}
