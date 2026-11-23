<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Project;
use App\Models\Property;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        $counts = fn ($q) => $q->visible();
        $areas = Location::active()->whereIn('type', ['city', 'area', 'sector'])->with('parent')
            ->withCount(['properties as listings_count' => $counts, 'projects as projects_count' => fn ($q) => $q->published()])->orderBy('path')->get();

        return view('locations.index', ['featured' => $areas->where('is_featured', true)->where('type.value', 'area')->sortByDesc('listings_count')->values(), 'areas' => $areas->groupBy(fn ($l) => $l->parent?->displayName() ?? __('Other'))]);
    }

    public function show(string $path): View
    {
        $location = Location::active()->where('path', $path)->with('parent.parent')->firstOrFail();
        $ids = Location::within($location)->select('id');

        $properties = Property::visible()->whereIn('location_id', $ids)->with(['type', 'location.parent', 'developer', 'coverMedia'])->withCount('gallery as images_count')->orderByDesc('published_at')->limit(9)->get();
        $projects = Project::published()->whereIn('location_id', $ids)->with(['developer.logo', 'location.parent', 'coverMedia', 'unitTypes'])->limit(6)->get();
        $children = $location->children()->active()->withCount(['properties as listings_count' => fn ($q) => $q->visible()])->orderBy('name')->get();
        $types = Property::visible()->whereIn('location_id', $ids)->join('property_types', 'property_types.id', '=', 'properties.property_type_id')
            ->selectRaw('property_types.name, property_types.slug, count(*) as total')->groupBy('property_types.name', 'property_types.slug')->orderByDesc('total')->get();

        // Thin pages stay out of the index (docs/12 §2).
        $indexable = filled($location->description) && ($properties->count() + $projects->count()) >= 1;

        return view('locations.show', compact('location', 'properties', 'projects', 'children', 'types', 'indexable'));
    }
}
