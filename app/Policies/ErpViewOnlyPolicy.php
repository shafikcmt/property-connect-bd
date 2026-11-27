<?php

declare(strict_types=1);

namespace App\Policies;

use App\Support\Permissions;

/** Generated records such as instalments: visible, never edited by hand. */
class ErpViewOnlyPolicy extends ErpPolicy
{
    protected array $read = [Permissions::ERP_VIEW];

    protected array $write = [];
}
