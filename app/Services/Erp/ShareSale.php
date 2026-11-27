<?php

declare(strict_types=1);

namespace App\Services\Erp;

use App\Models\InstalmentPlanItem;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\Shareholder;
use App\Models\ShareHolding;
use App\Models\ShareInstalment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Permissions;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Creating shares, selling them, and cancelling a sale (docs/27, ADR-0019). Every rule lives here, not in the screens. */
class ShareSale
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** Adds `$count` numbered shares to a project, continuing after the highest existing number. */
    public function generateShares(Project $project, int $count, ?float $areaEach, ?float $listPrice, User $by): int
    {
        $this->authorize($by, Permissions::ERP_MANAGE, $project->developer_id);
        throw_unless($count >= 1 && $count <= 500, ErpRuleViolation::class, 'Create between 1 and 500 shares at a time.');
        throw_if($listPrice !== null && $listPrice <= 0, ErpRuleViolation::class, 'The price must be above zero.');

        DB::transaction(function () use ($project, $count, $areaEach, $listPrice, $by) {
            $next = (int) ProjectShare::query()->where('project_id', $project->id)->max('number') + 1;
            for ($i = 0; $i < $count; $i++) {
                (new ProjectShare)->forceFill(['developer_id' => $project->developer_id, 'project_id' => $project->id, 'number' => $next + $i, 'area_shatangsho' => $areaEach, 'list_price' => $listPrice])->save();
            }
            $this->syncProject($project);
            $this->audit->log('erp.shares_created', $project, null, ['count' => $count], $by->id);
        });

        return $count;
    }

    public function sell(ProjectShare $share, Shareholder $holder, float $price, CarbonInterface $soldOn, User $by): ShareHolding
    {
        $this->authorize($by, Permissions::ERP_SELL, $share->developer_id);
        throw_unless($holder->developer_id === $share->developer_id, ErpRuleViolation::class, 'That shareholder belongs to another company.');
        throw_unless($price > 0, ErpRuleViolation::class, 'The price must be above zero.');
        throw_if($soldOn->gt(now()->endOfDay()), ErpRuleViolation::class, 'The sale date cannot be in the future.');

        return DB::transaction(function () use ($share, $holder, $price, $soldOn, $by) {
            $share = ProjectShare::query()->whereKey($share->id)->lockForUpdate()->firstOrFail();
            throw_unless($share->status->value === 'available', ErpRuleViolation::class, 'This share is not available.');

            $holding = (new ShareHolding)->forceFill([
                'developer_id' => $share->developer_id, 'project_share_id' => $share->id, 'shareholder_id' => $holder->id,
                'price' => $price, 'sold_on' => $soldOn->toDateString(), 'status' => 'active', 'created_by' => $by->id,
            ]);
            $holding->save();
            $share->forceFill(['status' => 'sold'])->save();
            $this->generateInstalments($holding, $share->project_id);
            $this->syncProject($share->project);
            $this->audit->log('erp.share_sold', $holding, null, ['share' => $share->number, 'price' => $price], $by->id);

            return $holding;
        });
    }

    public function cancel(ShareHolding $holding, string $reason, User $by): ShareHolding
    {
        $this->authorize($by, Permissions::ERP_MANAGE, $holding->developer_id);
        throw_unless($holding->isActive(), ErpRuleViolation::class, 'This sale is already cancelled.');
        $reason = trim($reason);
        throw_if($reason === '', ErpRuleViolation::class, 'Give a reason for cancelling the sale.');

        return DB::transaction(function () use ($holding, $reason, $by) {
            $holding->forceFill(['status' => 'cancelled', 'cancelled_at' => now(), 'cancel_reason' => mb_substr($reason, 0, 500)])->save();
            $share = $holding->share;
            $share->forceFill(['status' => 'available'])->save();
            $this->syncProject($share->project);
            // Money already received stays on record; settling it with the buyer is handled outside the system.
            $this->audit->log('erp.sale_cancelled', $holding, null, ['reason' => $reason], $by->id);

            return $holding;
        });
    }

    /** Copies the project's instalment template onto a sale: due dates run from the sale date. */
    public function generateInstalments(ShareHolding $holding, int $projectId): void
    {
        $order = 0;
        foreach (InstalmentPlanItem::query()->where('project_id', $projectId)->orderBy('sort_order')->orderBy('id')->get() as $item) {
            for ($i = 0; $i < $item->count; $i++) {
                (new ShareInstalment)->forceFill([
                    'developer_id' => $holding->developer_id, 'share_holding_id' => $holding->id, 'sort_order' => $order++,
                    'label' => $item->count > 1 ? sprintf('%s (%d/%d)', $item->label, $i + 1, $item->count) : $item->label,
                    'due_on' => $holding->sold_on->copy()->addMonthsNoOverflow($item->start_after_months + $i * $item->interval_months)->toDateString(),
                    'amount' => $item->amount,
                ])->save();
            }
        }
    }

    /** Keeps the public project page in step: total and sold counts come from the register, not from typing. */
    public function syncProject(Project $project): void
    {
        $total = ProjectShare::query()->where('project_id', $project->id)->count();
        $sold = ProjectShare::query()->where('project_id', $project->id)->where('status', 'sold')->count();
        if ($total > 0) {
            // A hand-typed "registered deeds" figure may be higher than the register's sold count; it is clamped so the
            // database rule registered <= sold <= total holds. Deed tracking will take over this number.
            Project::query()->whereKey($project->id)->update([
                'total_shares' => $total, 'sold_shares' => $sold, 'is_share_project' => true,
                'registered_shares' => DB::raw('case when registered_shares is null then null else least(registered_shares, '.$sold.') end'),
            ]);
        }
    }

    private function authorize(User $by, string $permission, int $developerId): void
    {
        throw_unless($by->can($permission) && $by->developer_id === $developerId, ErpRuleViolation::class, 'You are not allowed to do that.');
    }
}
