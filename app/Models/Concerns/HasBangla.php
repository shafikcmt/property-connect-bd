<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Public text with an optional Bangla twin column (`{field}_bn`). In Bangla the twin is used when filled; otherwise
 * the English text is shown (and, for seeded reference content, looked up in lang/bn.json).
 */
trait HasBangla
{
    public function tr(string $field, bool $lookup = false): ?string
    {
        $base = $this->getAttribute($field);
        if (app()->getLocale() !== 'bn') {
            return $base;
        }

        $twin = $field.'_bn';
        if (array_key_exists($twin, $this->getAttributes()) && filled($this->getAttribute($twin))) {
            return $this->getAttribute($twin);
        }

        return $lookup && is_string($base) ? __($base) : $base;
    }
}
