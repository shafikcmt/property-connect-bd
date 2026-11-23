<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\SlugHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

trait ResolvesSlugs
{
    /**
     * Returns the record for a slug, or a 301 to the current URL when the slug was renamed.
     *
     * @return Model|Response
     */
    protected function resolveSlug(Builder $query, string $slug, string $routeName)
    {
        $model = (clone $query)->where($query->getModel()->getTable().'.slug', $slug)->first();
        if ($model) {
            return $model;
        }

        $old = SlugHistory::where('sluggable_type', $query->getModel()->getMorphClass())->where('slug', $slug)->latest('id')->first();
        $current = $old ? (clone $query)->whereKey($old->sluggable_id)->first() : null;
        abort_unless($current, 404);

        return redirect()->route($routeName, $current->slug, 301);
    }
}
