<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesSlugs;
use App\Models\Developer;
use Illuminate\View\View;

class DeveloperController extends Controller
{
    use ResolvesSlugs;

    public function index(): View
    {
        $developers = Developer::published()->with('logo')
            ->withCount(['projects as projects_count' => fn ($q) => $q->published()])
            ->orderByRaw('(is_featured and (featured_until is null or featured_until > now())) desc')->orderBy('name')->paginate(12);

        return view('developers.index', compact('developers'));
    }

    public function show(string $slug)
    {
        $found = $this->resolveSlug(Developer::query()->published(), $slug, 'developers.show');
        if (! $found instanceof Developer) {
            return $found;
        }
        $developer = $found->load('logo');
        $projects = $developer->projects()->published()->with(['developer.logo', 'location.parent', 'coverMedia', 'unitTypes'])->get()->groupBy(fn ($p) => $p->status->value);

        return view('developers.show', compact('developer', 'projects'));
    }
}
