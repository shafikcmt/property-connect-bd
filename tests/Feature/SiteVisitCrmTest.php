<?php

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Enums\RoleName;
use App\Enums\SiteVisitStatus;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\RelationManagers\SiteVisitsRelationManager;
use App\Filament\Resources\SiteVisits\Pages\ListSiteVisits;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Property;
use App\Models\SiteVisit;
use App\Services\LeadRuleViolation;
use App\Services\SiteVisitWorkflow;
use Livewire\Livewire;

function makeVisit(array $lead = [], array $attrs = []): SiteVisit
{
    $lead = Lead::factory()->create($lead);

    return SiteVisit::create(array_merge(['lead_id' => $lead->id, 'property_id' => Property::factory()->published()->create()->id, 'preferred_date' => now()->addDays(3)->toDateString(), 'preferred_window' => 'morning'], $attrs));
}

function svw(): SiteVisitWorkflow
{
    return app(SiteVisitWorkflow::class);
}

it('merges a repeat inquiry into the buyer’s open lead instead of creating a duplicate', function () {
    $m = manager();
    $p1 = Property::factory()->published()->create(['title' => 'Lake View Flat']);
    $p2 = Property::factory()->published()->create();
    $post = fn ($p, $extra = []) => $this->post('/inquiry', array_merge(['name' => 'Karim', 'phone' => '01712345678', 'message' => 'Is it still available?', 'consent' => '1', 'property_id' => $p->id, '_t' => now()->subMinute()->timestamp], $extra));

    $post($p1);
    $post($p2, ['message' => 'And this one?']);

    $lead = Lead::sole();
    expect($lead->inquiry_count)->toBe(2)->and($lead->priority->value)->toBe('high')
        ->and($lead->activities()->where('type', 'inquiry')->first()->body)->toContain('about')->toContain('And this one?')
        ->and($m->notifications()->count())->toBe(2); // one for the new lead, one for the repeat

    $a = agent();
    wf()->assign($lead, $a, $m);
    $this->travel(11)->minutes(); // an identical request within 10 minutes is treated as a double click
    $post($p1, ['phone' => '+8801712345678', '_t' => now()->subMinute()->timestamp]);
    expect($lead->fresh()->inquiry_count)->toBe(3)->and($a->notifications()->where('data->title', 'Repeat inquiry')->count())->toBe(1);
});

it('starts a fresh lead when the earlier one is closed or older than 90 days', function () {
    $p = Property::factory()->published()->create();
    $post = fn () => $this->post('/inquiry', ['name' => 'Karim', 'phone' => '01712345678', 'message' => 'Hello again', 'consent' => '1', 'property_id' => $p->id, '_t' => now()->subMinute()->timestamp]);

    $old = Lead::factory()->create(['phone' => '+8801712345678', 'status' => LeadStatus::Lost, 'lost_reason' => 'x']);
    $post();
    expect(Lead::count())->toBe(2);

    Lead::query()->delete();
    Lead::factory()->create(['phone' => '+8801712345678', 'created_at' => now()->subDays(100)]);
    $post();
    expect(Lead::count())->toBe(2);
});

it('attaches a new site-visit request to the buyer’s existing lead', function () {
    $lead = Lead::factory()->create(['phone' => '+8801712345678', 'status' => LeadStatus::Contacted]);
    $p = Property::factory()->published()->create();

    $this->post('/site-visit', ['name' => 'Karim', 'phone' => '01712345678', 'consent' => '1', 'property_id' => $p->id, 'preferred_date' => now()->addDays(4)->toDateString(), 'preferred_window' => 'evening', '_t' => now()->subMinute()->timestamp]);

    expect(Lead::count())->toBe(1)->and($lead->fresh()->siteVisits)->toHaveCount(1)->and($lead->fresh()->siteVisits->first()->status)->toBe(SiteVisitStatus::Requested);
});

