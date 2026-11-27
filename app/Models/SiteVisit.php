<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SiteVisitStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisit extends Model
{
    protected $guarded = [];

    protected $attributes = ['status' => 'requested'];

    protected function casts(): array
    {
        return [
            'status' => SiteVisitStatus::class, 'preferred_date' => 'date', 'scheduled_at' => 'datetime',
            'confirmed_at' => 'datetime', 'completed_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** Managers see all visits; agents the ones they were given or whose lead is theirs. */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        return $user->can('leads.manage') ? $q : $q->where(fn ($w) => $w->where('assigned_to', $user->id)->orWhereHas('lead', fn ($l) => $l->where('assigned_to', $user->id)));
    }
}
