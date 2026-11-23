<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\PublishStatus;
use App\Models\Concerns\HasBangla;
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

#[Fillable(['developer_id', 'location_id', 'name', 'slug', 'summary', 'description', 'status', 'construction_progress', 'total_floors', 'total_units', 'handover_on', 'address', 'latitude', 'longitude', 'price_from', 'price_to', 'price_verified', 'payment_plan', 'video_url', 'available_units_verified', 'seo_title', 'seo_description',
    'name_bn', 'summary_bn', 'description_bn', 'payment_plan_bn',
    'is_share_project', 'total_shares', 'sold_shares', 'registered_shares', 'price_per_share', 'land_area_shatangsho', 'buildings_count', 'shares_verified', 'branded_landing'])]
class Project extends Model
{
    use HasBangla, HasFactory, HasMedia, HasSlug, Publishable, SoftDeletes, Verifiable;

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class, 'publish_status' => PublishStatus::class,
            'handover_on' => 'date', 'published_at' => 'datetime', 'verified_at' => 'datetime', 'featured_until' => 'datetime',
            'is_verified' => 'boolean', 'is_featured' => 'boolean', 'price_verified' => 'boolean', 'available_units_verified' => 'boolean',
            'price_from' => 'decimal:2', 'price_to' => 'decimal:2',
            'is_share_project' => 'boolean', 'shares_verified' => 'boolean', 'branded_landing' => 'boolean',
            'price_per_share' => 'decimal:2', 'land_area_shatangsho' => 'decimal:3',
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

    public function installments(): HasMany
    {
        return $this->hasMany(ProjectInstallment::class)->orderBy('sort_order')->orderBy('id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class)->orderByDesc('happened_on')->orderByDesc('id');
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

    /**
     * Public land-share figures. Counts of sold/remaining/registered shares appear only once an admin has marked
     * them verified, and the per-share price only when the project price is verified (docs/27, CLAUDE.md §3).
     *
     * @return array<string, int|float|null>|null
     */
    public function shareSummary(): ?array
    {
        if (! $this->is_share_project || ! $this->total_shares) {
            return null;
        }
        $counts = $this->shares_verified;

        return [
            'total' => $this->total_shares,
            'sold' => $counts ? $this->sold_shares : null,
            'remaining' => $counts && $this->sold_shares !== null ? $this->total_shares - $this->sold_shares : null,
            'registered' => $counts ? $this->registered_shares : null,
            'price' => $this->price_verified && $this->price_per_share ? (float) $this->price_per_share : null,
            'land' => $this->land_area_shatangsho ? (float) $this->land_area_shatangsho : null,
            'buildings' => $this->buildings_count,
        ];
    }

    /** The branded landing layout needs a published developer with a logo; otherwise the normal page is used. */
    public function isBranded(): bool
    {
        $dev = $this->developer;

        return $this->branded_landing && $dev && $dev->publish_status === PublishStatus::Published && $dev->logo !== null;
    }
}
