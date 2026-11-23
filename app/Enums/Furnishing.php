<?php

declare(strict_types=1);

namespace App\Enums;

enum Furnishing: string
{
    case Unfurnished = 'unfurnished';
    case Semi = 'semi';
    case Furnished = 'furnished';

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
