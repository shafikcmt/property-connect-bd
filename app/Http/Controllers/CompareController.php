<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompareController extends Controller
{
    public const MAX = 4;

    public function index(Request $request): View
    {
        $ids = $request->session()->get('compare', []);
        $properties = Property::visible()->whereIn('id', $ids)->with(['type', 'location.parent', 'developer', 'amenities', 'coverMedia'])->get()->sortBy(fn ($p) => array_search($p->id, $ids))->values();

        return view('compare', compact('properties'));
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        abort_unless(Property::visible()->whereKey($id)->exists(), 404);
        $list = collect($request->session()->get('compare', []));

        if ($list->contains($id)) {
            $list = $list->reject(fn ($x) => $x === $id);
            $on = false;
        } elseif ($list->count() >= self::MAX) {
            return response()->json(['error' => 'You can compare up to '.self::MAX.' properties.', 'count' => $list->count()], 422);
        } else {
            $list->push($id);
            $on = true;
        }
        $request->session()->put('compare', $list->values()->all());

        return response()->json(['compared' => $on, 'count' => $list->count()]);
    }

    public function clear(Request $request)
    {
        $request->session()->forget('compare');

        return redirect()->route('compare');
    }
}
