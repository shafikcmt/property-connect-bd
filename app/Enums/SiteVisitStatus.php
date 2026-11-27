<?php

declare(strict_types=1);

namespace App\Enums;

enum SiteVisitStatus: string
{
    case Requested = 'requested';
    case Confirmed = 'confirmed';
    case Rescheduled = 'rescheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return __(ucwords(str_replace('_', ' ', $this->value)));
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested => 'warning', self::Confirmed, self::Rescheduled => 'info', self::Completed => 'success', self::Cancelled => 'gray', self::NoShow => 'danger',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::NoShow], true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
