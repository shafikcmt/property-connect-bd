<?php

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Enums\RoleName;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\Leads\RelationManagers\FollowUpsRelationManager;
use App\Filament\Widgets\OverviewStats;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Property;
use App\Services\LeadRuleViolation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('moves a lead forward through the pipeline and records history, activity and audit', function () {
    $lead = Lead::factory()->create(['budget_max' => 20_000_000]);
    $by = agent();

    wf()->changeStatus($lead, LeadStatus::Qualified, $by);

    $lead = $lead->fresh();
    expect($lead->status)->toBe(LeadStatus::Qualified)->and($lead->first_contacted_at)->not->toBeNull()
        ->and($lead->statusHistories)->toHaveCount(1)->and($lead->activities->first()->type)->toBe(LeadActivityType::StatusChange)
        ->and(AuditLog::where('event', 'lead.status_changed')->exists())->toBeTrue();
});

it('enforces the transition rules of docs/13', function () {
    $m = manager();
    $a = agent();
    $lead = Lead::factory()->create(['status' => LeadStatus::Interested]);

    expect(fn () => wf()->changeStatus($lead, LeadStatus::Lost, $a))->toThrow(LeadRuleViolation::class, 'reason');
    expect(fn () => wf()->changeStatus($lead, LeadStatus::Duplicate, $a))->toThrow(LeadRuleViolation::class, 'original');
    expect(fn () => wf()->changeStatus($lead, LeadStatus::Converted, $a, 'Sold the flat'))->toThrow(LeadRuleViolation::class, 'manager');
    expect(fn () => wf()->changeStatus($lead, LeadStatus::Converted, $m, 'ok'))->toThrow(LeadRuleViolation::class, 'note');
    expect(fn () => wf()->changeStatus($lead, LeadStatus::New, $a, 'oops'))->toThrow(LeadRuleViolation::class, 'manager');
    expect(fn () => wf()->changeStatus($lead, LeadStatus::Contacted, $m))->toThrow(LeadRuleViolation::class, 'reason');

    wf()->changeStatus($lead, LeadStatus::Contacted, $m, 'Buyer asked to restart');
    expect($lead->fresh()->statusHistories->first()->is_override)->toBeTrue()->and(AuditLog::where('event', 'lead.status_override')->exists())->toBeTrue();

    wf()->changeStatus($lead->fresh(), LeadStatus::Lost, $a, 'Bought elsewhere');
    expect($lead->fresh()->lost_reason)->toBe('Bought elsewhere');
    expect(fn () => wf()->changeStatus($lead->fresh(), LeadStatus::Qualified, $a, 'again'))->toThrow(LeadRuleViolation::class, 'manager');
    expect(fn () => wf()->changeStatus($lead->fresh(), LeadStatus::Qualified, $m, 'again'))->toThrow(LeadRuleViolation::class, 'Contacted');

    wf()->changeStatus($lead->fresh(), LeadStatus::Contacted, $m, 'Back in touch');
    expect($lead->fresh()->status)->toBe(LeadStatus::Contacted)->and($lead->fresh()->lost_reason)->toBeNull();
});

it('marks duplicates against an existing original and converts only with a manager note', function () {
    $original = Lead::factory()->create();
    $copy = Lead::factory()->create();

    wf()->changeStatus($copy, LeadStatus::Duplicate, agent(), null, $original->id);
    expect($copy->fresh()->duplicate_of_id)->toBe($original->id);

    wf()->changeStatus($original, LeadStatus::Converted, manager(), 'Booked flat B-4');
    expect($original->fresh()->status)->toBe(LeadStatus::Converted);
});

it('assigns leads, notifies the assignee and rejects people who cannot work leads', function () {
    $lead = Lead::factory()->create();
    $m = manager();
    $a = agent();

    wf()->assign($lead, $a, $m);

    expect($lead->fresh()->assigned_to)->toBe($a->id)->and($a->notifications()->count())->toBe(1)
        ->and($lead->fresh()->activities->first()->type)->toBe(LeadActivityType::Assignment)->and(AuditLog::where('event', 'lead.assigned')->exists())->toBeTrue();

    wf()->assign($lead->fresh(), $m, $m);
    expect($m->notifications()->count())->toBe(0);

    expect(fn () => wf()->assign($lead, userWithRole(RoleName::ContentManager), $m))->toThrow(LeadRuleViolation::class, 'cannot work');

    wf()->assign($lead->fresh(), null, $m);
    expect($lead->fresh()->assigned_to)->toBeNull();
});

