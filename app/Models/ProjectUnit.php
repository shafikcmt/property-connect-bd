<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Facing;
use App\Enums\UnitStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'unit_type_id', 'unit_no', 'floor', 'facing', 'size_sqft', 'price', 'status'])]
class ProjectUnit extends Model
{
    protected function casts(): array
    {
        return ['status' => UnitStatus::class, 'facing' => Facing::class];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(ProjectUnitType::class, 'unit_type_id');
    }
}
