<?php

declare(strict_types=1);

namespace App\Policies;

use App\Support\Permissions;

/** Money: only staff who collect or manage can see it; payments are written through PaymentRecorder, never by hand. */
class ErpPaymentPolicy extends ErpPolicy
{
    protected array $read = [Permissions::ERP_COLLECT, Permissions::ERP_MANAGE];

    protected array $write = [];
}