it('logs activities, treats a real contact as the first response and refuses empty or automatic entries', function () {
    $lead = Lead::factory()->create();
    $a = agent();

    wf()->logActivity($lead, LeadActivityType::Note, 'Internal thought', $a);
    expect($lead->fresh()->status)->toBe(LeadStatus::New)->and($lead->fresh()->first_contacted_at)->toBeNull();

    wf()->logActivity($lead, LeadActivityType::Call, 'Spoke to the buyer', $a);
    $lead = $lead->fresh();
    expect($lead->status)->toBe(LeadStatus::Contacted)->and($lead->first_contacted_at)->not->toBeNull();

    expect(fn () => wf()->logActivity($lead, LeadActivityType::Call, '  ', $a))->toThrow(LeadRuleViolation::class);
    expect(fn () => wf()->logActivity($lead, LeadActivityType::StatusChange, 'x', $a))->toThrow(LeadRuleViolation::class, 'automatically');
});

it('schedules and completes follow-ups, keeping the next follow-up date in sync', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::Contacted]);
    $a = agent();

    $late = wf()->scheduleFollowUp($lead, now()->addDays(5), 'Send brochure', $a);
    $soon = wf()->scheduleFollowUp($lead, now()->addDay(), 'Call back', $a);
    expect($lead->fresh()->next_follow_up_at->equalTo($soon->due_at))->toBeTrue();

    expect(fn () => wf()->completeFollowUp($soon, ' ', $a))->toThrow(LeadRuleViolation::class, 'outcome');
    wf()->completeFollowUp($soon, 'Buyer wants a visit', $a);
    expect($lead->fresh()->next_follow_up_at->equalTo($late->due_at))->toBeTrue()
        ->and(fn () => wf()->completeFollowUp($soon->fresh(), 'again', $a))->toThrow(LeadRuleViolation::class, 'already');

    expect(fn () => wf()->scheduleFollowUp($lead, now()->subDay(), null, $a))->toThrow(LeadRuleViolation::class, 'future');
    wf()->changeStatus($lead->fresh(), LeadStatus::Lost, $a, 'No answer');
    expect(fn () => wf()->scheduleFollowUp($lead->fresh(), now()->addDay(), null, $a))->toThrow(LeadRuleViolation::class, 'closed');
});

it('sends each due follow-up reminder once', function () {
    $a = agent();
    $lead = Lead::factory()->create(['status' => LeadStatus::Contacted, 'assigned_to' => $a->id]);
    $f = wf()->scheduleFollowUp($lead, now()->addHour(), 'Call', $a, $a);
    $this->artisan('pcbd:crm-reminders')->assertSuccessful();
    expect($a->notifications()->count())->toBe(0);

    $this->travel(2)->hours();
    $this->artisan('pcbd:crm-reminders')->assertSuccessful();
    $this->artisan('pcbd:crm-reminders')->assertSuccessful();

    expect($a->notifications()->count())->toBe(1)->and($f->fresh()->reminded_at)->not->toBeNull();
});

it('flags overdue responses, overdue follow-ups, stale and unassigned leads', function () {
    $fresh = Lead::factory()->create();
    $slow = Lead::factory()->create(['created_at' => now()->subHours(3)]);
    $stale = Lead::factory()->create(['status' => LeadStatus::Contacted, 'first_contacted_at' => now()->subDays(20), 'last_activity_at' => now()->subDays(20), 'created_at' => now()->subDays(20)]);
    $overdue = Lead::factory()->create(['status' => LeadStatus::Qualified, 'next_follow_up_at' => now()->subHour(), 'last_activity_at' => now(), 'assigned_to' => agent()->id]);
    $closed = Lead::factory()->create(['status' => LeadStatus::Lost, 'created_at' => now()->subDays(40)]);

    expect(Lead::slaBreached()->pluck('id')->all())->toBe([$slow->id])
        ->and(Lead::stale()->pluck('id')->all())->toBe([$stale->id])
        ->and(Lead::followUpOverdue()->pluck('id')->all())->toBe([$overdue->id])
        ->and(Lead::unassigned()->pluck('id')->sort()->values()->all())->toBe(collect([$fresh->id, $slow->id, $stale->id])->sort()->values()->all())
        ->and($slow->isSlaBreached())->toBeTrue()->and($fresh->isSlaBreached())->toBeFalse()->and($closed->isSlaBreached())->toBeFalse();
});

