<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Developer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Developer ERP data belongs to exactly one developer (ADR-0019). While a developer's staff member is signed in,
 * every query is limited to their company and new rows are stamped with it. Console, queue and platform staff
 * (no developer_id) are not limited. Policies repeat the check as a second layer.
 */
trait BelongsToDeveloper
{
    protected static function bootBelongsToDeveloper(): void
    {
        static::addGlobalScope('developer', function (Builder $query): void {
            $id = auth()->user()?->developer_id;
            if ($id !== null) {
                $query->where($query->getModel()->getTable().'.developer_id', $id);
            }
        });

        static::creating(function ($model): void {
            if ($model->developer_id === null && ($id = auth()->user()?->developer_id) !== null) {
                $model->developer_id = $id;
            }
        });
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }
}
