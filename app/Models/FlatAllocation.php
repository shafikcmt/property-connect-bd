<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToDeveloper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The flat promised to one share sale, plus its handover checklist. Changed only through FlatAllocator. */
class FlatAllocation extends Model
{
    use BelongsToDeveloper;

    /** Handover checklist: every item must be ticked before a flat is handed over. */
    public const CHECKLIST = ['dues_cleared' => 'All dues cleared', 'documents' => 'Documents ready', 'inspection' => 'Joint inspection done', 'keys' => 'Keys handed over'];

    protected $guarded = ['developer_id', 'status'];

    protected $attributes = ['status' => 'allocated'];

    protected function casts(): array
    {
        return ['allocated_on' => 'date', 'handed_over_on' => 'date', 'checklist' => 'array'];
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(ShareHolding::class, 'share_holding_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function flatLabel(): string
    {
        return trim(($this->building !== '' ? $this->building.' · ' : '').'Floor '.$this->floor.' · Flat '.$this->flat_no);
    }
}
