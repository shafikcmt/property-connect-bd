<?php

declare(strict_types=1);

namespace App\Support;

/** Small helpers so a developer-supplied brand colour can never break text contrast (WCAG AA). */
class Color
{
    public static function isHex(?string $hex): bool
    {
        return is_string($hex) && preg_match('/^#[0-9a-f]{6}$/i', $hex) === 1;
    }

    /** WCAG contrast ratio of white text on the given background colour. */
    public static function contrastWithWhite(string $hex): float
    {
        return 1.05 / (self::luminance($hex) + 0.05);
    }

    /** Darkens by a 0–1 factor (used for hover states). */
    public static function darken(string $hex, float $factor): string
    {
        [$r, $g, $b] = self::rgb($hex);

        return sprintf('#%02x%02x%02x', (int) round($r * (1 - $factor)), (int) round($g * (1 - $factor)), (int) round($b * (1 - $factor)));
    }

    private static function luminance(string $hex): float
    {
        return collect(self::rgb($hex))->map(function (int $c): float {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        })->pipe(fn ($v) => 0.2126 * $v[0] + 0.7152 * $v[1] + 0.0722 * $v[2]);
    }

    /** @return array{0:int,1:int,2:int} */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
