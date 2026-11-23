<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Favorites;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request, Favorites $favorites): View
    {
        return view('favorites', ['properties' => $favorites->properties($request), 'projects' => $favorites->projects($request)]);
    }

    public function toggle(Request $request, Favorites $favorites, string $kind, int $id): JsonResponse
    {
        $on = $favorites->toggle($request, $kind, $id);

        return response()->json(['favorited' => $on, 'count' => $favorites->count($request)]);
    }
}
