<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesSlugs;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Project;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use ResolvesSlugs;

    public function index(Request $request, ?string $status = null): View
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:upcoming,ongoing,ready,completed'], 'location' => ['nullable', 'string', 'max:255'],
            'developer' => ['nullable', 'string', 'max:255'], 'q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $status = $status ?: ($data['status'] ?? null);
        abort_if($status && ! in_array($status, ['upcoming', 'ongoing', 'ready', 'completed'], true), 404);

        $q = Project::published()->with(['developer.logo', 'location.parent', 'coverMedia', 'unitTypes'])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($data['developer'] ?? null, fn ($q, $d) => $q->whereIn('developer_id', Developer::where('slug', $d)->select('id')))
            ->when($data['location'] ?? null, fn ($q, $p) => ($loc = Location::where('path', $p)->first()) ? $q->whereIn('location_id', Location::within($loc)->select('id')) : $q)
            ->when($data['q'] ?? null, fn ($q, $t) => $q->where('name', 'ilike', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $t).'%'))
            ->orderByRaw('(is_featured and (featured_until is null or featured_until > now())) desc')->latest('published_at');

        return view('projects.index', [
            'projects' => $q->paginate(9)->withQueryString(), 'status' => $status, 'filters' => $data,
            'developers' => Developer::published()->orderBy('name')->get(['name', 'slug']),
            'locations' => Location::active()->whereIn('type', ['city', 'area'])->with('parent')->orderBy('path')->get(),
            'indexable' => ! $request->hasAny(['location', 'developer', 'q', 'page']),
        ]);
    }

    public function show(string $slug)
    {
        $found = $this->resolveSlug(Project::query()->published(), $slug, 'projects.show');
        if (! $found instanceof Project) {
            return $found;
        }
        $project = $found->load(['developer.logo', 'location.parent.parent', 'amenities', 'unitTypes', 'media', 'installments', 'updates' => fn ($q) => $q->where('is_published', true)]);

        $availability = $project->available_units_verified
            ? $project->units()->selectRaw("count(*) filter (where status = 'available') as available, count(*) as total")->first()
            : null;
        $similar = Project::published()->where('id', '!=', $project->id)->where(fn ($q) => $q->where('location_id', $project->location_id)->orWhere('status', $project->status->value))
            ->with(['developer.logo', 'location.parent', 'coverMedia', 'unitTypes'])->limit(3)->get();
        $properties = Property::visible()->where('project_id', $project->id)->with(['type', 'location.parent', 'developer', 'coverMedia'])->withCount('gallery as images_count')->limit(6)->get();

        return view('projects.show', compact('project', 'availability', 'similar', 'properties'));
    }
}
