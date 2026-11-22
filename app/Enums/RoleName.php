<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case PropertyManager = 'property-manager';
    case SalesManager = 'sales-manager';
    case SalesAgent = 'sales-agent';
    case ContentManager = 'content-manager';
    case Buyer = 'buyer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::PropertyManager => 'Property Manager',
            self::SalesManager => 'Sales Manager',
            self::SalesAgent => 'Sales Agent',
            self::ContentManager => 'Content Manager',
            self::Buyer => 'Buyer',
        };
    }

    /** Roles that may sign in to the admin panel. */
    public static function adminPanelRoles(): array
    {
        return [
            self::SuperAdmin->value,
            self::Admin->value,
            self::PropertyManager->value,
            self::SalesManager->value,
            self::SalesAgent->value,
            self::ContentManager->value,
        ];
    }

    /** Roles that must use two-factor authentication. */
    public static function mfaRequiredRoles(): array
    {
        return [self::SuperAdmin->value, self::Admin->value];
    }
}
