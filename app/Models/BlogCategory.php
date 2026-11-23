<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class BlogCategory extends Model
{
    use HasSlug;

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }
}
