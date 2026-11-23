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

    public const LISTINGS_VIEW = 'listings.view';

    public const LISTINGS_MANAGE = 'listings.manage';

    public const LISTINGS_PUBLISH = 'listings.publish';

    public const LISTINGS_VERIFY = 'listings.verify';

    public const CATALOG_MANAGE = 'catalog.manage';

    public const LEADS_MANAGE = 'leads.manage';

    public const CONTENT_MANAGE = 'content.manage';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::USERS_MANAGE, self::ROLES_MANAGE, self::SETTINGS_MANAGE, self::AUDIT_VIEW,
            self::LISTINGS_VIEW, self::LISTINGS_MANAGE, self::LISTINGS_PUBLISH, self::LISTINGS_VERIFY, self::CATALOG_MANAGE,
            self::LEADS_MANAGE, self::CONTENT_MANAGE,
        ];
    }

    /** @return list<string> */
    public static function listingsFull(): array
    {
        return [self::LISTINGS_VIEW, self::LISTINGS_MANAGE, self::LISTINGS_PUBLISH, self::LISTINGS_VERIFY, self::CATALOG_MANAGE];
    }
}