it('shows agents only their own leads and refuses the rest', function () {
    $a = agent();
    $mine = Lead::factory()->create(['assigned_to' => $a->id]);
    $other = Lead::factory()->create();

    $this->actingAs($a);
    Livewire::test(ListLeads::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$other]);
    $this->get("/admin/leads/{$mine->id}/edit")->assertOk();
    $this->get("/admin/leads/{$other->id}/edit")->assertNotFound();

    $this->actingAs(manager());
    Livewire::test(ListLeads::class)->assertCanSeeTableRecords([$mine, $other]);
});

it('lets only managers assign, and drives the workflow from the lead page', function () {
    $lead = Lead::factory()->create();
    $a = agent();

    $this->actingAs($a);
    Livewire::test(EditLead::class, ['record' => $lead->id])->assertNotFound();

    $lead->update(['assigned_to' => $a->id]);
    Livewire::test(EditLead::class, ['record' => $lead->id])
        ->assertActionHidden('assign')->assertActionVisible('logActivity')
        ->callAction('logActivity', data: ['type' => 'call', 'body' => 'Rang the buyer'])
        ->callAction('scheduleFollowUp', data: ['due_at' => now()->addDay()->toDateTimeString(), 'note' => 'Visit?'])
        ->callAction('changeStatus', data: ['status' => 'lost', 'reason' => '']);
    expect($lead->fresh()->status)->toBe(LeadStatus::Contacted)->and($lead->fresh()->next_follow_up_at)->not->toBeNull();

    $this->actingAs(manager());
    Livewire::test(EditLead::class, ['record' => $lead->id])->assertActionVisible('assign')
        ->callAction('assign', data: ['assigned_to' => null]);
    expect($lead->fresh()->assigned_to)->toBeNull();
});

it('renders the timeline and follow-up managers with real rows and completes a follow-up', function () {
    $m = manager();
    $lead = Lead::factory()->create(['status' => LeadStatus::Contacted, 'assigned_to' => $m->id]);
    wf()->logActivity($lead, LeadActivityType::Whatsapp, 'Sent photos', $m);
    $f = wf()->scheduleFollowUp($lead, now()->addDay(), 'Ask about budget', $m);

    $this->actingAs($m);
    Livewire::test(ActivitiesRelationManager::class, ['ownerRecord' => $lead, 'pageClass' => EditLead::class])->assertSuccessful()->assertCountTableRecords(2);
    Livewire::test(FollowUpsRelationManager::class, ['ownerRecord' => $lead, 'pageClass' => EditLead::class])->assertSuccessful()->assertCountTableRecords(1)
        ->callTableAction('complete', $f, data: ['outcome' => 'Budget is 1.2 Cr']);

    expect($f->fresh()->completed_at)->not->toBeNull()->and($lead->fresh()->next_follow_up_at)->toBeNull();
});

it('shows CRM numbers on the dashboard only to people who work leads', function () {
    Lead::factory()->create(['created_at' => now()->subHours(2)]);

    $this->actingAs(manager());
    Livewire::test(OverviewStats::class)->assertSee('Response overdue')->assertSee('Unassigned leads');

    $this->actingAs(agent());
    Livewire::test(OverviewStats::class)->assertSee('Response overdue')->assertDontSee('Unassigned leads');

    $this->actingAs(userWithRole(RoleName::PropertyManager));
    Livewire::test(OverviewStats::class)->assertDontSee('Response overdue');
});

it('alerts managers in-app about a new public inquiry without including contact data', function () {
    $m = manager();
    $p = Property::factory()->published()->create();

    $this->post('/inquiry', ['name' => 'Karim Hasan', 'phone' => '01712345678', 'message' => 'Hello', 'consent' => '1', 'property_id' => $p->id, '_t' => now()->subMinute()->timestamp]);

    $n = $m->notifications()->first();
    expect($n)->not->toBeNull()->and(json_encode($n->data))->not->toContain('Karim')->not->toContain('01712345678');
    expect(Lead::first()->last_activity_at)->not->toBeNull();
});

it('keeps priority inside the allowed set at the database level and seeds the new permissions', function () {
    $lead = Lead::factory()->create();
    // A savepoint keeps the surrounding test transaction usable after PostgreSQL rejects the row.
    expect(fn () => DB::transaction(fn () => DB::table('leads')->where('id', $lead->id)->update(['priority' => 'whenever'])))->toThrow(QueryException::class);

    $this->seed(RolesAndPermissionsSeeder::class);
    expect(agent()->can('leads.work'))->toBeTrue()->and(agent()->can('leads.manage'))->toBeFalse()->and(manager()->can('leads.assign'))->toBeTrue();
});
