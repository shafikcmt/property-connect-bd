<?php

use App\Enums\PaymentMethod;
use App\Enums\RoleName;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\Shareholder;
use App\Models\ShareHolding;
use App\Services\Erp\ErpRuleViolation;
use App\Services\Erp\ErpSummary;
use App\Services\Erp\HoldingLedger;
use App\Services\Erp\PaymentRecorder;
use App\Services\Erp\ShareSale;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('creates numbered shares, sells one with an instalment schedule and keeps the public counts in step', function () {
    $dev = erpDeveloper();
    $project = erpProject($dev);
    $sales = erpUser(RoleName::DeveloperSales, $dev);

    expect($project->total_shares)->toBe(5)->and($project->sold_shares)->toBe(0);

    $share = ProjectShare::where('project_id', $project->id)->where('number', 2)->first();
    $holding = app(ShareSale::class)->sell($share, erpHolder($dev), 900_000, now(), $sales);

    $holding = $holding->fresh();
    expect($share->fresh()->status->value)->toBe('sold')->and($holding->instalments)->toHaveCount(8)
        ->and($holding->instalments->first()->label)->toBe('Booking')->and($holding->instalments->last()->label)->toBe('Monthly instalment (7/7)')
        ->and((float) $holding->instalments->sum('amount'))->toBe(900_000.0)
        ->and($holding->instalments[1]->due_on->toDateString())->toBe(now()->addMonthNoOverflow()->toDateString())
        ->and($project->fresh()->sold_shares)->toBe(1)->and($project->fresh()->total_shares)->toBe(5);

    expect(fn () => app(ShareSale::class)->sell($share, erpHolder($dev, 'Other'), 900_000, now(), $sales))->toThrow(ErpRuleViolation::class, 'not available');
});

it('only lets the right roles act and never across companies', function () {
    $dev = erpDeveloper();
    $project = erpProject($dev);
    $share = ProjectShare::where('project_id', $project->id)->first();
    $accountant = erpUser(RoleName::DeveloperAccountant, $dev);
    $sales = erpUser(RoleName::DeveloperSales, $dev);
    $stranger = erpUser(RoleName::DeveloperOwner);

    expect(fn () => app(ShareSale::class)->sell($share, erpHolder($dev), 900_000, now(), $accountant))->toThrow(ErpRuleViolation::class, 'not allowed');
    expect(fn () => app(ShareSale::class)->generateShares($project, 3, null, null, $sales))->toThrow(ErpRuleViolation::class, 'not allowed');
    expect(fn () => app(ShareSale::class)->sell($share, erpHolder($dev), 900_000, now(), $stranger))->toThrow(ErpRuleViolation::class, 'not allowed');

    $holding = app(ShareSale::class)->sell($share, erpHolder($dev), 900_000, now(), $sales);
    expect(fn () => app(ShareSale::class)->cancel($holding, 'Changed mind', $sales))->toThrow(ErpRuleViolation::class, 'not allowed');
    expect(fn () => app(PaymentRecorder::class)->record($holding, 1000, now(), PaymentMethod::Cash, null, null, $sales))->toThrow(ErpRuleViolation::class, 'not allowed');
});

it('scopes every ERP query to the signed-in developer', function () {
    $a = erpDeveloper();
    $b = erpDeveloper();
    erpProject($a, 2);
    erpProject($b, 3);
    erpHolder($a, 'A holder');
    erpHolder($b, 'B holder');

    expect(ProjectShare::withoutGlobalScopes()->count())->toBe(5);
    $this->actingAs(userWithRole(RoleName::DeveloperOwner, ['developer_id' => $a->id]));
    expect(ProjectShare::count())->toBe(2)->and(Shareholder::pluck('name')->all())->toBe(['A holder']);

    $this->actingAs(userWithRole(RoleName::DeveloperOwner, ['developer_id' => $b->id]));
    expect(ProjectShare::count())->toBe(3)->and(Shareholder::pluck('name')->all())->toBe(['B holder']);
});

it('stores the NID encrypted and shows only its last four digits', function () {
    $dev = erpDeveloper();
    $h = erpHolder($dev);

    expect($h->maskedNid())->toBe('••••••9012')->and(DB::table('shareholders')->where('id', $h->id)->value('nid'))->not->toContain('9012')
        ->and($h->fresh()->nid)->toBe('1234 5678 9012')->and(array_key_exists('nid', $h->toArray()))->toBeFalse();
});

