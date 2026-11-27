<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Enums\SiteVisitStatus;
use App\Models\SiteVisit;
use App\Models\User;
use App\Support\Permissions;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Site-visit rules (docs/13 §5): requested → confirmed → completed, with reschedule, cancel and no-show. Confirming needs
 * a future time, an agent and a meeting point; completing needs feedback and a visit that has actually started.
 * Each step is mirrored on the lead's timeline and nudges the lead pipeline and follow-ups.
 */
class SiteVisitWorkflow
{
    public function __construct(private readonly LeadWorkflow $leads, private readonly AuditLogger $audit) {}

    public function confirm(SiteVisit $visit, CarbonInterface $at, User $agent, string $meetingPoint, User $by): SiteVisit
    {
        $visit->loadMissing('lead.assignee');
        throw_unless(in_array($visit->status, [SiteVisitStatus::Requested, SiteVisitStatus::Rescheduled, SiteVisitStatus::Confirmed], true), LeadRuleViolation::class, 'This visit is already closed.');
        throw_unless($at->gt(now()), LeadRuleViolation::class, 'Pick a visit time in the future.');
        throw_unless($agent->can(Permissions::LEADS_WORK) || $agent->can(Permissions::LEADS_MANAGE), LeadRuleViolation::class, 'That person cannot take site visits.');
        $meetingPoint = trim($meetingPoint);
        throw_if($meetingPoint === '', LeadRuleViolation::class, 'Enter where the visit will meet.');

        $wasConfirmed = $visit->status === SiteVisitStatus::Confirmed;

        DB::transaction(function () use ($visit, $at, $agent, $meetingPoint, $by, $wasConfirmed) {
            $visit->forceFill(['status' => SiteVisitStatus::Confirmed, 'scheduled_at' => $at, 'assigned_to' => $agent->id, 'meeting_point' => $meetingPoint, 'confirmed_at' => $visit->confirmed_at ?? now()])->save();
            $lead = $visit->lead;
            if ($lead->assigned_to === null) {
                $this->leads->assign($lead, $agent, $by);
            }
            $this->note($visit, ($wasConfirmed ? 'Visit rescheduled to ' : 'Visit confirmed for ').$at->format('d M Y H:i').", meeting at {$meetingPoint}", $by);
            if (! $lead->status->isTerminal() && ($lead->status->rank() ?? 99) < (LeadStatus::SiteVisit->rank() ?? 0)) {
                $this->leads->changeStatus($lead, LeadStatus::SiteVisit, $by);
            }
            $this->audit->log($wasConfirmed ? 'site_visit.rescheduled' : 'site_visit.confirmed', $visit, null, ['scheduled_at' => $at->toIso8601String(), 'assigned_to' => $agent->id], $by->id);
        });

        if ($agent->id !== $by->id) {
            Notification::make()->title('A site visit was assigned to you')->body($at->format('d M H:i').' · '.$meetingPoint)->warning()->sendToDatabase($agent);
        }

        return $visit;
    }

    public function complete(SiteVisit $visit, string $feedback, User $by): SiteVisit
    {
        $visit->loadMissing('lead.assignee');
        throw_unless($visit->status === SiteVisitStatus::Confirmed, LeadRuleViolation::class, 'Only a confirmed visit can be completed.');
        throw_unless($visit->scheduled_at && $visit->scheduled_at->lte(now()), LeadRuleViolation::class, 'The visit has not taken place yet.');
        $feedback = trim($feedback);
        throw_if($feedback === '', LeadRuleViolation::class, 'Record the buyer’s feedback.');

        return DB::transaction(function () use ($visit, $feedback, $by) {
            $visit->forceFill(['status' => SiteVisitStatus::Completed, 'completed_at' => now(), 'feedback' => $feedback])->save();
            $this->note($visit, "Visit completed: {$feedback}", $by);
            $this->followUpTomorrow($visit, 'Ask for feedback and next step after the visit', $by);
            $this->audit->log('site_visit.completed', $visit, null, null, $by->id);

            return $visit;
        });
    }

    public function cancel(SiteVisit $visit, string $reason, User $by): SiteVisit
    {
        $visit->loadMissing('lead.assignee');
        throw_if($visit->status->isClosed(), LeadRuleViolation::class, 'This visit is already closed.');
        $reason = trim($reason);
        throw_if($reason === '', LeadRuleViolation::class, 'Give a reason for cancelling.');

        return DB::transaction(function () use ($visit, $reason, $by) {
            $visit->forceFill(['status' => SiteVisitStatus::Cancelled, 'cancel_reason' => mb_substr($reason, 0, 500)])->save();
            $this->note($visit, "Visit cancelled: {$reason}", $by);
            $this->audit->log('site_visit.cancelled', $visit, null, null, $by->id);

            return $visit;
        });
    }

    public function markNoShow(SiteVisit $visit, User $by): SiteVisit
    {
        $visit->loadMissing('lead.assignee');
        throw_unless($visit->status === SiteVisitStatus::Confirmed, LeadRuleViolation::class, 'Only a confirmed visit can be marked as a no-show.');
        throw_unless($visit->scheduled_at && $visit->scheduled_at->lt(now()), LeadRuleViolation::class, 'The visit time has not passed yet.');

        return DB::transaction(function () use ($visit, $by) {
            $visit->forceFill(['status' => SiteVisitStatus::NoShow])->save();
            $this->note($visit, 'Buyer did not attend the visit', $by);
            $this->followUpTomorrow($visit, 'Re-engage after the missed visit', $by);
            $this->audit->log('site_visit.no_show', $visit, null, null, $by->id);

            return $visit;
        });
    }

    private function note(SiteVisit $visit, string $body, User $by): void
    {
        // Visit entries are written straight to the timeline so they are not mistaken for a hand-logged contact.
        $visit->lead->activities()->create(['user_id' => $by->id, 'type' => LeadActivityType::Visit, 'body' => $body]);
        $visit->lead->forceFill(['last_activity_at' => now()])->save();
    }

    private function followUpTomorrow(SiteVisit $visit, string $note, User $by): void
    {
        $lead = $visit->lead;
        if (! $lead->status->isTerminal()) {
            $this->leads->scheduleFollowUp($lead, now()->addDay()->setTime(10, 0), $note, $by, $lead->assignee ?? $by);
        }
    }
}
