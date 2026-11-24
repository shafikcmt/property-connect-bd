<?php

use App\Enums\RoleName;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Models\AuditLog;
use App\Models\Lead;
use Livewire\Livewire;

it('limits the inquiry inbox to staff with leads.manage', function (RoleName $role, int $status) {
    $this->actingAs(userWithRole($role))->get('/admin/leads')->assertStatus($status);
})->with([[RoleName::Admin, 200], [RoleName::SalesManager, 200], [RoleName::SalesAgent, 200], [RoleName::PropertyManager, 403], [RoleName::ContentManager, 403], [RoleName::Buyer, 403]]);

it('audits viewing and status changes of an inquiry without changing contact data', function () {
    $lead = Lead::factory()->create(['phone' => '+8801712345678']);
    $this->actingAs(userWithRole(RoleName::SalesManager));

    Livewire::test(EditLead::class, ['record' => $lead->id])
        ->fillForm(['priority' => 'high', 'internal_notes' => 'Called back'])
        ->call('save')->assertHasNoFormErrors()
        ->callAction('changeStatus', data: ['status' => 'contacted']);

    expect($lead->fresh()->status->value)->toBe('contacted')->and($lead->fresh()->priority->value)->toBe('high')->and($lead->fresh()->phone)->toBe('+8801712345678')
        ->and(AuditLog::where('event', 'lead.viewed')->exists())->toBeTrue()
        ->and(AuditLog::where('event', 'lead.status_changed')->exists())->toBeTrue();
});

it('limits content screens to content managers and admins', function (RoleName $role, int $status) {
    $this->actingAs(userWithRole($role));
    $this->get('/admin/faqs')->assertStatus($status);
    $this->get('/admin/blog-posts')->assertStatus($status);
})->with([[RoleName::ContentManager, 200], [RoleName::Admin, 200], [RoleName::SalesManager, 403], [RoleName::PropertyManager, 403]]);
