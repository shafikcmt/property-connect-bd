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
    case DeveloperOwner = 'developer-owner';
    case DeveloperAccountant = 'developer-accountant';
    case DeveloperSales = 'developer-sales';

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
            self::DeveloperOwner => 'Developer owner',
            self::DeveloperAccountant => 'Developer accountant',
            self::DeveloperSales => 'Developer sales',
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

    /** Roles for a developer's own staff (Developer ERP only). */
    public static function erpRoles(): array
    {
        return [self::DeveloperOwner->value, self::DeveloperAccountant->value, self::DeveloperSales->value];
    }

    /** Roles that must use two-factor authentication (staff with platform power or access to money). */
    public static function mfaRequiredRoles(): array
    {
        return [self::SuperAdmin->value, self::Admin->value, self::DeveloperOwner->value, self::DeveloperAccountant->value];
    }
}
