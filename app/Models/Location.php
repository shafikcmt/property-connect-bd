<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LocationType;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['parent_id', 'type', 'name', 'name_bn', 'slug', 'latitude', 'longitude', 'description', 'is_active', 'is_featured', 'seo_title', 'seo_description'])]
class Location extends Model
{
    use HasFactory, HasSlug;

    protected function casts(): array
    {
        return ['type' => LocationType::class, 'is_active' => 'boolean', 'is_featured' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Runs after HasSlug's saving hook, so the slug is final here.
        static::saving(function (self $loc): void {
            $parentPath = $loc->parent_id ? static::query()->whereKey($loc->parent_id)->value('path') : null;
            $loc->path = trim(($parentPath ? $parentPath.'/' : '').$loc->slug, '/');
        });

        // Keep descendant paths consistent when a path changes.
        static::saved(function (self $loc): void {
            if ($loc->wasChanged('path')) {
                $loc->children()->get()->each->save();
            }
        });
    }

    protected function slugScope($query)
    {
        return $query->where('parent_id', $this->parent_id);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** This location plus every descendant (path prefix match). */
    public function scopeWithin(Builder $q, self $root): Builder
    {
        return $q->where(fn ($w) => $w->where('path', $root->path)->orWhere('path', 'like', $root->path.'/%'));
    }

    /** "Gulshan, Dhaka" — this location plus its direct parent (callers eager-load `parent`). */
    /** Bangla name when the visitor reads Bangla and one is stored. */
    public function displayName(): string
    {
        return app()->getLocale() === 'bn' && filled($this->name_bn) ? $this->name_bn : $this->name;
    }

    public function fullName(): string
    {
        return $this->parent ? $this->displayName().', '.$this->parent->displayName() : $this->displayName();
    }
}
