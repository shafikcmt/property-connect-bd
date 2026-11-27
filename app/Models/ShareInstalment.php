<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToDeveloper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareInstalment extends Model
{
    use BelongsToDeveloper;

    protected $guarded = ['developer_id'];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'amount' => 'decimal:2'];
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(ShareHolding::class, 'share_holding_id');
    }
}
