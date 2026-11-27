<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\BelongsToDeveloper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/** A money movement. Append-only (also enforced by a database trigger): a mistake is undone with a `reversal` row. */
class Payment extends Model
{
    use BelongsToDeveloper;

    public const UPDATED_AT = null;

    protected $guarded = ['developer_id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'received_on' => 'date', 'method' => PaymentMethod::class, 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Payments cannot be edited; record a reversal instead.'));
        static::deleting(fn () => throw new LogicException('Payments cannot be deleted; record a reversal instead.'));
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(ShareHolding::class, 'share_holding_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_payment_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reverses_payment_id');
    }

    public function isReversal(): bool
    {
        return $this->kind === 'reversal';
    }

    /** Signed effect on what the shareholder has paid. */
    public function signedAmount(): float
    {
        return $this->isReversal() ? -(float) $this->amount : (float) $this->amount;
    }
}