it('records payments with sequential receipts, applies them oldest-first and refuses overpayment', function () {
    $dev = erpDeveloper();
    $project = erpProject($dev, 2);
    $owner = erpUser(RoleName::DeveloperOwner, $dev);
    $accountant = erpUser(RoleName::DeveloperAccountant, $dev);
    $holding = app(ShareSale::class)->sell(ProjectShare::where('project_id', $project->id)->first(), erpHolder($dev), 900_000, now()->subMonths(3)->startOfDay(), $owner);
    $rec = app(PaymentRecorder::class);

    expect(fn () => $rec->record($holding, 0, now(), PaymentMethod::Cash, null, null, $accountant))->toThrow(ErpRuleViolation::class, 'above zero');
    expect(fn () => $rec->record($holding, 5000, now(), PaymentMethod::Bkash, '', null, $accountant))->toThrow(ErpRuleViolation::class, 'reference');
    expect(fn () => $rec->record($holding, 5000, now()->addDay(), PaymentMethod::Cash, null, null, $accountant))->toThrow(ErpRuleViolation::class, 'future');

    $p1 = $rec->record($holding, 200_000, now()->subMonths(3), PaymentMethod::Cash, null, 'Booking', $accountant);
    $p2 = $rec->record($holding, 150_000, now()->subMonth(), PaymentMethod::Bkash, 'TX123', null, $accountant);
    expect([$p1->receipt_no, $p2->receipt_no])->toBe(['R000001', 'R000002']);

    $ledger = new HoldingLedger($holding->fresh());
    $rows = $ledger->rows();
    expect($ledger->paid())->toBe(350_000.0)->and($ledger->totalDue())->toBe(900_000.0)->and($ledger->balance())->toBe(550_000.0)
        ->and($rows[0]['status'])->toBe('paid')->and($rows[1]['status'])->toBe('paid')->and($rows[2]['status'])->toBe('overdue')
        ->and((float) $rows[2]['paid'])->toBe(50_000.0)->and($rows[3]['status'])->toBe('due')->and($rows[4]['status'])->toBe('upcoming')
        ->and($ledger->overdue())->toBe(50_000.0)->and($ledger->overdueCount())->toBe(1)->and($ledger->nextDue()->label)->toContain('(2/7)');

    expect(fn () => $rec->record($holding, 600_000, now(), PaymentMethod::Cash, null, null, $accountant))->toThrow(ErpRuleViolation::class, 'balance');
    $rec->record($holding, 550_000, now(), PaymentMethod::BankTransfer, 'DD-77', null, $accountant);
    expect((new HoldingLedger($holding->fresh()))->balance())->toBe(0.0);
});

it('undoes a payment with a reversal and keeps money records append-only, even at the database level', function () {
    $dev = erpDeveloper();
    $project = erpProject($dev, 1);
    $owner = erpUser(RoleName::DeveloperOwner, $dev);
    $accountant = erpUser(RoleName::DeveloperAccountant, $dev);
    $holding = app(ShareSale::class)->sell(ProjectShare::where('project_id', $project->id)->first(), erpHolder($dev), 900_000, now(), $owner);
    $rec = app(PaymentRecorder::class);
    $payment = $rec->record($holding, 100_000, now(), PaymentMethod::Cash, null, null, $accountant);

    expect(fn () => $rec->reverse($payment, 'Entered twice', $accountant))->toThrow(ErpRuleViolation::class, 'owner');
    expect(fn () => $rec->reverse($payment, ' ', $owner))->toThrow(ErpRuleViolation::class, 'reason');
    $reversal = $rec->reverse($payment, 'Entered twice', $owner);

    expect($reversal->kind)->toBe('reversal')->and($reversal->reverses_payment_id)->toBe($payment->id)->and((new HoldingLedger($holding->fresh()))->paid())->toBe(0.0);
    expect(fn () => $rec->reverse($payment, 'again', $owner))->toThrow(ErpRuleViolation::class, 'already');
    expect(fn () => $rec->reverse($reversal, 'oops', $owner))->toThrow(ErpRuleViolation::class, 'reversal');

    expect(fn () => $payment->fresh()->update(['amount' => 1]))->toThrow(LogicException::class);
    expect(fn () => $payment->fresh()->delete())->toThrow(LogicException::class);
    expect(fn () => DB::transaction(fn () => DB::table('payments')->where('id', $payment->id)->update(['amount' => 1])))->toThrow(QueryException::class, 'append-only');
    expect(fn () => DB::transaction(fn () => DB::table('payments')->where('id', $payment->id)->delete()))->toThrow(QueryException::class, 'append-only');
});

