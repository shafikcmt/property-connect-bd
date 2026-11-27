<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToDeveloper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstructionMilestone extends Model
{
    use BelongsToDeveloper;

    protected $guarded = ['developer_id'];

    protected function casts(): array
    {
        return ['happened_on' => 'date'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
