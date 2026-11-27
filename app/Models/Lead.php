<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BuyTimeline;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\RequestType;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** Minimal lead (Phase 3/4). Contact data is private: hidden from serialisation, never rendered publicly. */
class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /** Mirrors the column defaults so a freshly created model is already `new` / `normal` without a reload. */
    protected $attributes = ['status' => 'new', 'priority' => 'normal'];

    protected $hidden = ['phone', 'email', 'ip_hash', 'user_agent'];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class, 'priority' => LeadPriority::class, 'request_type' => RequestType::class, 'meta' => 'array',
            'consent_given_at' => 'datetime', 'marketing_consent' => 'boolean', 'assigned_at' => 'datetime',
            'next_follow_up_at' => 'datetime', 'last_activity_at' => 'datetime', 'first_contacted_at' => 'datetime',
            'budget_min' => 'decimal:2', 'budget_max' => 'decimal:2', 'buy_timeline' => BuyTimeline::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $l) => $l->uuid ??= (string) Str::uuid());
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function siteVisits(): HasMany
    {
        return $this->hasMany(SiteVisit::class);
    }

    public function preferredLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'preferred_location_id');
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    /** Budget or preferred area known: the minimum to call a lead qualified (docs/13 §1). */
    public function hasNeeds(): bool
    {
        return $this->budget_min !== null || $this->budget_max !== null || $this->preferred_location_id !== null;
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(LeadStatusHistory::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(LeadFollowUp::class)->orderBy('due_at');
    }

    /** Managers see every lead; agents only the ones assigned to them (docs/13 §8). */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        return $user->can('leads.manage') ? $q : $q->where('assigned_to', $user->id);
    }

    public function scopeOpenPipeline(Builder $q): Builder
    {
        return $q->whereNotIn('status', array_map(fn (LeadStatus $s) => $s->value, array_filter(LeadStatus::cases(), fn (LeadStatus $s) => $s->isTerminal())));
    }

    public function scopeUnassigned(Builder $q): Builder
    {
        return $q->whereNull('assigned_to')->openPipeline();
    }

    public function scopeFollowUpOverdue(Builder $q): Builder
    {
        return $q->openPipeline()->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', now());
    }

    /** New, still uncontacted and older than the first-response target (setting leads.sla_minutes). */
    public function scopeSlaBreached(Builder $q): Builder
    {
        $minutes = (int) app(SettingsService::class)->get('leads.sla_minutes', 30);

        return $q->where('status', LeadStatus::New->value)->whereNull('first_contacted_at')->where('created_at', '<', now()->subMinutes($minutes));
    }

    /** Open lead with no activity for 14 days (docs/13 §1 stale rule). */
    public function scopeStale(Builder $q): Builder
    {
        return $q->openPipeline()->where(fn ($w) => $w->where('last_activity_at', '<', now()->subDays(14))->orWhere(fn ($n) => $n->whereNull('last_activity_at')->where('created_at', '<', now()->subDays(14))));
    }

    public function isSlaBreached(): bool
    {
        $minutes = (int) app(SettingsService::class)->get('leads.sla_minutes', 30);

        return $this->status === LeadStatus::New && $this->first_contacted_at === null && $this->created_at->lt(now()->subMinutes($minutes));
    }
}