it('cancels a sale, frees the share for resale and keeps the payments on record', function () {
    $dev = erpDeveloper();
    $project = erpProject($dev, 1);
    $owner = erpUser(RoleName::DeveloperOwner, $dev);
    $share = ProjectShare::where('project_id', $project->id)->first();
    $holding = app(ShareSale::class)->sell($share, erpHolder($dev), 900_000, now(), $owner);
    app(PaymentRecorder::class)->record($holding, 200_000, now(), PaymentMethod::Cash, null, null, $owner);

    expect(fn () => app(ShareSale::class)->cancel($holding, '', $owner))->toThrow(ErpRuleViolation::class, 'reason');
    app(ShareSale::class)->cancel($holding, 'Buyer withdrew', $owner);

    expect($holding->fresh()->status)->toBe('cancelled')->and($share->fresh()->status->value)->toBe('available')->and($project->fresh()->sold_shares)->toBe(0)
        ->and(Payment::where('share_holding_id', $holding->id)->count())->toBe(1);
    expect(fn () => app(PaymentRecorder::class)->record($holding->fresh(), 1000, now(), PaymentMethod::Cash, null, null, $owner))->toThrow(ErpRuleViolation::class, 'cancelled');

    app(ShareSale::class)->sell($share->fresh(), erpHolder($dev, 'Second buyer'), 850_000, now(), $owner);
    expect(ShareHolding::where('project_share_id', $share->id)->where('status', 'active')->count())->toBe(1);
});

it('summarises the company in a few queries and agrees with the per-sale ledger', function () {
    $dev = erpDeveloper();
    $project = erpProject($dev, 3);
    $owner = erpUser(RoleName::DeveloperOwner, $dev);
    $rec = app(PaymentRecorder::class);

    $behind = app(ShareSale::class)->sell(ProjectShare::where('project_id', $project->id)->where('number', 1)->first(), erpHolder($dev, 'Behind'), 900_000, now()->subMonths(4)->startOfDay(), $owner);
    $fine = app(ShareSale::class)->sell(ProjectShare::where('project_id', $project->id)->where('number', 2)->first(), erpHolder($dev, 'Fine'), 900_000, now(), $owner);
    $rec->record($behind, 250_000, now()->subMonths(3), PaymentMethod::Cash, null, null, $owner);
    $rec->record($fine, 200_000, now(), PaymentMethod::Cash, null, null, $owner);

    $sum = app(ErpSummary::class)->forDeveloper($dev->id);
    $ledgerOverdue = (new HoldingLedger($behind->fresh()))->overdue() + (new HoldingLedger($fine->fresh()))->overdue();

    expect($sum['shares'])->toBe(3)->and($sum['sold'])->toBe(2)->and($sum['available'])->toBe(1)->and($sum['due'])->toBe(1_800_000.0)->and($sum['paid'])->toBe(450_000.0)
        ->and($sum['balance'])->toBe(1_350_000.0)->and($sum['overdue'])->toBe($ledgerOverdue)->and($sum['overdue_holdings'])->toBe(1)
        ->and($sum['collected_month'])->toBeGreaterThanOrEqual(200_000.0);
    expect(app(ErpSummary::class)->overdueHoldings($dev->id)[0]['id'])->toBe($behind->id);
});

it('keeps developer staff out of the platform admin and platform staff out of the ERP', function () {
    $owner = erpUser(RoleName::DeveloperOwner);
    $this->actingAs($owner)->get('/admin')->assertForbidden();

    $admin = userWithRole(RoleName::Admin);
    $this->actingAs($admin)->get('/erp')->assertForbidden();
    $noCompany = userWithRole(RoleName::DeveloperOwner);
    $this->actingAs($noCompany)->get('/erp')->assertForbidden();

    $this->actingAs($owner)->get('/erp')->assertOk();
});

it('requires two-factor setup for the owner and accountant but not for sales', function () {
    config(['pcbd.admin_mfa_required' => true]);

    expect(erpUser(RoleName::DeveloperOwner)->requiresMfa())->toBeTrue()->and(erpUser(RoleName::DeveloperAccountant)->requiresMfa())->toBeTrue()->and(erpUser(RoleName::DeveloperSales)->requiresMfa())->toBeFalse();
    $this->actingAs(erpUser(RoleName::DeveloperOwner))->get('/erp')->assertRedirect();
});

it('takes over hand-typed share counts without breaking the database rules', function () {
    $dev = erpDeveloper();
    $project = Project::factory()->published()->create(['developer_id' => $dev->id, 'is_share_project' => true, 'total_shares' => 90, 'sold_shares' => 90, 'registered_shares' => 60]);
    $owner = erpUser(RoleName::DeveloperOwner, $dev);

    app(ShareSale::class)->generateShares($project, 4, null, 900_000, $owner);

    $fresh = $project->fresh();
    expect($fresh->total_shares)->toBe(4)->and($fresh->sold_shares)->toBe(0)->and($fresh->registered_shares)->toBe(0);

    $share = ProjectShare::where('project_id', $project->id)->first();
    app(ShareSale::class)->sell($share, erpHolder($dev), 900_000, now(), $owner);
    expect($project->fresh()->sold_shares)->toBe(1)->and($project->fresh()->registered_shares)->toBe(0);
});
