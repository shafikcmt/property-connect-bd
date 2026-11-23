<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Developer;
use App\Models\Location;
use App\Models\Project;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    private function like(string $q): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($q)).'%';
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '');
        $like = $this->like($q);
        $has = $q !== '';

        return view('search', [
            'q' => $q,
            'properties' => $has ? Property::visible()->where(fn ($w) => $w->where('title', 'ilike', $like)->orWhere('public_code', 'ilike', $like)->orWhereHas('location', fn ($l) => $l->where('name', 'ilike', $like)))
                ->with(['type', 'location.parent', 'developer', 'coverMedia'])->withCount('gallery as images_count')->limit(12)->get() : collect(),
            'projects' => $has ? Project::published()->where('name', 'ilike', $like)->with(['developer.logo', 'location.parent', 'coverMedia', 'unitTypes'])->limit(6)->get() : collect(),
            'developers' => $has ? Developer::published()->where('name', 'ilike', $like)->with('logo')->limit(6)->get() : collect(),
            'locations' => $has ? Location::active()->where('name', 'ilike', $like)->with('parent')->limit(8)->get() : collect(),
        ]);
    }

    /** Lightweight autosuggest for the header/hero search (rate limited). */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2 || mb_strlen($q) > 60) {
            return response()->json([]);
        }
        $like = $this->like($q);

        return response()->json(collect()
            ->merge(Location::active()->where('name', 'ilike', $like)->with('parent')->limit(4)->get()->map(fn ($l) => ['type' => 'Location', 'label' => $l->fullName(), 'url' => route('locations.show', $l->path)]))
            ->merge(Project::published()->where('name', 'ilike', $like)->limit(3)->get()->map(fn ($p) => ['type' => 'Project', 'label' => $p->name, 'url' => route('projects.show', $p->slug)]))
            ->merge(Developer::published()->where('name', 'ilike', $like)->limit(2)->get()->map(fn ($d) => ['type' => 'Developer', 'label' => $d->name, 'url' => route('developers.show', $d->slug)]))
            ->merge(Property::visible()->where('title', 'ilike', $like)->limit(3)->get()->map(fn ($p) => ['type' => 'Property', 'label' => $p->title, 'url' => route('properties.show', $p->slug)]))
            ->values());
    }
}
