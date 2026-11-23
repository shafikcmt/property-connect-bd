<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Project;
use App\Models\Property;
use Illuminate\View\View;

class PageController extends Controller
{
    public function services(): View
    {
        return view('pages.services');
    }

    public function about(): View
    {
        return view('pages.about', ['stats' => ['properties' => Property::visible()->count(), 'projects' => Project::published()->count()]]);
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function faq(): View
    {
        return view('pages.faq', ['faqs' => Faq::published()->get()->groupBy('category')]);
    }

    public function legal(string $page): View
    {
        abort_unless(in_array($page, ['privacy-policy', 'terms-and-conditions', 'cookie-policy', 'disclaimer'], true), 404);

        $bangla = 'pages.legal.'.$page.'-bn';

        return view(app()->getLocale() === 'bn' && view()->exists($bangla) ? $bangla : 'pages.legal.'.$page);
    }
}
