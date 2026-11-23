<?php

declare(strict_types=1);

namespace App\Enums;

enum PropertyStatus: string
{
    case Ready = 'ready';
    case UnderConstruction = 'under_construction';
    case Upcoming = 'upcoming';

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
