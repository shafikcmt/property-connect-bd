<?php

declare(strict_types=1);

namespace App\Enums;

enum BuyTimeline: string
{
    case Immediately = 'immediately';
    case Within3Months = 'within_3_months';
    case Within6Months = 'within_6_months';
    case Later = 'later';
    case JustExploring = 'just_exploring';

    public function label(): string
    {
        return match ($this) {
            self::Immediately => 'Immediately', self::Within3Months => 'Within 3 months', self::Within6Months => 'Within 6 months',
            self::Later => 'Later than 6 months', self::JustExploring => 'Just exploring',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
