<?php

declare(strict_types=1);

namespace App\Enums;

enum LocationType: string
{
    case Country = 'country';
    case Division = 'division';
    case District = 'district';
    case City = 'city';
    case Area = 'area';
    case Sector = 'sector';
    case Road = 'road';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
