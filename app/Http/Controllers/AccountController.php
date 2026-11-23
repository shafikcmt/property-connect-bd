<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Property;
use App\Models\SavedSearch;
use App\Models\SiteVisit;
use App\Services\Favorites;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Buyer/owner area. Every query is scoped through the authenticated user (no unscoped find()). */
class AccountController extends Controller
{
    public function index(Request $request, Favorites $favorites): View
    {
        $u = $request->user();

        return view('account.index', [
            'user' => $u, 'favorites' => $favorites->count($request),
            'inquiries' => Lead::where('user_id', $u->id)->count(), 'visits' => SiteVisit::where('user_id', $u->id)->count(),
            'searches' => SavedSearch::where('user_id', $u->id)->count(), 'submissions' => Property::where('submitted_by', $u->id)->count(),
        ]);
    }

    public function favorites(Request $request, Favorites $favorites): View
    {
        return view('favorites', ['properties' => $favorites->properties($request), 'projects' => $favorites->projects($request), 'account' => true]);
    }

    public function inquiries(Request $request): View
    {
        return view('account.inquiries', ['leads' => Lead::where('user_id', $request->user()->id)->with(['property', 'project'])->latest()->paginate(15)]);
    }

    public function visits(Request $request): View
    {
        return view('account.visits', ['visits' => SiteVisit::where('user_id', $request->user()->id)->with(['property', 'project'])->latest()->paginate(15)]);
    }

    public function submissions(Request $request): View
    {
        return view('account.submissions', ['properties' => Property::where('submitted_by', $request->user()->id)->with('coverMedia')->latest()->paginate(15)]);
    }

    public function recent(Request $request): View
    {
        $ids = $request->session()->get('recent', []);
        $properties = Property::visible()->whereIn('id', $ids)->with(['type', 'location.parent', 'developer', 'coverMedia'])->withCount('gallery as images_count')->get()
            ->sortBy(fn ($p) => array_search($p->id, $ids))->values();

        return view('account.recent', compact('properties'));
    }

    public function searches(Request $request): View
    {
        return view('account.searches', ['searches' => SavedSearch::where('user_id', $request->user()->id)->latest()->get()]);
    }

    public function storeSearch(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'filters' => ['required', 'array'], 'filters.*' => ['nullable']]);
        abort_if(SavedSearch::where('user_id', $request->user()->id)->count() >= 20, 422, 'You can save up to 20 searches.');
        $filters = collect($data['filters'])->filter(fn ($v) => $v !== null && $v !== '' && $v !== [])->all();
        SavedSearch::create(['user_id' => $request->user()->id, 'name' => $data['name'], 'filters' => $filters]);

        return back()->with('status', 'Search saved.');
    }

    public function destroySearch(Request $request, int $id): RedirectResponse
    {
        SavedSearch::where('user_id', $request->user()->id)->whereKey($id)->firstOrFail()->delete();

        return back()->with('status', 'Saved search removed.');
    }

    public function profile(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', fn ($a, $v, $f) => ! $v || Phone::isValid((string) $v) ?: $f(__('Please enter a valid phone number.'))],
        ]);
        $user->update(['name' => $data['name'], 'phone' => $data['phone'] ? Phone::normalize($data['phone']) : null]);

        return back()->with('status', 'Profile updated.');
    }
}
