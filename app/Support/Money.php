<?php

declare(strict_types=1);

namespace App\Support;

/** BDT display helpers using lakh/crore (docs/08 §2). Storage is always decimal BDT. */
final class Money
{
    public static function short(float|int|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }
        $n = (float) $amount;
        if ($n >= 10_000_000) {
            return Num::local('৳ '.rtrim(rtrim(number_format($n / 10_000_000, 2), '0'), '.')).' '.__('Cr');
        }
        if ($n >= 100_000) {
            return Num::local('৳ '.rtrim(rtrim(number_format($n / 100_000, 2), '0'), '.')).' '.__('Lac');
        }

        return Num::local('৳ '.number_format($n));
    }

    public static function full(float|int|string|null $amount): string
    {
        return $amount === null ? '—' : '৳ '.number_format((float) $amount);
    }

    public static function priceLabel(?float $amount, bool $negotiable, bool $rent = false, ?string $period = null): string
    {
        if ($amount === null) {
            return $negotiable ? 'Price on request' : '—';
        }

        return self::short($amount).($rent ? ' / '.($period === 'yearly' ? 'yr' : 'mo') : '');
    }
}
