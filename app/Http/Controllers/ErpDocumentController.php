<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\ShareHolding;
use App\Services\Erp\HoldingLedger;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Printable receipt and statement. Route binding goes through the developer scope, so another company's records
 * answer 404; the policy then checks the signed-in user's role. English only: these are working documents.
 */
class ErpDocumentController extends Controller
{
    public function receipt(Payment $payment): View
    {
        Gate::authorize('view', $payment);
        $payment->load(['holding.shareholder', 'holding.share.project', 'recorder', 'developer', 'reverses']);

        return view('erp.receipt', ['payment' => $payment, 'ledger' => new HoldingLedger($payment->holding)]);
    }

    public function statement(ShareHolding $holding): View
    {
        Gate::authorize('view', $holding);
        $holding->load(['shareholder', 'share.project', 'developer']);

        return view('erp.statement', ['holding' => $holding, 'ledger' => new HoldingLedger($holding), 'payments' => auth()->user()->can('viewAny', Payment::class) ? $holding->payments()->with('recorder')->get()->sortBy('id') : collect()]);
    }
}
