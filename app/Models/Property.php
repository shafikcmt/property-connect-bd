<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Facing;
use App\Enums\Furnishing;
use App\Enums\PropertyStatus;
use App\Enums\PublishStatus;
use App\Enums\Purpose;
use App\Models\Concerns\HasBangla;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\Verifiable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title', 'slug', 'description', 'purpose', 'property_type_id', 'location_id', 'project_id', 'developer_id', 'project_unit_id',
    'address_line', 'latitude', 'longitude', 'price', 'price_negotiable', 'rent_period', 'size_sqft', 'bedrooms', 'bathrooms',
    'balconies', 'floor_no', 'total_floors', 'facing', 'parking_spaces', 'furnishing', 'property_status', 'year_built',
    'handover_on', 'payment_plan', 'nearby', 'video_url', 'expires_at', 'show_owner_contact', 'owner_name', 'owner_phone',
    'owner_email', 'seo_title', 'seo_description', 'title_bn', 'description_bn',
])]
class Property extends Model
{
    use HasBangla, HasFactory, HasMedia, HasSlug, Publishable, SoftDeletes, Verifiable;

    /** Never serialise private owner contact data. */
    protected $hidden = ['owner_name', 'owner_phone', 'owner_email'];

    protected function casts(): array
    {
        return [
            'purpose' => Purpose::class, 'publish_status' => PublishStatus::class, 'property_status' => PropertyStatus::class,
            'furnishing' => Furnishing::class, 'facing' => Facing::class,
            'price' => 'decimal:2', 'size_sqft' => 'decimal:2', 'nearby' => 'array', 'handover_on' => 'date',
            'published_at' => 'datetime', 'price_updated_at' => 'datetime', 'expires_at' => 'datetime',
            'featured_until' => 'datetime', 'verified_at' => 'datetime',
            'is_featured' => 'boolean', 'is_verified' => 'boolean', 'price_negotiable' => 'boolean', 'show_owner_contact' => 'boolean',
        ];
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    protected static function booted(): void
    {
        static::created(function (self $p): void {
            $p->forceFill(['public_code' => 'PC-'.(1000 + $p->id)])->saveQuietly();
        });

        static::creating(function (self $p): void {
            $p->created_by ??= auth()->id();
        });

        static::updating(function (self $p): void {
            if ($p->isDirty('price')) {
                $p->price_updated_at = now();
            }
        });
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProjectUnit::class, 'project_unit_id');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'amenity_property');
    }

    /** Publicly visible: published, not expired. */
    public function scopeVisible(Builder $q): Builder
    {
        return $q->published()->where(fn ($w) => $w->whereNull('properties.expires_at')->orWhere('properties.expires_at', '>', now()));
    }

    public function pricePerSqft(): ?float
    {
        return $this->purpose === Purpose::Sale && $this->price && $this->size_sqft ? round((float) $this->price / (float) $this->size_sqft, 2) : null;
    }
}
