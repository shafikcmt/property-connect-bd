<?php

declare(strict_types=1);

namespace App\Enums;

enum ShareStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'success', self::Reserved => 'warning', self::Sold => 'gray',
        };
    }
}
