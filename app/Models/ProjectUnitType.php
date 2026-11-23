<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'name', 'bedrooms', 'bathrooms', 'size_sqft_min', 'size_sqft_max', 'price_from', 'price_to', 'sort_order'])]
class ProjectUnitType extends Model
{
    use HasMedia;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
