<?php

use App\Enums\LeadStatus;
use App\Enums\RoleName;
use App\Filament\Pages\CrmReports;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Location;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\SiteVisit;
use App\Services\CrmReport;
use App\Services\LeadRuleViolation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('lets a lead become qualified or interested only when the buyer’s needs are known', function () {
    $a = agent();
    $bare = Lead::factory()->create();

    expect(fn () => wf()->changeStatus($bare, LeadStatus::Qualified, $a))->toThrow(LeadRuleViolation::class, 'budget or preferred area');
    expect(fn () => wf()->changeStatus($bare, LeadStatus::Interested, $a))->toThrow(LeadRuleViolation::class, 'interested in');

    wf()->updateNeeds($bare, ['preferred_location_id' => Location::factory()->create()->id], $a);
    wf()->changeStatus($bare->fresh(), LeadStatus::Qualified, $a);
    expect($bare->fresh()->status)->toBe(LeadStatus::Qualified);

    $withProperty = Lead::factory()->create(['property_id' => Property::factory()->published()->create()->id]);
    wf()->changeStatus($withProperty, LeadStatus::Interested, $a);
    expect($withProperty->fresh()->status)->toBe(LeadStatus::Interested);
});

it('saves the buyer’s needs, logs it and audits ids and numbers only', function () {
    $lead = Lead::factory()->create(['phone' => '+8801712345678']);
    $loc = Location::factory()->create();
    $type = PropertyType::create(['name' => 'Studio', 'group' => 'residential', 'sort_order' => 1, 'is_active' => true]);

    wf()->updateNeeds($lead, ['budget_min' => 8_000_000, 'budget_max' => 12_000_000, 'preferred_location_id' => $loc->id, 'property_type_id' => $type->id, 'purpose' => 'sale', 'buy_timeline' => 'within_3_months'], agent());

    $lead = $lead->fresh();
    expect((float) $lead->budget_max)->toBe(12_000_000.0)->and($lead->buy_timeline->label())->toBe('Within 3 months')->and($lead->hasNeeds())->toBeTrue()
        ->and($lead->activities->first()->body)->toBe('Buyer needs updated');
    $audit = AuditLog::where('event', 'lead.needs_updated')->first();
    expect($audit)->not->toBeNull()->and(json_encode($audit->new_values))->not->toContain('8801712345678');

    expect(fn () => wf()->updateNeeds($lead, ['budget_min' => 9, 'budget_max' => 5], agent()))->toThrow(LeadRuleViolation::class, 'minimum');
    expect(fn () => wf()->updateNeeds($lead, ['purpose' => 'swap'], agent()))->toThrow(LeadRuleViolation::class, 'sale or rent');
});

it('edits the buyer’s needs on the lead page and rejects a minimum above the maximum', function () {
    $m = manager();
    $lead = Lead::factory()->create();
    $this->actingAs($m);

    Livewire::test(EditLead::class, ['record' => $lead->id])
        ->fillForm(['budget_min' => 5_000_000, 'budget_max' => 9_000_000, 'purpose' => 'rent', 'buy_timeline' => 'immediately', 'priority' => 'normal'])
        ->call('save')->assertHasNoFormErrors();
    expect((float) $lead->fresh()->budget_min)->toBe(5_000_000.0)->and($lead->fresh()->purpose)->toBe('rent');

    Livewire::test(EditLead::class, ['record' => $lead->id])
        ->fillForm(['budget_min' => 9_000_000, 'budget_max' => 5_000_000, 'priority' => 'normal'])
        ->call('save')->assertHasFormErrors(['budget_max']);
});

it('keeps needs consistent at the database level', function () {
    $lead = Lead::factory()->create();
    expect(fn () => DB::transaction(fn () => DB::table('leads')->where('id', $lead->id)->update(['budget_min' => 10, 'budget_max' => 5])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('leads')->where('id', $lead->id)->update(['buy_timeline' => 'someday'])))->toThrow(QueryException::class);
});

it('copies budget and property type from the assistance form onto the lead', function () {
    PropertyType::create(['name' => 'Flat', 'group' => 'residential', 'sort_order' => 1, 'is_active' => true]);

    $this->post('/requests/assistance', ['name' => 'Rina', 'phone' => '01712345678', 'consent' => '1', 'property_type' => 'Flat', 'expected_price' => 7_500_000, 'area' => 'Uttara', '_t' => now()->subMinute()->timestamp]);

    $lead = Lead::sole();
    expect((float) $lead->budget_max)->toBe(7_500_000.0)->and($lead->propertyType?->name)->toBe('Flat')->and($lead->hasNeeds())->toBeTrue();
});

