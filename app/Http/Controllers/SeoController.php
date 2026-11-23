<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Project;
use App\Models\Property;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = cache()->remember('pcbd.sitemap', 3600, function () {
            $urls = collect([['/', null], ['/properties', null], ['/properties/for-sale', null], ['/properties/for-rent', null], ['/projects', null], ['/developers', null], ['/locations', null],
                ['/services', null], ['/about', null], ['/contact', null], ['/faq', null], ['/blog', null], ['/sell', null], ['/rent-out', null], ['/valuation', null], ['/submit-property', null],
                ['/privacy-policy', null], ['/terms-and-conditions', null]])->map(fn ($u) => [url($u[0]), null]);
            $urls = $urls
                ->merge(Property::visible()->get(['slug', 'updated_at'])->map(fn ($m) => [route('properties.show', $m->slug), $m->updated_at]))
                ->merge(Project::published()->get(['slug', 'updated_at'])->map(fn ($m) => [route('projects.show', $m->slug), $m->updated_at]))
                ->merge(Developer::published()->get(['slug', 'updated_at'])->map(fn ($m) => [route('developers.show', $m->slug), $m->updated_at]))
                ->merge(BlogPost::live()->get(['slug', 'updated_at'])->map(fn ($m) => [route('blog.show', $m->slug), $m->updated_at]))
                // Location pages only when they carry unique copy (docs/12 §2).
                ->merge(Location::active()->whereNotNull('description')->where('description', '!=', '')->get(['path', 'updated_at'])->map(fn ($m) => [route('locations.show', $m->path), $m->updated_at]));

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $lines = app()->environment('production')
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /account', 'Disallow: /search', 'Disallow: /compare', 'Disallow: /favorites', 'Disallow: /login', 'Disallow: /register', 'Allow: /', '', 'Sitemap: '.url('/sitemap.xml')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
