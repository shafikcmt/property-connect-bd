<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadActivityType;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\RequestType;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\LeadStatusHistory;
use App\Models\User;
use App\Support\Permissions;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every change to a lead goes through here so the rules of docs/13 §1 and §4 hold no matter which screen,
 * command or API calls them: status transitions, assignment, activity log and follow-ups.
 */
class LeadWorkflow
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function changeStatus(Lead $lead, LeadStatus $to, User $by, ?string $reason = null, ?int $duplicateOfId = null): Lead
    {
        $from = $lead->status;
        if ($from === $to) {
            return $lead;
        }
        $manager = $by->can(Permissions::LEADS_MANAGE);
        $reason = ($t = trim((string) $reason)) !== '' ? $t : null;
        $override = false;

        if ($from->isTerminal()) {
            throw_unless($manager, LeadRuleViolation::class, 'Only a manager can reopen a closed lead.');
            throw_unless($to === LeadStatus::Contacted, LeadRuleViolation::class, 'A closed lead can only be reopened as “Contacted”.');
            throw_unless($reason, LeadRuleViolation::class, 'Give a reason for reopening this lead.');
            $override = true;
        }
        if ($to === LeadStatus::Converted) {
            // Deals do not exist yet (Phase 9), so conversion is a manager decision with a note, logged as an override.
            throw_unless($manager, LeadRuleViolation::class, 'Only a manager can mark a lead as converted.');
            throw_unless($reason !== null && mb_strlen($reason) >= 5, LeadRuleViolation::class, 'Add a note explaining the sale before marking the lead converted.');
            $override = true;
        }
        if ($to === LeadStatus::Qualified) {
            throw_unless($lead->hasNeeds(), LeadRuleViolation::class, 'Record the buyer’s budget or preferred area before marking the lead qualified.');
        }
        if ($to === LeadStatus::Interested) {
            throw_unless($lead->property_id || $lead->project_id || $lead->hasNeeds(), LeadRuleViolation::class, 'Record what the buyer is interested in (a property, project, budget or area) first.');
        }
        if (in_array($to, [LeadStatus::Lost, LeadStatus::NotInterested, LeadStatus::Invalid], true)) {
            throw_unless($reason, LeadRuleViolation::class, 'A reason is required to close a lead as '.strtolower($to->label()).'.');
        }
        if ($to === LeadStatus::Duplicate) {
            throw_unless($duplicateOfId && $duplicateOfId !== $lead->id && Lead::whereKey($duplicateOfId)->exists(), LeadRuleViolation::class, 'Choose the original lead this one duplicates.');
        }
        if (($f = $from->rank()) !== null && ($t = $to->rank()) !== null && $t < $f) {
            throw_unless($manager, LeadRuleViolation::class, 'Only a manager can move a lead backwards.');
            throw_unless($reason, LeadRuleViolation::class, 'Give a reason for moving the lead backwards.');
            $override = true;
        }

        return DB::transaction(function () use ($lead, $from, $to, $by, $reason, $duplicateOfId, $override) {
            $lead->status = $to;
            $lead->lost_reason = in_array($to, [LeadStatus::Lost, LeadStatus::NotInterested, LeadStatus::Invalid], true) ? $reason : null;
            $lead->duplicate_of_id = $to === LeadStatus::Duplicate ? $duplicateOfId : null;
            $lead->last_activity_at = now();
            if ($lead->first_contacted_at === null && $to !== LeadStatus::New && $to !== LeadStatus::Invalid && $to !== LeadStatus::Duplicate) {
                $lead->first_contacted_at = now();
            }
            $lead->save();

            LeadStatusHistory::create(['lead_id' => $lead->id, 'user_id' => $by->id, 'from_status' => $from, 'to_status' => $to, 'reason' => $reason, 'is_override' => $override]);
            $this->record($lead, LeadActivityType::StatusChange, "{$from->label()} → {$to->label()}".($reason ? ": {$reason}" : ''), $by);
            $this->audit->log($override ? 'lead.status_override' : 'lead.status_changed', $lead, ['status' => $from->value], ['status' => $to->value], $by->id);

            return $lead;
        });
    }

    public function assign(Lead $lead, ?User $assignee, User $by): Lead
    {
        throw_if($assignee && ! ($assignee->can(Permissions::LEADS_WORK) || $assignee->can(Permissions::LEADS_MANAGE)), LeadRuleViolation::class, 'That person cannot work on leads.');
        if ($lead->assigned_to === $assignee?->id) {
            return $lead;
        }

        DB::transaction(function () use ($lead, $assignee, $by) {
            $old = $lead->assigned_to;
            $lead->forceFill(['assigned_to' => $assignee?->id, 'assigned_at' => $assignee ? now() : null, 'last_activity_at' => now()])->save();
            $this->record($lead, LeadActivityType::Assignment, $assignee ? 'Assigned to '.$assignee->name : 'Unassigned', $by);
            $this->audit->log('lead.assigned', $lead, ['assigned_to' => $old], ['assigned_to' => $assignee?->id], $by->id);
        });

        if ($assignee && $assignee->id !== $by->id) {
            Notification::make()->title('A lead was assigned to you')->body("{$lead->name} · {$lead->request_type->label()}")->warning()->sendToDatabase($assignee);
        }

        return $lead;
    }

    public function logActivity(Lead $lead, LeadActivityType $type, string $body, User $by): LeadActivity
    {
        throw_if(in_array($type, [LeadActivityType::StatusChange, LeadActivityType::Assignment, LeadActivityType::FollowUp, LeadActivityType::Inquiry], true), LeadRuleViolation::class, 'That activity type is recorded automatically.');
        $body = trim($body);
        throw_if($body === '', LeadRuleViolation::class, 'Write a short note about what happened.');

        return DB::transaction(function () use ($lead, $type, $body, $by) {
            $activity = $this->record($lead, $type, $body, $by);
            if ($type->isContact() && $lead->status === LeadStatus::New) {
                $this->changeStatus($lead, LeadStatus::Contacted, $by);
            } elseif ($type->isContact() && $lead->first_contacted_at === null) {
                $lead->forceFill(['first_contacted_at' => now()])->save();
            }

            return $activity;
        });
    }

    public function scheduleFollowUp(Lead $lead, CarbonInterface $dueAt, ?string $note, User $by, ?User $owner = null): LeadFollowUp
    {
        throw_if($lead->status->isTerminal(), LeadRuleViolation::class, 'A closed lead cannot have follow-ups.');
        throw_if($dueAt->lt(now()->subMinutes(5)), LeadRuleViolation::class, 'Pick a follow-up time in the future.');

        return DB::transaction(function () use ($lead, $dueAt, $note, $by, $owner) {
            $followUp = LeadFollowUp::create(['lead_id' => $lead->id, 'user_id' => ($owner ?? $lead->loadMissing('assignee')->assignee ?? $by)->id, 'due_at' => $dueAt, 'note' => $note ? mb_substr(trim($note), 0, 255) : null]);
            $this->record($lead, LeadActivityType::FollowUp, 'Follow-up scheduled for '.$dueAt->format('d M Y H:i').($note ? ": {$note}" : ''), $by);
            $this->syncNextFollowUp($lead);

            return $followUp;
        });
    }

    public function completeFollowUp(LeadFollowUp $followUp, string $outcome, User $by): LeadFollowUp
    {
        $outcome = trim($outcome);
        throw_if($followUp->completed_at !== null, LeadRuleViolation::class, 'This follow-up is already completed.');
        throw_if($outcome === '', LeadRuleViolation::class, 'Write the outcome of the follow-up.');

        return DB::transaction(function () use ($followUp, $outcome, $by) {
            $followUp->forceFill(['completed_at' => now(), 'completed_by' => $by->id, 'outcome' => $outcome])->save();
            $lead = $followUp->lead;
            $this->record($lead, LeadActivityType::FollowUp, "Follow-up done: {$outcome}", $by);
            $this->syncNextFollowUp($lead);

            return $followUp;
        });
    }

    /**
     * The same buyer asked again while their lead is still open: record it on that lead instead of creating a duplicate
     * (docs/13 §3). A repeat means a keen buyer, so normal priority rises to high, and the assignee (or, if nobody has
     * the lead yet, the managers) is alerted.
     */
    public function appendInquiry(Lead $lead, RequestType $type, ?string $subject, ?string $message): Lead
    {
        DB::transaction(function () use ($lead, $type, $subject, $message) {
            $message = trim((string) $message);
            $body = 'New '.strtolower($type->label()).' request'.($subject ? " about {$subject}" : '').($message !== '' ? ': '.mb_substr($message, 0, 500) : '');
            $lead->forceFill(['inquiry_count' => $lead->inquiry_count + 1, 'last_activity_at' => now()]);
            if ($lead->priority === LeadPriority::Normal) {
                $lead->priority = LeadPriority::High;
            }
            $lead->save();
            LeadActivity::create(['lead_id' => $lead->id, 'user_id' => null, 'type' => LeadActivityType::Inquiry, 'body' => $body]);
        });

        $this->notifyTeam($lead, 'Repeat inquiry', $lead->name.' asked again ('.$lead->inquiry_count.' inquiries)', $lead->loadMissing('assignee')->assignee ? collect([$lead->assignee]) : $this->managers());

        return $lead;
    }

    /** People who assign leads, plus super admins. @return \Illuminate\Support\Collection<int, User> */
    public function managers(): Collection
    {
        return User::query()->where('status', 'active')
            ->where(fn ($q) => $q->permission(Permissions::LEADS_ASSIGN)->orWhereHas('roles', fn ($r) => $r->where('name', 'super-admin')))->get();
    }

    /** In-app (bell) alert with a link to the lead. Never includes contact details. */
    public function notifyTeam(Lead $lead, string $title, string $body, Collection $recipients): void
    {
        if ($recipients->isEmpty()) {
            return;
        }
        Notification::make()->title($title)->body($body)->warning()
            ->actions([Action::make('open')->label('Open')->url('/admin/leads/'.$lead->id.'/edit')])->sendToDatabase($recipients);
    }

    /**
     * Saves what the buyer is looking for. Only these fields change; the audit entry holds ids and numbers, no contact data.
     *
     * @param  array<string, mixed>  $data  budget_min, budget_max, preferred_location_id, property_type_id, purpose, buy_timeline
     */
    public function updateNeeds(Lead $lead, array $data, User $by): Lead
    {
        $fields = ['budget_min', 'budget_max', 'preferred_location_id', 'property_type_id', 'purpose', 'buy_timeline'];
        $clean = [];
        foreach ($fields as $f) {
            $clean[$f] = ($data[$f] ?? null) === '' ? null : ($data[$f] ?? null);
        }
        throw_if($clean['budget_min'] !== null && $clean['budget_max'] !== null && (float) $clean['budget_min'] > (float) $clean['budget_max'], LeadRuleViolation::class, 'The minimum budget cannot be higher than the maximum.');
        throw_if($clean['purpose'] !== null && ! in_array($clean['purpose'], ['sale', 'rent'], true), LeadRuleViolation::class, 'Purpose must be sale or rent.');

        $old = $lead->only($fields);
        $lead->forceFill($clean)->save();
        if ($old !== $lead->only($fields)) {
            $this->record($lead, LeadActivityType::Note, 'Buyer needs updated', $by);
            $this->audit->log('lead.needs_updated', $lead, array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $old), array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $lead->only($fields)), $by->id);
        }

        return $lead;
    }

    /** Keeps leads.next_follow_up_at equal to the earliest open follow-up. */
    public function syncNextFollowUp(Lead $lead): void
    {
        $lead->forceFill(['next_follow_up_at' => $lead->followUps()->open()->min('due_at')])->save();
    }

    private function record(Lead $lead, LeadActivityType $type, string $body, ?User $by): LeadActivity
    {
        $lead->forceFill(['last_activity_at' => now()])->save();

        return LeadActivity::create(['lead_id' => $lead->id, 'user_id' => $by?->id, 'type' => $type, 'body' => $body]);
    }
}
