<?php

declare(strict_types=1);

namespace App\Services\Erp;

use App\Models\ConstructionMilestone;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Permissions;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Construction milestones. The latest one sets the project's progress bar; a milestone marked public also appears on the project's timeline. */
class ConstructionLog
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function add(Project $project, string $title, int $progress, CarbonInterface $on, ?string $note, bool $publish, User $by): ConstructionMilestone
    {
        $this->authorize($by, $project->developer_id);
        $title = trim($title);
        throw_if($title === '', ErpRuleViolation::class, 'Enter a title for the milestone.');
        throw_unless($progress >= 0 && $progress <= 100, ErpRuleViolation::class, 'Progress must be between 0 and 100.');
        throw_if($on->gt(now()->endOfDay()), ErpRuleViolation::class, 'The date cannot be in the future.');

        return DB::transaction(function () use ($project, $title, $progress, $on, $note, $publish, $by) {
            $update = $publish ? ProjectUpdate::query()->create(['project_id' => $project->id, 'title' => mb_substr($title, 0, 160), 'body' => $note, 'happened_on' => $on->toDateString(), 'is_published' => true]) : null;
            $m = (new ConstructionMilestone)->forceFill([
                'developer_id' => $project->developer_id, 'project_id' => $project->id, 'title' => mb_substr($title, 0, 160), 'note' => $note,
                'progress' => $progress, 'happened_on' => $on->toDateString(), 'project_update_id' => $update?->id, 'created_by' => $by->id,
            ]);
            $m->save();
            $this->syncProgress($project->id);
            $this->audit->log('erp.milestone_added', $m, null, ['progress' => $progress, 'public' => $publish], $by->id);

            return $m;
        });
    }

    /** Removes a wrongly entered milestone, and its public timeline entry with it. */
    public function retract(ConstructionMilestone $m, User $by): void
    {
        $this->authorize($by, $m->developer_id);
        DB::transaction(function () use ($m, $by) {
            $this->audit->log('erp.milestone_retracted', $m, null, ['title' => $m->title], $by->id);
            $updateId = $m->project_update_id;
            $m->delete();
            if ($updateId) {
                ProjectUpdate::query()->whereKey($updateId)->delete();
            }
            $this->syncProgress($m->project_id);
        });
    }

    private function syncProgress(int $projectId): void
    {
        $latest = ConstructionMilestone::query()->where('project_id', $projectId)->orderByDesc('happened_on')->orderByDesc('id')->first();
        Project::query()->whereKey($projectId)->update(['construction_progress' => $latest?->progress]);
    }

    private function authorize(User $by, int $developerId): void
    {
        throw_unless($by->can(Permissions::ERP_MANAGE) && $by->developer_id === $developerId, ErpRuleViolation::class, 'You are not allowed to do that.');
    }
}
