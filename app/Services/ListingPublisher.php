<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PropertyGroup;
use App\Enums\PublishStatus;
use App\Models\Developer;
use App\Models\Project;
use App\Models\Property;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Publish gate (docs/11 M4/M5): a listing can only go public when it is complete.
 * problems() lists every unmet requirement so the admin can fix them in one pass.
 */
class ListingPublisher
{
    /** @return list<string> */
    public function problems(Model $m): array
    {
        $images = $m->gallery()->where('is_private', false)->count();
        $p = [];

        if ($m instanceof Property) {
            $m->loadMissing('type');
            $p[] = ! $m->property_type_id ? 'Choose a property type.' : null;
            $p[] = ! $m->location_id ? 'Choose a location.' : null;
            $p[] = ($m->price === null && ! $m->price_negotiable) ? 'Set a price or mark it as negotiable.' : null;
            $p[] = (! $m->size_sqft && $m->type?->group !== PropertyGroup::Land) ? 'Set the size (sqft).' : null;
            $p[] = $images < 1 ? 'Add at least one image.' : null;
            $p[] = mb_strlen((string) $m->description) < 80 ? 'Description must be at least 80 characters.' : null;
        } elseif ($m instanceof Project) {
            $p[] = ! $m->developer || $m->developer->publish_status !== PublishStatus::Published ? 'The developer must be published first.' : null;
            $p[] = ! $m->location_id ? 'Choose a location.' : null;
            $p[] = $images < 3 ? 'Add at least three images.' : null;
            $p[] = mb_strlen((string) $m->description) < 150 ? 'Description must be at least 150 characters.' : null;
        } elseif ($m instanceof Developer) {
            $p[] = mb_strlen((string) $m->about) < 50 ? 'Add an "About" text of at least 50 characters.' : null;
            $p[] = ! $m->logo()->exists() ? 'Upload a logo.' : null;
        }

        return array_values(array_filter($p));
    }

    public function publish(Model $m): void
    {
        $problems = $this->problems($m);
        if ($problems) {
            throw new RuntimeException(implode(' ', $problems));
        }

        $m->forceFill(['publish_status' => PublishStatus::Published, 'published_at' => $m->published_at ?? now()])->save();
        app(AuditLogger::class)->log('listing.published', $m, null, ['status' => 'published']);
    }

    public function unpublish(Model $m, PublishStatus $to = PublishStatus::Draft): void
    {
        $m->forceFill(['publish_status' => $to])->save();
        app(AuditLogger::class)->log('listing.unpublished', $m, null, ['status' => $to->value]);
    }
}
