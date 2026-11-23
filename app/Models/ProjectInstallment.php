<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasBangla;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'sort_order', 'label', 'timing', 'amount', 'note', 'label_bn', 'timing_bn', 'note_bn'])]
class ProjectInstallment extends Model
{
    use HasBangla;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
