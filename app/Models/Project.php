<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\PublishStatus;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\Verifiable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['developer_id', 'location_id', 'name', 'slug', 'summary', 'description', 'status', 'construction_progress', 'total_floors', 'total_units', 'handover_on', 'address', 'latitude', 'longitude', 'price_from', 'price_to', 'price_verified', 'payment_plan', 'video_url', 'available_units_verified', 'seo_title', 'seo_description'])]
class Project extends Model
{
    use HasFactory, HasMedia, HasSlug, Publishable, SoftDeletes, Verifiable;

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class, 'publish_status' => PublishStatus::class,
            'handover_on' => 'date', 'published_at' => 'datetime', 'verified_at' => 'datetime', 'featured_until' => 'datetime',
            'is_verified' => 'boolean', 'is_featured' => 'boolean', 'price_verified' => 'boolean', 'available_units_verified' => 'boolean',
            'price_from' => 'decimal:2', 'price_to' => 'decimal:2',
        ];
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'amenity_project');
    }

    public function unitTypes(): HasMany
    {
        return $this->hasMany(ProjectUnitType::class)->orderBy('sort_order');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProjectUnit::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /** Public price range only when an admin has verified it (docs/02 FR-104). */
    public function publicPriceRange(): ?array
    {
        return $this->price_verified && $this->price_from ? ['from' => (float) $this->price_from, 'to' => $this->price_to ? (float) $this->price_to : null] : null;
    }
}
