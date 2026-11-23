<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Amenity;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Builder;

/**
 * Turns validated filter input into a Property query. Only publicly visible
 * properties are ever returned; callers cannot widen that.
 */
class PropertySearch
{
    public const SORTS = [
        'featured' => 'Featured first',
        'newest' => 'Newest',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'size_desc' => 'Size: largest',
    ];

    /** @param array<string, mixed> $f */
    public function query(array $f): Builder
    {
        $q = Property::query()->visible()
            ->with(['type', 'location.parent', 'developer', 'coverMedia'])
            ->withCount('gallery as images_count');

        $q->when($f['purpose'] ?? null, fn ($q, $v) => $q->where('purpose', $v));

        if ($types = array_filter((array) ($f['type'] ?? []))) {
            $q->whereHas('type', fn ($t) => $t->whereIn('slug', $types));
        }
        if (! empty($f['location']) && ($loc = Location::where('path', $f['location'])->first())) {
            $q->whereIn('location_id', Location::within($loc)->select('id'));
        }
        $q->when($f['price_min'] ?? null, fn ($q, $v) => $q->where('price', '>=', $v));
        $q->when($f['price_max'] ?? null, fn ($q, $v) => $q->where('price', '<=', $v));
        $q->when($f['size_min'] ?? null, fn ($q, $v) => $q->where('size_sqft', '>=', $v));
        $q->when($f['size_max'] ?? null, fn ($q, $v) => $q->where('size_sqft', '<=', $v));
        $q->when($f['beds'] ?? null, fn ($q, $v) => $q->where('bedrooms', '>=', $v));
        $q->when($f['baths'] ?? null, fn ($q, $v) => $q->where('bathrooms', '>=', $v));
        $q->when($f['status'] ?? null, fn ($q, $v) => $q->where('property_status', $v));
        $q->when($f['furnishing'] ?? null, fn ($q, $v) => $q->where('furnishing', $v));
        $q->when($f['facing'] ?? null, fn ($q, $v) => $q->where('facing', $v));
        $q->when($f['parking'] ?? null, fn ($q, $v) => $q->where('parking_spaces', '>=', $v));
        $q->when($f['floor_min'] ?? null, fn ($q, $v) => $q->where('floor_no', '>=', $v));
        $q->when($f['floor_max'] ?? null, fn ($q, $v) => $q->where('floor_no', '<=', $v));
        $q->when(! empty($f['verified']), fn ($q) => $q->where('is_verified', true));

        if (! empty($f['developer'])) {
            $q->whereIn('developer_id', Developer::where('slug', $f['developer'])->select('id'));
        }
        if (! empty($f['project'])) {
            $q->whereIn('project_id', Project::where('slug', $f['project'])->select('id'));
        }
        foreach (array_filter((array) ($f['amenities'] ?? [])) as $slug) { // all-of
            $q->whereHas('amenities', fn ($a) => $a->where('slug', $slug));
        }
        if (! empty($f['q'])) {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $f['q'])).'%';
            $q->where(fn ($w) => $w->where('properties.title', 'ilike', $term)
                ->orWhere('properties.public_code', 'ilike', $term)
                ->orWhere('properties.address_line', 'ilike', $term)
                ->orWhere('properties.description', 'ilike', $term)
                ->orWhereHas('location', fn ($l) => $l->where('name', 'ilike', $term)));
        }

        return $this->sort($q, (string) ($f['sort'] ?? 'featured'));
    }

    private function sort(Builder $q, string $sort): Builder
    {
        return match ($sort) {
            'newest' => $q->orderByDesc('published_at'),
            'price_asc' => $q->orderByRaw('price asc nulls last'),
            'price_desc' => $q->orderByRaw('price desc nulls last'),
            'size_desc' => $q->orderByRaw('size_sqft desc nulls last'),
            default => $q->orderByRaw('(is_featured and (featured_until is null or featured_until > now())) desc')->orderByDesc('published_at'),
        };
    }

    /** Filter dropdown data. (Not cached: the cache store cannot unserialize Eloquent models.) */
    public function options(): array
    {
        return [
            'types' => PropertyType::active()->get(['id', 'name', 'slug']),
            'locations' => Location::active()->whereIn('type', ['city', 'area', 'sector', 'district'])->with('parent')->orderBy('path')->get(),
            'developers' => Developer::published()->orderBy('name')->get(['id', 'name', 'slug']),
            'amenities' => Amenity::active()->get(['id', 'name', 'slug']),
        ];
    }
}
