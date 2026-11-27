<?php

use App\Enums\RoleName;
use App\Models\DeedEntry;
use App\Services\Erp\DeedRegistry;
use App\Services\Erp\ErpRuleViolation;
use App\Services\Erp\ShareSale;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->dev = erpDeveloper();
    $this->owner = erpUser(RoleName::DeveloperOwner, $this->dev);
    $this->project = erpProject($this->dev, 4);
    $this->holdings = collect($this->project->shares()->orderBy('number')->get())->take(3)->map(
        fn ($s) => app(ShareSale::class)->sell($s, erpHolder($this->dev, 'Holder '.$s->number), 900000, now()->subDay(), $this->owner)
    );
});

it('registers a deed and feeds the public registered count', function () {
    $reg = app(DeedRegistry::class);
    $batch = $reg->createBatch($this->project, null, $this->owner);
    $reg->addHoldings($batch, $this->holdings->take(2)->pluck('id')->all(), $this->owner);
    expect($this->project->fresh()->registered_shares)->not->toBe(2);

    $reg->register($batch, 'D-1234', 'Savar Sub-Registry', now()->subDay(), $this->owner);

    expect($batch->fresh()->status)->toBe('registered')->and($this->project->fresh()->registered_shares)->toBe(2);
});

it('refuses to register an empty deed, a future date or a missing number', function () {
    $reg = app(DeedRegistry::class);
    $batch = $reg->createBatch($this->project, null, $this->owner);
    expect(fn () => $reg->register($batch, 'D-1', 'Office', now(), $this->owner))->toThrow(ErpRuleViolation::class, 'at least one');
    $reg->addHoldings($batch, [$this->holdings[0]->id], $this->owner);
    expect(fn () => $reg->register($batch, 'D-1', 'Office', now()->addDay(), $this->owner))->toThrow(ErpRuleViolation::class, 'future');
    expect(fn () => $reg->register($batch, ' ', 'Office', now(), $this->owner))->toThrow(ErpRuleViolation::class);
});

it('keeps a share on only one deed and locks a registered deed', function () {
    $reg = app(DeedRegistry::class);
    $a = $reg->createBatch($this->project, null, $this->owner);
    $reg->addHoldings($a, [$this->holdings[0]->id], $this->owner);
    $b = $reg->createBatch($this->project, null, $this->owner);
    expect(fn () => $reg->addHoldings($b, [$this->holdings[0]->id], $this->owner))->toThrow(ErpRuleViolation::class, 'already on a deed');

    $reg->register($a, 'D-1', 'Office', now(), $this->owner);
    expect(fn () => $reg->addHoldings($a, [$this->holdings[1]->id], $this->owner))->toThrow(ErpRuleViolation::class, 'already registered');
    expect(fn () => $reg->removeEntry(DeedEntry::first(), $this->owner))->toThrow(ErpRuleViolation::class);
});

it('moves mutation forward only and after registration', function () {
    $reg = app(DeedRegistry::class);
    $batch = $reg->createBatch($this->project, null, $this->owner);
    $reg->addHoldings($batch, [$this->holdings[0]->id], $this->owner);
    $entry = DeedEntry::first();
    expect(fn () => $reg->updateMutation($entry, 'applied', now(), null, null, $this->owner))->toThrow(ErpRuleViolation::class, 'Register the deed');

    $reg->register($batch, 'D-1', 'Office', now()->subDays(5), $this->owner);
    $reg->updateMutation($entry->fresh(), 'applied', now()->subDays(3), null, 'MC-9', $this->owner);
    $reg->updateMutation($entry->fresh(), 'completed', now()->subDays(3), now()->subDay(), 'MC-9', $this->owner);
    expect($entry->fresh()->mutation_status)->toBe('completed');
    expect(fn () => $reg->updateMutation($entry->fresh(), 'applied', now()->subDays(3), null, null, $this->owner))->toThrow(ErpRuleViolation::class, 'forward');
});

it('stops a cancelled sale or another company from touching a deed', function () {
    $reg = app(DeedRegistry::class);
    $batch = $reg->createBatch($this->project, null, $this->owner);
    $reg->addHoldings($batch, [$this->holdings[0]->id], $this->owner);
    expect(fn () => app(ShareSale::class)->cancel($this->holdings[0]->fresh(), 'changed mind', $this->owner))->toThrow(ErpRuleViolation::class, 'on a deed');

    $other = erpUser(RoleName::DeveloperOwner);
    expect(fn () => $reg->register($batch, 'D-1', 'Office', now(), $other))->toThrow(ErpRuleViolation::class, 'not allowed');
    $sales = erpUser(RoleName::DeveloperSales, $this->dev);
    expect(fn () => $reg->createBatch($this->project, null, $sales))->toThrow(ErpRuleViolation::class, 'not allowed');
});
