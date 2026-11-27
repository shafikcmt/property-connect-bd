<?php

declare(strict_types=1);

namespace App\Services\Erp;

use App\Enums\PaymentMethod;
use App\Models\Developer;
use App\Models\Payment;
use App\Models\ShareHolding;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Permissions;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Records money received and undoes mistakes (ADR-0019). Rows are never edited or deleted: a wrong entry is cancelled by
 * a reversal that points at it. The system records what happened; it does not move money.
 */
class PaymentRecorder
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function record(ShareHolding $holding, float $amount, CarbonInterface $receivedOn, PaymentMethod $method, ?string $reference, ?string $note, User $by): Payment
    {
        throw_unless($by->can(Permissions::ERP_COLLECT) && $by->developer_id === $holding->developer_id, ErpRuleViolation::class, 'You are not allowed to record payments.');
        throw_unless($holding->isActive(), ErpRuleViolation::class, 'This sale is cancelled; payments cannot be recorded.');
        $cents = HoldingLedger::cents($amount);
        throw_unless($cents > 0, ErpRuleViolation::class, 'The amount must be above zero.');
        throw_if($receivedOn->gt(now()->endOfDay()), ErpRuleViolation::class, 'The payment date cannot be in the future.');
        $ref = ($t = trim((string) $reference)) !== '' ? mb_substr($t, 0, 120) : null;
        throw_if($method !== PaymentMethod::Cash && $ref === null, ErpRuleViolation::class, 'Enter the bank, cheque or transaction reference.');

        return DB::transaction(function () use ($holding, $cents, $receivedOn, $method, $ref, $note, $by) {
            $this->lockDeveloper($holding->developer_id);
            $ledger = new HoldingLedger($holding->fresh());
            throw_if($cents > $ledger->totalDueCents() - $ledger->paidCents(), ErpRuleViolation::class, 'That is more than the balance of '.number_format($ledger->balance(), 2).'.');

            $payment = $this->insert($holding, 'payment', $cents, $receivedOn, $method, $ref, $note, null, $by);
            $this->audit->log('erp.payment_recorded', $payment, null, ['holding' => $holding->id, 'amount' => $cents / 100, 'receipt' => $payment->receipt_no], $by->id);

            return $payment;
        });
    }

    public function reverse(Payment $payment, string $reason, User $by): Payment
    {
        throw_unless($by->can(Permissions::ERP_MANAGE) && $by->developer_id === $payment->developer_id, ErpRuleViolation::class, 'Only the owner can reverse a payment.');
        throw_if($payment->isReversal(), ErpRuleViolation::class, 'A reversal cannot be reversed; record a new payment instead.');
        $reason = trim($reason);
        throw_if($reason === '', ErpRuleViolation::class, 'Give a reason for the reversal.');

        return DB::transaction(function () use ($payment, $reason, $by) {
            $this->lockDeveloper($payment->developer_id);
            throw_if(Payment::query()->where('reverses_payment_id', $payment->id)->exists(), ErpRuleViolation::class, 'This payment was already reversed.');

            $reversal = $this->insert($payment->holding, 'reversal', HoldingLedger::cents($payment->amount), now(), $payment->method, $payment->reference, mb_substr($reason, 0, 500), $payment->id, $by);
            $this->audit->log('erp.payment_reversed', $reversal, null, ['reverses' => $payment->receipt_no, 'reason' => $reason], $by->id);

            return $reversal;
        });
    }

    private function insert(ShareHolding $holding, string $kind, int $cents, CarbonInterface $date, PaymentMethod $method, ?string $reference, ?string $note, ?int $reverses, User $by): Payment
    {
        $next = Payment::withoutGlobalScopes()->where('developer_id', $holding->developer_id)->count() + 1;

        $payment = (new Payment)->forceFill([
            'developer_id' => $holding->developer_id, 'share_holding_id' => $holding->id, 'kind' => $kind, 'amount' => $cents / 100,
            'received_on' => $date->toDateString(), 'method' => $method->value, 'reference' => $reference, 'receipt_no' => sprintf('R%06d', $next),
            'note' => $note ? mb_substr(trim($note), 0, 500) : null, 'reverses_payment_id' => $reverses, 'recorded_by' => $by->id,
        ]);
        $payment->save();

        return $payment;
    }

    /** Serialises receipt numbering per developer so two clerks cannot be given the same number. */
    private function lockDeveloper(int $developerId): void
    {
        Developer::withoutGlobalScopes()->whereKey($developerId)->lockForUpdate()->first();
    }
}
