<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyReport extends Model
{
    protected $guarded = [];

    protected $hidden = ['contact', 'ip_hash'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
