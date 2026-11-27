<?php

declare(strict_types=1);

namespace App\Services\Erp;

use App\Models\Payment;
use App\Models\ShareHolding;
use Carbon\CarbonInterface;

/**
 * Collection, ageing and per-project reports for one developer (ADR-0019). Everything is derived from the ledger,
 * so a report can never disagree with a statement. Ageing buckets count days past the instalment due date.
 */
class ErpReport
{
    public const BUCKETS = ['1–30 days' => [1, 30], '31–60 days' => [31, 60], '61–90 days' => [61, 90], 'Over 90 days' => [91, PHP_INT_MAX]];

    /** @return array{period: array{from: CarbonInterface, to: CarbonInterface}, collected: float, by_method: array<string, float>, by_month: array<string, float>, ageing: array<string, float>, ageing_total: float, rows: list<array<string, mixed>>, projects: list<array<string, mixed>>} */
    public function build(int $developerId, CarbonInterface $from, CarbonInterface $to, ?CarbonInterface $today = null): array
    {
        $today ??= now();
        $payments = Payment::withoutGlobalScopes()->where('developer_id', $developerId)->whereBetween('received_on', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("method, to_char(received_on, 'YYYY-MM') as month, sum(case when kind = 'payment' then amount else -amount end) as total")->groupBy('method', 'month')->get();

        $byMethod = $payments->groupBy('method')->map(fn ($g) => (float) $g->sum('total'))->sortDesc()->all();
        $byMonth = $payments->groupBy('month')->map(fn ($g) => (float) $g->sum('total'))->sortKeys()->all();

        $holdings = ShareHolding::withoutGlobalScopes()->where('developer_id', $developerId)->where('status', 'active')
            ->with(['share.project', 'shareholder', 'instalments', 'payments'])->get();

        $ageing = array_fill_keys(array_keys(self::BUCKETS), 0);
        $rows = [];
        $projects = [];
        foreach ($holdings as $h) {
            $ledger = new HoldingLedger($h, $today);
            $buckets = array_fill_keys(array_keys(self::BUCKETS), 0);
            foreach ($ledger->rows() as $r) {
                if ($r['status'] !== 'overdue') {
                    continue;
                }
                $days = (int) $r['instalment']->due_on->diffInDays($today->copy()->startOfDay());
                $owed = HoldingLedger::cents($r['instalment']->amount) - HoldingLedger::cents($r['paid']);
                foreach (self::BUCKETS as $label => [$lo, $hi]) {
                    if ($days >= $lo && $days <= $hi) {
                        $buckets[$label] += $owed;
                    }
                }
            }
            $overdue = array_sum($buckets);
            foreach ($buckets as $k => $v) {
                $ageing[$k] += $v;
            }
            if ($overdue > 0) {
                $rows[] = ['holding_id' => $h->id, 'project' => $h->share->project->name, 'share' => $h->share->number, 'shareholder' => $h->shareholder->name, 'phone' => $h->shareholder->phone,
                    'overdue' => $overdue / 100.0, 'buckets' => array_map(fn ($v) => $v / 100.0, $buckets), 'oldest_days' => $this->oldestDays($ledger, $today)];
            }

            $pid = $h->share->project_id;
            $p = $projects[$pid] ??= ['project' => $h->share->project->name, 'sold' => 0, 'due' => 0, 'paid' => 0, 'balance' => 0, 'overdue' => 0];
            $p['sold']++;
            $p['due'] += $ledger->totalDueCents();
            $p['paid'] += $ledger->paidCents();
            $p['balance'] += (int) round($ledger->balance() * 100);
            $p['overdue'] += $overdue;
            $projects[$pid] = $p;
        }
        usort($rows, fn ($a, $b) => $b['overdue'] <=> $a['overdue']);

        return [
            'period' => ['from' => $from, 'to' => $to],
            'collected' => array_sum($byMethod), 'by_method' => $byMethod, 'by_month' => $byMonth,
            'ageing' => array_map(fn ($v) => $v / 100.0, $ageing), 'ageing_total' => array_sum($ageing) / 100.0,
            'rows' => $rows,
            'projects' => array_values(array_map(fn ($p) => ['project' => $p['project'], 'sold' => $p['sold'], 'due' => $p['due'] / 100.0, 'paid' => $p['paid'] / 100.0, 'balance' => $p['balance'] / 100.0, 'overdue' => $p['overdue'] / 100.0], $projects)),
        ];
    }

    /** Every payment in the period, one line each, for the collection export. @return list<array<string, mixed>> */
    public function paymentLines(int $developerId, CarbonInterface $from, CarbonInterface $to): array
    {
        return Payment::withoutGlobalScopes()->where('developer_id', $developerId)->whereBetween('received_on', [$from->toDateString(), $to->toDateString()])
            ->with(['holding' => fn ($q) => $q->withoutGlobalScopes(), 'holding.share' => fn ($q) => $q->withoutGlobalScopes(), 'holding.share.project', 'holding.shareholder' => fn ($q) => $q->withoutGlobalScopes()])
            ->orderBy('received_on')->orderBy('id')->get()
            ->map(fn (Payment $p) => [
                'receipt_no' => $p->receipt_no, 'date' => $p->received_on->toDateString(), 'type' => $p->kind, 'project' => $p->holding->share->project->name, 'share' => $p->holding->share->number,
                'shareholder' => $p->holding->shareholder->name, 'method' => $p->method instanceof \BackedEnum ? $p->method->value : $p->method, 'reference' => $p->reference,
                'amount' => ($p->kind === 'payment' ? 1 : -1) * (float) $p->amount,
            ])->all();
    }

    private function oldestDays(HoldingLedger $ledger, CarbonInterface $today): int
    {
        foreach ($ledger->rows() as $r) {
            if ($r['status'] === 'overdue') {
                return (int) $r['instalment']->due_on->diffInDays($today->copy()->startOfDay());
            }
        }

        return 0;
    }
}
