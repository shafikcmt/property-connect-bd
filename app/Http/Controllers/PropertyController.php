<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesSlugs;
use App\Http\Requests\PropertyFilterRequest;
use App\Models\Property;
use App\Services\PropertySearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    use ResolvesSlugs;

    public function index(PropertyFilterRequest $request, PropertySearch $search, ?string $purpose = null): View
    {
        $filters = $request->filters();
        if ($purpose) {
            $filters['purpose'] = $purpose === 'for-rent' ? 'rent' : 'sale';
        }

        $properties = $search->query($filters)->paginate(12)->withQueryString();

        return view('properties.index', [
            'properties' => $properties, 'filters' => $filters, 'options' => $search->options(),
            'view' => $request->input('view') === 'list' ? 'list' : 'grid',
            'purposePage' => $purpose,
            // Only the clean, unfiltered listing pages are indexable (docs/12 crawl control).
            'indexable' => count(array_diff_key($filters, ['purpose' => 1])) === 0 && ! $request->filled('page'),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $found = $this->resolveSlug(Property::query()->visible(), $slug, 'properties.show');
        if (! $found instanceof Property) {
            return $found;
        }

        $property = $found->load(['type', 'location.parent.parent', 'developer.logo', 'project', 'amenities', 'media']);

        // Recently viewed (session ring buffer, newest first, max 20).
        $recent = collect($request->session()->get('recent', []))->reject(fn ($id) => $id === $property->id)->prepend($property->id)->take(20)->all();
        $request->session()->put('recent', $recent);

        $similar = Property::visible()->where('id', '!=', $property->id)
            ->where('purpose', $property->purpose)
            ->where(fn ($q) => $q->where('location_id', $property->location_id)->orWhere('property_type_id', $property->property_type_id))
            ->when($property->price, fn ($q) => $q->whereBetween('price', [$property->price * 0.6, $property->price * 1.6]))
            ->with(['type', 'location.parent', 'developer', 'coverMedia'])->withCount('gallery as images_count')
            ->orderByRaw('(location_id = ?) desc', [$property->location_id])->limit(3)->get();

        return view('properties.show', ['property' => $property, 'similar' => $similar]);
    }
}
