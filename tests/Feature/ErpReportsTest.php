<?php

use App\Enums\PaymentMethod;
use App\Enums\RoleName;
use App\Filament\Erp\Pages\ErpReports;
use App\Services\Erp\CsvExport;
use App\Services\Erp\ErpReport;
use App\Services\Erp\PaymentRecorder;
use App\Services\Erp\ShareSale;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->dev = erpDeveloper();
    $this->owner = erpUser(RoleName::DeveloperOwner, $this->dev);
    $this->project = erpProject($this->dev, 3);
    $shares = $this->project->shares()->orderBy('number')->get();
    // Sold 100 days ago: booking (200k) due then, monthly 100k instalments at +1..+7 months → 3 are past due.
    $this->old = app(ShareSale::class)->sell($shares[0], erpHolder($this->dev, 'Old Payer'), 900000, now()->subDays(100), $this->owner);
    $this->ok = app(ShareSale::class)->sell($shares[1], erpHolder($this->dev, 'Good Payer'), 900000, now()->subDays(100), $this->owner);
});

it('buckets overdue money by age and keeps good payers out', function () {
    app(PaymentRecorder::class)->record($this->ok, 500000, now()->subDays(1), PaymentMethod::Cash, null, null, $this->owner);
    $r = app(ErpReport::class)->build($this->dev->id, now()->subDays(30), now());

    expect($r['rows'])->toHaveCount(1)->and($r['rows'][0]['shareholder'])->toBe('Old Payer');
    // booking due at day 0 (100 days ago) 200k, +1 month ≈ 70 days ago 100k, +2 months ≈ 40 days ago 100k, +3 months ≈ 10 days ago 100k
    expect($r['ageing_total'])->toBe($r['rows'][0]['overdue'])->and($r['ageing']['Over 90 days'])->toBe(200000.0)->and($r['ageing']['61–90 days'])->toBe(100000.0)->and($r['ageing']['31–60 days'])->toBe(100000.0)->and($r['ageing']['1–30 days'])->toBe(100000.0);
    expect($r['collected'])->toBe(500000.0);
});

it('subtracts reversals from collections', function () {
    $rec = app(PaymentRecorder::class);
    $p = $rec->record($this->ok, 100000, now()->subDays(2), PaymentMethod::Cash, null, null, $this->owner);
    $rec->reverse($p, 'wrong share', $this->owner);
    $r = app(ErpReport::class)->build($this->dev->id, now()->subDays(30), now());
    expect($r['collected'])->toBe(0.0);
});

it('defuses spreadsheet formulas in exports', function () {
    expect(CsvExport::cell('=HYPERLINK("x")'))->toBe("'=HYPERLINK(\"x\")")->and(CsvExport::cell('-5'))->toBe('-5')->and(CsvExport::cell('Rina'))->toBe('Rina');
});

it('serves CSV only to staff who can see payments, for their own company', function () {
    app(PaymentRecorder::class)->record($this->ok, 100000, now()->subDays(2), PaymentMethod::Bkash, 'TX1', null, $this->owner);

    $res = $this->actingAs($this->owner)->get(route('erp.export', ['report' => 'collections']));
    $res->assertOk();
    expect($res->streamedContent())->toContain('Good Payer')->toContain('TX1')->not->toContain('1234 5678');
    $this->get(route('erp.export', ['report' => 'overdue']))->assertOk();
    $this->get(route('erp.export', ['report' => 'nope']))->assertNotFound();

    $this->actingAs(erpUser(RoleName::DeveloperSales, $this->dev))->get(route('erp.export', ['report' => 'collections']))->assertForbidden();
    expect($this->actingAs(erpUser(RoleName::DeveloperOwner))->get(route('erp.export', ['report' => 'collections']))->streamedContent())->not->toContain('Good Payer');
});

it('shows the reports page to the owner and hides it from sales', function () {
    $this->actingAs($this->owner);
    erpPanel();
    Livewire::test(ErpReports::class)->assertSuccessful()->assertSee('Old Payer');
    expect(ErpReports::canAccess())->toBeTrue();
    $this->actingAs(erpUser(RoleName::DeveloperSales, $this->dev));
    expect(ErpReports::canAccess())->toBeFalse();
});
