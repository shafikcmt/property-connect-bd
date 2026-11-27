<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ShareStatus;
use App\Models\Concerns\BelongsToDeveloper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectShare extends Model
{
    use BelongsToDeveloper;

    protected $guarded = ['developer_id', 'status'];

    protected $attributes = ['status' => 'available'];

    protected function casts(): array
    {
        return ['status' => ShareStatus::class, 'area_shatangsho' => 'decimal:4', 'list_price' => 'decimal:2'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(ShareHolding::class);
    }

    public function activeHolding(): HasOne
    {
        return $this->hasOne(ShareHolding::class)->where('status', 'active');
    }

    public function label(): string
    {
        return 'Share '.$this->number;
    }
}
