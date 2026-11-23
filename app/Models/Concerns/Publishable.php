<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Builder;

trait Publishable
{
    public function scopePublished(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('publish_status'), PublishStatus::Published->value);
    }

    /** Featured only while the flag is set and any expiry date has not passed. */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_featured'), true)
            ->where(fn (Builder $q) => $q->whereNull($query->qualifyColumn('featured_until'))->orWhere($query->qualifyColumn('featured_until'), '>', now()));
    }

    public function isFeaturedNow(): bool
    {
        return $this->is_featured && ($this->featured_until === null || $this->featured_until->isFuture());
    }

    public function isPublished(): bool
    {
        return $this->publish_status === PublishStatus::Published;
    }
}
