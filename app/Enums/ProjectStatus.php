<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectStatus: string
{
    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Ready = 'ready';
    case Completed = 'completed';

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
