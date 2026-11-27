<?php

declare(strict_types=1);

namespace App\Services\Erp;

use App\Models\Payment;
use App\Models\ProjectShare;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Whole-company numbers in a few queries. The overdue figure uses the same rule as HoldingLedger: payments clear the
 * oldest instalments first, so what is overdue is (instalments due before today) minus (net paid), never below zero.
 */
class ErpSummary
{
    private const HOLDING_SQL = "
        select h.id, h.project_share_id, h.shareholder_id,
               coalesce(i.total, h.price) as total_due,
               coalesce(p.paid, 0) as paid,
               greatest(0, coalesce(i.due_to_date, 0) - coalesce(p.paid, 0)) as overdue
        from share_holdings h
        left join (select share_holding_id, sum(amount) as total, sum(amount) filter (where due_on < ?) as due_to_date from share_instalments group by 1) i on i.share_holding_id = h.id
        left join (select share_holding_id, sum(case when kind = 'payment' then amount else -amount end) as paid from payments group by 1) p on p.share_holding_id = h.id
        where h.developer_id = ? and h.status = 'active'";

    /** @return array{shares: int, sold: int, available: int, due: float, paid: float, balance: float, overdue: float, overdue_holdings: int, collected_month: float} */
    public function forDeveloper(int $developerId, ?CarbonInterface $today = null): array
    {
        $today ??= now();
        $rows = collect(DB::select(self::HOLDING_SQL, [$today->toDateString(), $developerId]));

        $shares = ProjectShare::withoutGlobalScopes()->where('developer_id', $developerId);
        $month = Payment::withoutGlobalScopes()->where('developer_id', $developerId)->whereBetween('received_on', [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()])
            ->selectRaw("coalesce(sum(case when kind = 'payment' then amount else -amount end), 0) as total")->value('total');

        $due = (float) $rows->sum('total_due');
        $paid = (float) $rows->sum('paid');

        return [
            'shares' => (clone $shares)->count(),
            'sold' => (clone $shares)->where('status', 'sold')->count(),
            'available' => (clone $shares)->where('status', 'available')->count(),
            'due' => $due, 'paid' => $paid, 'balance' => max(0, $due - $paid),
            'overdue' => (float) $rows->sum('overdue'),
            'overdue_holdings' => $rows->filter(fn ($r) => (float) $r->overdue > 0)->count(),
            'collected_month' => (float) $month,
        ];
    }

    /** Holdings that are behind, worst first. @return list<array{id: int, overdue: float}> */
    public function overdueHoldings(int $developerId, ?CarbonInterface $today = null, int $limit = 10): array
    {
        $today ??= now();

        return collect(DB::select(self::HOLDING_SQL, [$today->toDateString(), $developerId]))
            ->filter(fn ($r) => (float) $r->overdue > 0)->sortByDesc(fn ($r) => (float) $r->overdue)->take($limit)
            ->map(fn ($r) => ['id' => (int) $r->id, 'overdue' => (float) $r->overdue])->values()->all();
    }
}
