<?php

declare(strict_types=1);

namespace App\Services\Erp;

use App\Models\FlatAllocation;
use App\Models\ShareHolding;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Permissions;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Flat allocation and handover (docs/27, ADR-0019). A flat is handed over only when every checklist item is ticked and nothing is owed. */
class FlatAllocator
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function allocate(ShareHolding $holding, string $building, string $floor, string $flatNo, string $method, CarbonInterface $on, User $by): FlatAllocation
    {
        $this->authorize($by, $holding->developer_id);
        throw_unless($holding->isActive(), ErpRuleViolation::class, 'This sale is cancelled.');
        throw_unless(in_array($method, ['lottery', 'choice', 'assigned'], true), ErpRuleViolation::class, 'Choose how the flat was decided.');
        [$building, $floor, $flatNo] = [trim($building), trim($floor), trim($flatNo)];
        throw_if($floor === '' || $flatNo === '', ErpRuleViolation::class, 'Enter the floor and the flat number.');
        throw_if($on->gt(now()->endOfDay()), ErpRuleViolation::class, 'The allocation date cannot be in the future.');
        $holding->loadMissing('share');
        $projectId = $holding->share->project_id;

        return DB::transaction(function () use ($holding, $projectId, $building, $floor, $flatNo, $method, $on, $by) {
            throw_if(FlatAllocation::query()->where('share_holding_id', $holding->id)->exists(), ErpRuleViolation::class, 'This shareholder already has a flat.');
            throw_if(FlatAllocation::query()->where(['project_id' => $projectId, 'building' => $building, 'floor' => $floor, 'flat_no' => $flatNo])->exists(), ErpRuleViolation::class, 'That flat is already allocated.');

            $a = (new FlatAllocation)->forceFill([
                'developer_id' => $holding->developer_id, 'project_id' => $projectId, 'share_holding_id' => $holding->id,
                'building' => mb_substr($building, 0, 40), 'floor' => mb_substr($floor, 0, 20), 'flat_no' => mb_substr($flatNo, 0, 20),
                'method' => $method, 'allocated_on' => $on->toDateString(), 'created_by' => $by->id, 'status' => 'allocated',
            ]);
            $a->save();
            $this->audit->log('erp.flat_allocated', $a, null, ['flat' => $a->flatLabel(), 'method' => $method], $by->id);

            return $a;
        });
    }

    public function release(FlatAllocation $a, User $by): void
    {
        $this->authorize($by, $a->developer_id);
        throw_if($a->status === 'handed_over', ErpRuleViolation::class, 'A handed-over flat cannot be released.');
        $this->audit->log('erp.flat_released', $a, null, ['flat' => $a->flatLabel()], $by->id);
        $a->delete();
    }

    /** @param list<string> $ticked keys of FlatAllocation::CHECKLIST that are done */
    public function saveChecklist(FlatAllocation $a, array $ticked, User $by): FlatAllocation
    {
        $this->authorize($by, $a->developer_id);
        throw_if($a->status === 'handed_over', ErpRuleViolation::class, 'This flat is already handed over.');
        $a->forceFill(['checklist' => array_values(array_intersect(array_keys(FlatAllocation::CHECKLIST), $ticked))])->save();
        $this->audit->log('erp.handover_checklist', $a, null, ['done' => $a->checklist], $by->id);

        return $a;
    }

    public function handOver(FlatAllocation $a, CarbonInterface $on, User $by): FlatAllocation
    {
        $this->authorize($by, $a->developer_id);
        throw_if($a->status === 'handed_over', ErpRuleViolation::class, 'This flat is already handed over.');
        throw_if(count(array_diff(array_keys(FlatAllocation::CHECKLIST), (array) $a->checklist)) > 0, ErpRuleViolation::class, 'Tick every handover checklist item first.');
        throw_if($on->gt(now()->endOfDay()) || $on->lt($a->allocated_on), ErpRuleViolation::class, 'The handover date must be after the allocation date and not in the future.');
        $holding = $a->holding;
        throw_unless($holding->isActive(), ErpRuleViolation::class, 'This sale is cancelled.');
        // Dues are checked against the ledger, not trusted from the checklist tick.
        throw_if((new HoldingLedger($holding))->balance() > 0, ErpRuleViolation::class, 'The shareholder still owes money on this share.');

        $a->forceFill(['status' => 'handed_over', 'handed_over_on' => $on->toDateString()])->save();
        $this->audit->log('erp.flat_handed_over', $a, null, ['flat' => $a->flatLabel()], $by->id);

        return $a;
    }

    private function authorize(User $by, int $developerId): void
    {
        throw_unless($by->can(Permissions::ERP_MANAGE) && $by->developer_id === $developerId, ErpRuleViolation::class, 'You are not allowed to do that.');
    }
}
