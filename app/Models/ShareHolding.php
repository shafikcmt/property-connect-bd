<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToDeveloper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One share sold to one shareholder at an agreed price. Cancelling keeps the row (and its payments) for the record. */
class ShareHolding extends Model
{
    use BelongsToDeveloper;

    protected $guarded = ['developer_id', 'status'];

    protected $attributes = ['status' => 'active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'sold_on' => 'date', 'cancelled_at' => 'datetime'];
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(ProjectShare::class, 'project_share_id');
    }

    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class);
    }

    public function instalments(): HasMany
    {
        return $this->hasMany(ShareInstalment::class)->orderBy('due_on')->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('received_on')->orderByDesc('id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
