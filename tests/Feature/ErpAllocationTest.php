<?php

use App\Enums\PaymentMethod;
use App\Enums\RoleName;
use App\Models\FlatAllocation;
use App\Models\ProjectUpdate;
use App\Services\Erp\ConstructionLog;
use App\Services\Erp\ErpRuleViolation;
use App\Services\Erp\FlatAllocator;
use App\Services\Erp\PaymentRecorder;
use App\Services\Erp\ShareSale;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->dev = erpDeveloper();
    $this->owner = erpUser(RoleName::DeveloperOwner, $this->dev);
    $this->project = erpProject($this->dev, 3);
    $shares = $this->project->shares()->orderBy('number')->get();
    $this->h1 = app(ShareSale::class)->sell($shares[0], erpHolder($this->dev, 'A'), 900000, now()->subMonths(10), $this->owner);
    $this->h2 = app(ShareSale::class)->sell($shares[1], erpHolder($this->dev, 'B'), 900000, now()->subMonths(10), $this->owner);
});

it('allocates a flat once and keeps one flat for one shareholder', function () {
    $a = app(FlatAllocator::class);
    $a->allocate($this->h1, 'A', '3', '3B', 'lottery', now(), $this->owner);
    expect(fn () => $a->allocate($this->h2, 'A', '3', '3B', 'choice', now(), $this->owner))->toThrow(ErpRuleViolation::class, 'already allocated');
    expect(fn () => $a->allocate($this->h1, 'A', '4', '4A', 'choice', now(), $this->owner))->toThrow(ErpRuleViolation::class, 'already has a flat');
    expect(fn () => $a->allocate($this->h2, 'A', '', '4A', 'choice', now(), $this->owner))->toThrow(ErpRuleViolation::class);
});

it('hands over only with a full checklist and nothing owed', function () {
    $a = app(FlatAllocator::class);
    $alloc = $a->allocate($this->h1, 'A', '3', '3B', 'assigned', now()->subDays(2), $this->owner);
    $all = array_keys(FlatAllocation::CHECKLIST);

    expect(fn () => $a->handOver($alloc, now(), $this->owner))->toThrow(ErpRuleViolation::class, 'checklist');
    $a->saveChecklist($alloc, $all, $this->owner);
    expect(fn () => $a->handOver($alloc->fresh(), now(), $this->owner))->toThrow(ErpRuleViolation::class, 'still owes');

    app(PaymentRecorder::class)->record($this->h1->fresh(), 900000, now(), PaymentMethod::Cash, null, null, $this->owner);
    $a->handOver($alloc->fresh(), now(), $this->owner);
    expect($alloc->fresh()->status)->toBe('handed_over');
    expect(fn () => $a->release($alloc->fresh(), $this->owner))->toThrow(ErpRuleViolation::class, 'handed-over');
});

it('lets the latest milestone set progress and publishes to the timeline', function () {
    $log = app(ConstructionLog::class);
    $log->add($this->project, 'Foundation done', 20, now()->subDays(30), null, true, $this->owner);
    $m = $log->add($this->project, 'Roof cast', 55, now()->subDays(5), 'Floor 5', false, $this->owner);
    expect($this->project->fresh()->construction_progress)->toBe(55);
    expect(ProjectUpdate::where('project_id', $this->project->id)->where('title', 'Foundation done')->where('is_published', true)->exists())->toBeTrue();
    expect(ProjectUpdate::where('title', 'Roof cast')->exists())->toBeFalse();

    $log->retract($m, $this->owner);
    expect($this->project->fresh()->construction_progress)->toBe(20);
    expect(fn () => $log->add($this->project, 'X', 101, now(), null, false, $this->owner))->toThrow(ErpRuleViolation::class);
    expect(fn () => $log->add($this->project, 'X', 50, now(), null, false, erpUser(RoleName::DeveloperOwner)))->toThrow(ErpRuleViolation::class, 'not allowed');
});
