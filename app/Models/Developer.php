<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\Verifiable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'summary', 'about', 'website', 'email', 'phone', 'address', 'show_contact_publicly', 'established_year', 'seo_title', 'seo_description'])]
class Developer extends Model
{
    use HasFactory, HasMedia, HasSlug, Publishable, SoftDeletes, Verifiable;

    protected function casts(): array
    {
        return [
            'publish_status' => PublishStatus::class,
            'is_verified' => 'boolean', 'is_featured' => 'boolean', 'show_contact_publicly' => 'boolean',
            'verified_at' => 'datetime', 'featured_until' => 'datetime', 'published_at' => 'datetime',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function logo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where('collection', 'logo');
    }

    /** Established year is shown publicly only when verified (docs/02 FR-105). */
    public function publicEstablishedYear(): ?int
    {
        return $this->is_verified ? $this->established_year : null;
    }
}
