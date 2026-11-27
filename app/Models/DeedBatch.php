<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToDeveloper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One registered deed (or a draft of one) listing many shareholders. Status changes only through DeedRegistry. */
class DeedBatch extends Model
{
    use BelongsToDeveloper;

    protected $guarded = ['developer_id', 'status'];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return ['registered_on' => 'date'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(DeedEntry::class);
    }

    public function isRegistered(): bool
    {
        return $this->status === 'registered';
    }
}
