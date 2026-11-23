<?php

declare(strict_types=1);

namespace App\Enums;

enum Facing: string
{
    case North = 'north';
    case South = 'south';
    case East = 'east';
    case West = 'west';
    case NorthEast = 'north_east';
    case NorthWest = 'north_west';
    case SouthEast = 'south_east';
    case SouthWest = 'south_west';

    public function label(): string
    {
        return __(ucwords(str_replace('_', ' ', $this->value)));
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