it('computes the CRM numbers from recorded data only', function () {
    $a = agent();
    $m = manager();
    $p = Property::factory()->published()->create(['title' => 'Lake Flat']);
    $pr = Project::factory()->published()->create(['name' => 'Green Court']);
    $mk = fn (array $x = []) => Lead::factory()->create(array_merge(['created_at' => now()->subDays(2), 'utm_source' => 'facebook', 'property_id' => $p->id], $x));

    $fast = $mk(['assigned_to' => $a->id, 'first_contacted_at' => now()->subDays(2)->addMinutes(10), 'status' => LeadStatus::Contacted]);
    $slow = $mk(['assigned_to' => $a->id, 'first_contacted_at' => now()->subDays(2)->addHours(5), 'status' => LeadStatus::Contacted, 'utm_source' => null, 'property_id' => null, 'project_id' => $pr->id]);
    $waiting = $mk(['inquiry_count' => 3]);
    $lost = $mk(['status' => LeadStatus::Lost, 'lost_reason' => ' Bought elsewhere ', 'first_contacted_at' => now()->subDays(2)->addMinutes(20)]);
    $old = Lead::factory()->create(['created_at' => now()->subDays(60)]);

    wf()->updateNeeds($fast, ['budget_max' => 9_000_000], $a);
    wf()->changeStatus($fast->fresh(), LeadStatus::Qualified, $a);
    wf()->changeStatus($fast->fresh(), LeadStatus::Converted, $m, 'Booked flat B-4');
    SiteVisit::create(['lead_id' => $fast->id, 'property_id' => $p->id, 'preferred_date' => now()->addDay()->toDateString(), 'preferred_window' => 'morning', 'status' => 'completed']);
    SiteVisit::create(['lead_id' => $slow->id, 'property_id' => $p->id, 'preferred_date' => now()->addDay()->toDateString(), 'preferred_window' => 'morning']);

    $r = app(CrmReport::class)->build(now()->subDays(30)->startOfDay(), now());

    expect($r['total'])->toBe(4)->and($r['repeat'])->toBe(2)->and($r['response']['responded'])->toBe(3)->and($r['response']['waiting'])->toBe(1)
        ->and(round($r['response']['median_minutes']))->toBe(20.0)->and($r['response']['within_sla'])->toBe(2)
        ->and($r['funnel']['Contacted'])->toBe(3)->and($r['funnel']['Qualified'])->toBe(1)->and($r['funnel']['Converted'])->toBe(1)
        ->and($r['visits']['Completed'])->toBe(1)->and($r['visits']['Requested'])->toBe(1)->and($r['visit_leads'])->toBe(1)
        ->and($r['lost'])->toBe(['bought elsewhere' => 1])
        ->and(collect($r['sources'])->firstWhere('source', 'facebook')['leads'])->toBe(3)->and(collect($r['sources'])->firstWhere('source', 'direct')['leads'])->toBe(1)
        ->and(collect($r['agents'])->first()['assigned'])->toBe(2)->and(collect($r['agents'])->first()['converted'])->toBe(1)
        ->and($r['top_properties'][0])->toBe(['name' => 'Lake Flat', 'n' => 3])->and($r['top_projects'][0])->toBe(['name' => 'Green Court', 'n' => 1])
        ->and($old->created_at->lt($r['period']['from']))->toBeTrue();
});

it('shows the reports page to managers only and responds to the period filter', function () {
    Lead::factory()->count(2)->create(['created_at' => now()->subDays(20)]);

    $this->actingAs(manager());
    Livewire::test(CrmReports::class)->assertSuccessful()->assertSee('Last 30 days')->assertSee('Pipeline')
        ->set('range', '7')->assertSee('Leads created')
        ->set('range', 'bogus')->assertSuccessful();
    $this->get('/admin/crm-reports')->assertOk()->assertSee('Median first response');

    foreach ([RoleName::SalesAgent, RoleName::PropertyManager, RoleName::ContentManager] as $role) {
        $this->actingAs(userWithRole($role))->get('/admin/crm-reports')->assertForbidden();
    }
});
