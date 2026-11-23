<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasMedia
{
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('sort_order')->orderBy('id');
    }

    public function gallery(): MorphMany
    {
        return $this->media()->where('collection', 'gallery');
    }

    public function floorPlans(): MorphMany
    {
        return $this->media()->where('collection', 'floor_plans');
    }

    /** First public gallery image by order — used as the card/cover image. */
    public function coverMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where('collection', 'gallery')->where('is_private', false)->orderBy('sort_order')->orderBy('id');
    }
}
