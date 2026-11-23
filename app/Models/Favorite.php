<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Favorite extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }
}
