<?php

declare(strict_types=1);

namespace App\Services\Erp;

use DomainException;

/** A Developer ERP action that breaks a business rule. The message is safe to show to the developer's staff. */
class ErpRuleViolation extends DomainException {}
