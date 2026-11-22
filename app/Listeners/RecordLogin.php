<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\RoleName;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;

class RecordLogin
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        // Staff sign-ins are always audited; buyer sign-ins are not (data minimisation).
        if ($user->hasAnyRole(RoleName::adminPanelRoles())) {
            $this->audit->log('auth.login', $user, userId: $user->id);
        }
    }
}
