<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/** A lead action that breaks a workflow rule (docs/13). The message is safe to show to staff. */
class LeadRuleViolation extends DomainException {}
