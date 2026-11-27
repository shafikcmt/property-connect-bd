<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToDeveloper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line of a project's instalment template: `count` instalments of `amount`, every `interval_months`, starting `start_after_months` after the sale. */
class InstalmentPlanItem extends Model
{
    use BelongsToDeveloper;

    protected $guarded = ['developer_id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
