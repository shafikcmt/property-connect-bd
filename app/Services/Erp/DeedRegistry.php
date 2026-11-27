<?php

declare(strict_types=1);

namespace App\Services\Erp;

use App\Models\DeedBatch;
use App\Models\DeedEntry;
use App\Models\Project;
use App\Models\ShareHolding;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Permissions;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Deed batches and mutation tracking (docs/27, ADR-0019). Only a registered deed counts towards the public "registered" figure. */
class DeedRegistry
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function createBatch(Project $project, ?string $notes, User $by): DeedBatch
    {
        $this->authorize($by, $project->developer_id);

        $batch = (new DeedBatch)->forceFill(['developer_id' => $project->developer_id, 'project_id' => $project->id, 'notes' => $notes, 'created_by' => $by->id, 'status' => 'draft']);
        $batch->save();
        $this->audit->log('erp.deed_batch_created', $batch, null, ['project' => $project->id], $by->id);

        return $batch;
    }

    /** @param list<int> $holdingIds */
    public function addHoldings(DeedBatch $batch, array $holdingIds, User $by): int
    {
        $this->authorize($by, $batch->developer_id);
        throw_if($batch->isRegistered(), ErpRuleViolation::class, 'This deed is already registered; its list cannot change.');
        throw_if($holdingIds === [], ErpRuleViolation::class, 'Choose at least one shareholder.');

        return DB::transaction(function () use ($batch, $holdingIds, $by) {
            $holdings = ShareHolding::query()->with('share')->whereIn('id', $holdingIds)->get();
            throw_unless($holdings->count() === count(array_unique($holdingIds)), ErpRuleViolation::class, 'One of the chosen sales was not found.');
            foreach ($holdings as $h) {
                throw_unless($h->developer_id === $batch->developer_id && $h->share->project_id === $batch->project_id, ErpRuleViolation::class, 'A chosen share belongs to a different project.');
                throw_unless($h->isActive(), ErpRuleViolation::class, 'Share '.$h->share->number.' has a cancelled sale.');
                throw_if(DeedEntry::query()->where('share_holding_id', $h->id)->exists(), ErpRuleViolation::class, 'Share '.$h->share->number.' is already on a deed.');
                (new DeedEntry)->forceFill(['developer_id' => $batch->developer_id, 'deed_batch_id' => $batch->id, 'share_holding_id' => $h->id])->save();
            }
            $this->audit->log('erp.deed_holdings_added', $batch, null, ['count' => $holdings->count()], $by->id);

            return $holdings->count();
        });
    }

    public function removeEntry(DeedEntry $entry, User $by): void
    {
        $this->authorize($by, $entry->developer_id);
        throw_if($entry->batch->isRegistered(), ErpRuleViolation::class, 'This deed is already registered; its list cannot change.');
        $this->audit->log('erp.deed_entry_removed', $entry->batch, null, ['holding' => $entry->share_holding_id], $by->id);
        $entry->delete();
    }

    public function register(DeedBatch $batch, string $deedNumber, string $office, CarbonInterface $registeredOn, User $by): DeedBatch
    {
        $this->authorize($by, $batch->developer_id);
        throw_if($batch->isRegistered(), ErpRuleViolation::class, 'This deed is already registered.');
        $deedNumber = trim($deedNumber);
        $office = trim($office);
        throw_if($deedNumber === '' || $office === '', ErpRuleViolation::class, 'Enter the deed number and the registry office.');
        throw_if($registeredOn->gt(now()->endOfDay()), ErpRuleViolation::class, 'The registration date cannot be in the future.');
        throw_unless($batch->entries()->exists(), ErpRuleViolation::class, 'Add at least one shareholder before registering.');

        return DB::transaction(function () use ($batch, $deedNumber, $office, $registeredOn, $by) {
            $batch->forceFill(['status' => 'registered', 'deed_number' => mb_substr($deedNumber, 0, 60), 'registry_office' => mb_substr($office, 0, 150), 'registered_on' => $registeredOn->toDateString()])->save();
            $this->syncProject($batch->project_id);
            $this->audit->log('erp.deed_registered', $batch, null, ['deed' => $deedNumber, 'shares' => $batch->entries()->count()], $by->id);

            return $batch;
        });
    }

    /** Mutation only moves forward: none → applied → completed. */
    public function updateMutation(DeedEntry $entry, string $status, ?CarbonInterface $appliedOn, ?CarbonInterface $completedOn, ?string $reference, User $by): DeedEntry
    {
        $this->authorize($by, $entry->developer_id);
        throw_unless($entry->batch->isRegistered(), ErpRuleViolation::class, 'Register the deed before tracking mutation.');
        $order = ['none' => 0, 'applied' => 1, 'completed' => 2];
        throw_unless(isset($order[$status]) && $order[$status] > $order[$entry->mutation_status], ErpRuleViolation::class, 'Mutation can only move forward.');
        throw_if($appliedOn === null, ErpRuleViolation::class, 'Enter the date mutation was applied for.');
        throw_if($appliedOn->lt($entry->batch->registered_on) || $appliedOn->gt(now()->endOfDay()), ErpRuleViolation::class, 'The applied date must be after the deed date and not in the future.');
        if ($status === 'completed') {
            throw_if($completedOn === null || $completedOn->lt($appliedOn) || $completedOn->gt(now()->endOfDay()), ErpRuleViolation::class, 'Enter a completion date on or after the applied date, not in the future.');
        }

        $entry->forceFill([
            'mutation_status' => $status, 'mutation_applied_on' => $appliedOn->toDateString(),
            'mutation_completed_on' => $status === 'completed' ? $completedOn->toDateString() : null,
            'mutation_reference' => $reference !== null ? mb_substr(trim($reference), 0, 100) : null,
        ])->save();
        $this->audit->log('erp.mutation_updated', $entry->batch, null, ['holding' => $entry->share_holding_id, 'status' => $status], $by->id);

        return $entry;
    }

    /** The public project page shows how many shareholders hold a registered deed; it is counted here, never typed. */
    public function syncProject(int $projectId): void
    {
        $count = DeedEntry::query()->whereHas('batch', fn ($q) => $q->where('project_id', $projectId)->where('status', 'registered'))->count();
        Project::query()->whereKey($projectId)->update(['registered_shares' => $count]);
    }

    private function authorize(User $by, int $developerId): void
    {
        throw_unless($by->can(Permissions::ERP_MANAGE) && $by->developer_id === $developerId, ErpRuleViolation::class, 'You are not allowed to do that.');
    }
}
