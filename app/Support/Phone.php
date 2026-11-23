<?php

declare(strict_types=1);

namespace App\Support;

/** Normalises Bangladeshi mobile numbers to E.164 (+8801XXXXXXXXX); other international numbers pass through. */
final class Phone
{
    public static function normalize(string $raw): ?string
    {
        $digits = preg_replace('/[^\d+]/', '', $raw) ?? '';
        $plain = ltrim($digits, '+');

        if (preg_match('/^(?:880)?0?(1[3-9]\d{8})$/', $plain, $m)) {
            return '+880'.$m[1];
        }
        if (str_starts_with($digits, '+') && preg_match('/^\+[1-9]\d{7,14}$/', $digits)) {
            return $digits;
        }

        return null;
    }

    public static function isValid(string $raw): bool
    {
        return self::normalize($raw) !== null;
    }

    /** Digits only, as required by wa.me links. */
    public static function whatsapp(?string $number): ?string
    {
        if (! $number) {
            return null;
        }
        $n = self::normalize($number);

        return $n ? ltrim($n, '+') : null;
    }
}
