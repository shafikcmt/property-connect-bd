<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToDeveloper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A shareholder's share inside a deed, with its mutation progress. */
class DeedEntry extends Model
{
    use BelongsToDeveloper;

    protected $guarded = ['developer_id', 'mutation_status'];

    protected $attributes = ['mutation_status' => 'none'];

    protected function casts(): array
    {
        return ['mutation_applied_on' => 'date', 'mutation_completed_on' => 'date'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DeedBatch::class, 'deed_batch_id');
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(ShareHolding::class, 'share_holding_id');
    }
}
