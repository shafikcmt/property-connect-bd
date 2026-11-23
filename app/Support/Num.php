<?php

declare(strict_types=1);

namespace App\Support;

/** Locale-aware digits: Bangla visitors see ০–৯, everyone else keeps 0–9. */
class Num
{
    private const BN = ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯'];

    public static function local(string $text): string
    {
        return app()->getLocale() === 'bn' ? strtr($text, self::BN) : $text;
    }

    public static function toBangla(string $text): string
    {
        return strtr($text, self::BN);
    }
}
