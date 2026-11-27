<?php

declare(strict_types=1);

namespace App\Services\Erp;

use App\Models\ShareHolding;
use App\Models\ShareInstalment;
use Carbon\CarbonInterface;

/**
 * What a shareholder owes and has paid, computed from the append-only records (nothing is stored twice).
 * Payments are applied to instalments oldest-due first. All arithmetic is in whole poisha, never floats.
 */
final class HoldingLedger
{
    /** @var list<array{instalment: ShareInstalment, paid: int, status: string}> */
    private array $rows = [];

    private int $paid;

    private int $scheduled;

    private int $price;

    public function __construct(public readonly ShareHolding $holding, ?CarbonInterface $today = null)
    {
        $today ??= now();
        $this->price = self::cents($holding->price);
        $payments = $holding->relationLoaded('payments') ? $holding->payments : $holding->payments()->get();
        $this->paid = (int) $payments->sum(fn ($p) => self::cents($p->amount) * ($p->isReversal() ? -1 : 1));

        $left = $this->paid;
        $this->scheduled = 0;
        foreach ($holding->relationLoaded('instalments') ? $holding->instalments : $holding->instalments()->get() as $i) {
            $amount = self::cents($i->amount);
            $this->scheduled += $amount;
            $take = max(0, min($left, $amount));
            $left -= $take;
            $status = match (true) {
                $take >= $amount => 'paid',
                $i->due_on->lt($today->copy()->startOfDay()) => 'overdue',
                $i->due_on->isSameDay($today) => 'due',
                $take > 0 => 'partial',
                default => 'upcoming',
            };
            $this->rows[] = ['instalment' => $i, 'paid' => $take, 'status' => $status];
        }
    }

    /** @return list<array{instalment: ShareInstalment, paid: float, status: string}> */
    public function rows(): array
    {
        return array_map(fn ($r) => ['instalment' => $r['instalment'], 'paid' => $r['paid'] / 100, 'status' => $r['status']], $this->rows);
    }

    public function price(): float
    {
        return $this->price / 100;
    }

    public function scheduled(): float
    {
        return $this->scheduled / 100;
    }

    public function paid(): float
    {
        return $this->paid / 100;
    }

    /** The amount the shareholder is expected to pay in total: the instalment schedule when there is one, else the price. */
    public function totalDue(): float
    {
        return $this->totalDueCents() / 100;
    }

    public function balance(): float
    {
        return max(0, $this->totalDueCents() - $this->paid) / 100;
    }

    public function overdue(): float
    {
        return array_sum(array_map(fn ($r) => $r['status'] === 'overdue' ? self::cents($r['instalment']->amount) - $r['paid'] : 0, $this->rows)) / 100;
    }

    public function overdueCount(): int
    {
        return count(array_filter($this->rows, fn ($r) => $r['status'] === 'overdue'));
    }

    public function nextDue(): ?ShareInstalment
    {
        foreach ($this->rows as $r) {
            if ($r['status'] !== 'paid') {
                return $r['instalment'];
            }
        }

        return null;
    }

    public function totalDueCents(): int
    {
        return $this->scheduled > 0 ? $this->scheduled : $this->price;
    }

    public function paidCents(): int
    {
        return $this->paid;
    }

    public static function cents(float|int|string|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
