<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'icon', 'group', 'is_active'])]
class Amenity extends Model
{
    /** Reference-data names are translated through lang/bn.json. */
    public function displayName(): string
    {
        return __($this->name);
    }

    use HasSlug;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('group')->orderBy('name');
    }
}