it('confirms a visit with agent and meeting point, moves the lead to site visit and notifies the agent', function () {
    $m = manager();
    $a = agent();
    $v = makeVisit(['status' => LeadStatus::Qualified]);

    expect(fn () => svw()->confirm($v, now()->subHour(), $a, 'Gate', $m))->toThrow(LeadRuleViolation::class, 'future');
    expect(fn () => svw()->confirm($v, now()->addDay(), $a, '  ', $m))->toThrow(LeadRuleViolation::class, 'meet');
    expect(fn () => svw()->confirm($v, now()->addDay(), userWithRole(RoleName::ContentManager), 'Gate', $m))->toThrow(LeadRuleViolation::class, 'cannot take');

    svw()->confirm($v, now()->addDay(), $a, 'Site gate', $m);

    $v = $v->fresh();
    expect($v->status)->toBe(SiteVisitStatus::Confirmed)->and($v->assigned_to)->toBe($a->id)->and($v->meeting_point)->toBe('Site gate')
        ->and($v->lead->status)->toBe(LeadStatus::SiteVisit)->and($v->lead->assigned_to)->toBe($a->id)
        ->and($v->lead->activities()->where('type', LeadActivityType::Visit->value)->exists())->toBeTrue()
        ->and($a->notifications()->where('data->title', 'A site visit was assigned to you')->exists())->toBeTrue()
        ->and(AuditLog::where('event', 'site_visit.confirmed')->exists())->toBeTrue();

    svw()->confirm($v, now()->addDays(2), $a, 'Main road', $m);
    expect($v->fresh()->status)->toBe(SiteVisitStatus::Confirmed)->and(AuditLog::where('event', 'site_visit.rescheduled')->exists())->toBeTrue();
});

it('completes a visit only after it took place, with feedback, and schedules a follow-up', function () {
    $a = agent();
    $v = makeVisit(['status' => LeadStatus::SiteVisit]);
    svw()->confirm($v, now()->addHours(3), $a, 'Gate', manager());

    expect(fn () => svw()->complete($v->fresh(), 'Liked it', $a))->toThrow(LeadRuleViolation::class, 'not taken place');

    $this->travel(4)->hours();
    expect(fn () => svw()->complete($v->fresh(), ' ', $a))->toThrow(LeadRuleViolation::class, 'feedback');
    svw()->complete($v->fresh(), 'Liked the view, wants a discount', $a);

    $v = $v->fresh();
    expect($v->status)->toBe(SiteVisitStatus::Completed)->and($v->feedback)->toContain('discount')->and($v->completed_at)->not->toBeNull()
        ->and($v->lead->next_follow_up_at)->not->toBeNull();
    expect(fn () => svw()->cancel($v, 'Too late', $a))->toThrow(LeadRuleViolation::class, 'closed');
});

it('handles cancellations and no-shows, each with a timeline entry and re-engagement', function () {
    $a = agent();
    $cancelled = makeVisit();
    expect(fn () => svw()->cancel($cancelled, '', $a))->toThrow(LeadRuleViolation::class, 'reason');
    svw()->cancel($cancelled, 'Buyer travelling', $a);
    expect($cancelled->fresh()->status)->toBe(SiteVisitStatus::Cancelled)->and($cancelled->fresh()->cancel_reason)->toBe('Buyer travelling');

    $missed = makeVisit(['status' => LeadStatus::SiteVisit]);
    svw()->confirm($missed, now()->addHour(), $a, 'Gate', manager());
    expect(fn () => svw()->markNoShow($missed->fresh(), $a))->toThrow(LeadRuleViolation::class, 'not passed');
    $this->travel(2)->hours();
    svw()->markNoShow($missed->fresh(), $a);

    expect($missed->fresh()->status)->toBe(SiteVisitStatus::NoShow)->and($missed->fresh()->lead->followUps()->open()->count())->toBe(1);
});

it('shows agents only their visits and renders the board and lead tab with real rows', function () {
    $a = agent();
    $mine = makeVisit(['assigned_to' => $a->id]);
    $other = makeVisit();

    $this->actingAs($a);
    Livewire::test(ListSiteVisits::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$other]);

    $this->actingAs(manager());
    Livewire::test(ListSiteVisits::class)->assertCanSeeTableRecords([$mine, $other])
        ->callTableAction('confirm', $other, data: ['scheduled_at' => now()->addDay()->toDateTimeString(), 'assigned_to' => $a->id, 'meeting_point' => 'Office'])
        ->assertHasNoTableActionErrors();
    expect($other->fresh()->status)->toBe(SiteVisitStatus::Confirmed);

    Livewire::test(SiteVisitsRelationManager::class, ['ownerRecord' => $other->lead, 'pageClass' => EditLead::class])->assertSuccessful()->assertCountTableRecords(1);
    $this->get('/admin/site-visits')->assertOk();

    $this->actingAs(userWithRole(RoleName::PropertyManager))->get('/admin/site-visits')->assertForbidden();
});

it('keeps visits that target a project working too', function () {
    $lead = Lead::factory()->create();
    $v = SiteVisit::create(['lead_id' => $lead->id, 'project_id' => Project::factory()->published()->create()->id, 'preferred_date' => now()->addDay()->toDateString(), 'preferred_window' => 'afternoon']);

    svw()->confirm($v, now()->addDay(), agent(), 'Sales office', manager());
    expect($v->fresh()->status)->toBe(SiteVisitStatus::Confirmed);
});
