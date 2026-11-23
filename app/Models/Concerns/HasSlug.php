<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\SlugHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * Unique, stable slugs. When a slug changes the old one is recorded so the public
 * site can 301 old URLs (docs/12). Models define slugSource() (the attribute name).
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::saving(function (self $model): void {
            $source = $model->slugSource();
            if ($model->slug === null || ($model->isDirty($source) && ! $model->isDirty('slug') && $model->slugFollowsSource())) {
                $old = $model->getOriginal('slug');
                $model->slug = $model->uniqueSlug((string) $model->{$source});
                $model->slugChangedFrom = $old;
            }
        });

        static::saved(function (self $model): void {
            if ($model->slugChangedFrom) {
                $model->slugHistories()->create(['slug' => $model->slugChangedFrom]);
                $model->slugChangedFrom = null;
            }
        });
    }

    public ?string $slugChangedFrom = null;

    protected function slugSource(): string
    {
        return 'name';
    }

    protected function slugFollowsSource(): bool
    {
        return true;
    }

    public function slugHistories(): MorphMany
    {
        return $this->morphMany(SlugHistory::class, 'sluggable');
    }

    /** Override to make slugs unique within a scope (e.g. sibling locations). */
    protected function slugScope($query)
    {
        return $query;
    }

    protected function uniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $i = 2;
        while ($this->slugScope(static::withoutGlobalScopes()->where('slug', $slug))->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
