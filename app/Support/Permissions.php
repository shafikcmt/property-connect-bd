<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Permission names ({resource}.{action}). Only permissions needed by shipped
 * phases are defined; later phases append here and in the seeder.
 */
final class Permissions
{
    public const USERS_MANAGE = 'users.manage';

    public const ROLES_MANAGE = 'roles.manage';

    public const SETTINGS_MANAGE = 'settings.manage';

    public const AUDIT_VIEW = 'audit.view';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::USERS_MANAGE, self::ROLES_MANAGE, self::SETTINGS_MANAGE, self::AUDIT_VIEW];
    }
}
