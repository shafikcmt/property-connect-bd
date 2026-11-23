<?php

declare(strict_types=1);

namespace App\Enums;

enum PropertyGroup: string
{
    case Residential = 'residential';
    case Land = 'land';
    case Commercial = 'commercial';
    case Industrial = 'industrial';

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
