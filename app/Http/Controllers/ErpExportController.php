<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\AuditLogger;
use App\Services\Erp\CsvExport;
use App\Services\Erp\ErpReport;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV downloads of money reports. Requires the same permission as seeing payments; every download is audited. */
class ErpExportController extends Controller
{
    public function __invoke(Request $request, string $report, ErpReport $reports, AuditLogger $audit): StreamedResponse
    {
        Gate::authorize('viewAny', Payment::class);
        $user = $request->user();
        abort_unless($user->developer_id !== null && $user->can(Permissions::ERP_VIEW), 403);
        abort_unless(in_array($report, ['collections', 'overdue'], true), 404);

        $days = in_array($request->query('days'), ['30', '90', '365'], true) ? (int) $request->query('days') : 30;
        $to = now();
        $from = $to->copy()->subDays($days)->startOfDay();
        $audit->log('erp.report_exported', null, null, ['report' => $report, 'days' => $days], $user->id);

        if ($report === 'collections') {
            $lines = $reports->paymentLines($user->developer_id, $from, $to);

            return CsvExport::download('collections-'.$to->format('Y-m-d').'.csv', ['Receipt', 'Date', 'Type', 'Project', 'Share', 'Shareholder', 'Method', 'Reference', 'Amount (BDT)'],
                array_map(fn ($l) => [$l['receipt_no'], $l['date'], $l['type'], $l['project'], $l['share'], $l['shareholder'], $l['method'], $l['reference'], number_format($l['amount'], 2, '.', '')], $lines));
        }

        $data = $reports->build($user->developer_id, $from, $to);
        $labels = array_keys(ErpReport::BUCKETS);

        return CsvExport::download('overdue-'.$to->format('Y-m-d').'.csv', ['Project', 'Share', 'Shareholder', 'Phone', 'Oldest overdue (days)', ...$labels, 'Total overdue (BDT)'],
            array_map(fn ($r) => [$r['project'], $r['share'], $r['shareholder'], $r['phone'], $r['oldest_days'], ...array_map(fn ($b) => number_format($r['buckets'][$b], 2, '.', ''), $labels), number_format($r['overdue'], 2, '.', '')], $data['rows']));
    }
}
