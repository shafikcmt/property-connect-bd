<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PropertyGroup;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'group', 'sort_order', 'is_active'])]
class PropertyType extends Model
{
    /** Reference-data names are translated through lang/bn.json. */
    public function displayName(): string
    {
        return __($this->name);
    }

    use HasSlug;

    protected function casts(): array
    {
        return ['group' => PropertyGroup::class, 'is_active' => 'boolean'];
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
