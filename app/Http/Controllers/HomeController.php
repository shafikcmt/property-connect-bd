<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyType;
use App\Services\PropertySearch;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(PropertySearch $search): View
    {
        $data = (function () {
            $card = fn ($q) => $q->with(['type', 'location.parent', 'developer', 'coverMedia'])->withCount('gallery as images_count');

            return [
                'featured' => $card(Property::visible()->featured())->latest('published_at')->limit(6)->get(),
                'latest' => $card(Property::visible())->latest('published_at')->limit(8)->get(),
                'ready' => $card(Property::visible()->where('property_status', 'ready')->where('purpose', 'sale'))->latest('published_at')->limit(4)->get(),
                'commercial' => $card(Property::visible()->whereHas('type', fn ($t) => $t->whereIn('group', ['commercial', 'industrial'])))->latest('published_at')->limit(4)->get(),
                'projects' => Project::published()->featured()->with(['developer.logo', 'location.parent', 'coverMedia', 'unitTypes'])->latest('published_at')->limit(4)->get(),
                'upcoming' => Project::published()->where('status', 'upcoming')->with(['developer.logo', 'location.parent', 'coverMedia', 'unitTypes'])->limit(3)->get(),
                'locations' => Location::active()->where('is_featured', true)->where('type', 'area')->withCount(['properties as listings_count' => fn ($q) => $q->visible()])->with('parent')->orderByDesc('listings_count')->limit(8)->get(),
                'developers' => Developer::published()->with('logo')->withCount(['projects as projects_count' => fn ($q) => $q->published()])->orderByDesc('is_featured')->orderBy('name')->limit(8)->get(),
                'types' => PropertyType::active()->withCount(['properties as listings_count' => fn ($q) => $q->visible()])->get()->filter(fn ($t) => $t->listings_count > 0)->values(),
                'posts' => BlogPost::live()->with(['category', 'coverMedia'])->latest('published_at')->limit(3)->get(),
                'stats' => [
                    'properties' => Property::visible()->count(),
                    'projects' => Project::published()->count(),
                    'developers' => Developer::published()->count(),
                    'verified' => Property::visible()->where('is_verified', true)->count(),
                ],
            ];
        })();

        return view('home', $data + ['options' => $search->options()]);
    }
}
