<?php

declare(strict_types=1);

namespace App\Enums;

enum PublishStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
    case Sold = 'sold';
    case Rented = 'rented';

    public function label(): string
    {
        return __(ucwords(str_replace('_', ' ', $this->value)));
    }

    public function color(): string
    {
        return match ($this) {
            self::Published => 'success',
            self::PendingReview => 'warning',
            self::Draft, self::Archived => 'gray',
            self::Rejected => 'danger',
            self::Sold, self::Rented => 'info',
        };
    }

    /** Statuses a project/developer may use. */
    public static function simple(): array
    {
        return [self::Draft, self::Published, self::Archived];
    }
}
