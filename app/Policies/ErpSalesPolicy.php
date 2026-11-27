<?php

declare(strict_types=1);

namespace App\Policies;

use App\Support\Permissions;

/** Shareholders and sales: everyone sees them; sales staff and the owner create and edit. */
class ErpSalesPolicy extends ErpPolicy
{
    protected array $read = [Permissions::ERP_VIEW];

    protected array $write = [Permissions::ERP_SELL, Permissions::ERP_MANAGE];
}
