<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Favorite;
use App\Models\Project;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Favorites for guests (session) and members (database). Guest favorites are merged
 * into the account at login (see MergeGuestFavorites). Max 200 per user.
 */
class Favorites
{
    private const MAP = ['property' => Property::class, 'project' => Project::class];

    public const MAX = 200;

    /** @return list<int> ids of the given kind */
    public function ids(Request $request, string $kind): array
    {
        if (! $request->hasSession()) {
            return []; // e.g. error pages rendered before the session middleware ran
        }

        if ($user = $request->user()) {
            return Favorite::where('user_id', $user->id)->where('favoritable_type', (new (self::MAP[$kind]))->getMorphClass())->pluck('favoritable_id')->all();
        }

        return array_values(array_map('intval', $request->session()->get("fav.$kind", [])));
    }

    /** @return bool true when now favorited */
    public function toggle(Request $request, string $kind, int $id): bool
    {
        abort_unless(isset(self::MAP[$kind]), 404);
        $class = self::MAP[$kind];
        $visible = $kind === 'property' ? $class::visible() : $class::published();
        abort_unless($visible->whereKey($id)->exists(), 404);

        if ($user = $request->user()) {
            $row = Favorite::where(['user_id' => $user->id, 'favoritable_type' => (new $class)->getMorphClass(), 'favoritable_id' => $id]);
            if ($row->exists()) {
                $row->delete();

                return false;
            }
            abort_if(Favorite::where('user_id', $user->id)->count() >= self::MAX, 422, 'Favorites limit reached.');
            Favorite::create(['user_id' => $user->id, 'favoritable_type' => (new $class)->getMorphClass(), 'favoritable_id' => $id]);

            return true;
        }

        $list = collect($request->session()->get("fav.$kind", []));
        $on = ! $list->contains($id);
        $request->session()->put("fav.$kind", ($on ? $list->push($id) : $list->reject(fn ($x) => $x === $id))->unique()->take(self::MAX)->values()->all());

        return $on;
    }

    public function count(Request $request): int
    {
        return count($this->ids($request, 'property')) + count($this->ids($request, 'project'));
    }

    public function merge(User $user, array $session): void
    {
        foreach (self::MAP as $kind => $class) {
            foreach ((array) ($session[$kind] ?? []) as $id) {
                if (Favorite::where('user_id', $user->id)->count() >= self::MAX) {
                    return;
                }
                $exists = ($kind === 'property' ? $class::visible() : $class::published())->whereKey($id)->exists();
                if ($exists) {
                    Favorite::firstOrCreate(['user_id' => $user->id, 'favoritable_type' => (new $class)->getMorphClass(), 'favoritable_id' => (int) $id]);
                }
            }
        }
    }

    /** @return Collection<int, Property> */
    public function properties(Request $request): Collection
    {
        $ids = $this->ids($request, 'property');

        return Property::visible()->whereIn('id', $ids)->with(['type', 'location.parent', 'developer', 'coverMedia'])->withCount('gallery as images_count')->get();
    }

    /** @return Collection<int, Project> */
    public function projects(Request $request): Collection
    {
        return Project::published()->whereIn('id', $this->ids($request, 'project'))->with(['developer.logo', 'location.parent', 'coverMedia', 'unitTypes'])->get();
    }
}
