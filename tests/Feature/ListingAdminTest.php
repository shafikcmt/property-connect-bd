<?php

use App\Enums\RoleName;
use App\Filament\Resources\Developers\Pages\EditDeveloper;
use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Models\AuditLog;
use App\Models\Developer;
use App\Models\Property;
use App\Models\PropertyType;
use Livewire\Livewire;

$resources = ['/admin/properties', '/admin/projects', '/admin/developers', '/admin/locations', '/admin/property-types', '/admin/amenities'];

it('lets listing managers open every Phase 2 admin list', function (RoleName $role, string $url) {
    $this->actingAs(userWithRole($role))->get($url)->assertOk();
})->with([RoleName::PropertyManager, RoleName::Admin, RoleName::SuperAdmin])->with($resources);

it('lets sales roles read listings but not create them', function (RoleName $role) {
    $this->actingAs(userWithRole($role));
    $this->get('/admin/properties')->assertOk();
    $this->get('/admin/properties/create')->assertForbidden();
    $this->get('/admin/locations/create')->assertForbidden();
})->with([RoleName::SalesManager, RoleName::SalesAgent]);

it('keeps content managers and buyers out of listings', function (RoleName $role) {
    $this->actingAs(userWithRole($role));
    $this->get('/admin/properties')->assertForbidden();
    $this->get('/admin/developers')->assertForbidden();
})->with([RoleName::ContentManager, RoleName::Buyer]);

it('creates a property through the admin form', function () {
    $this->actingAs(userWithRole(RoleName::PropertyManager));
    $type = PropertyType::firstOrCreate(['name' => 'Apartment'], ['group' => 'residential']);

    Livewire::test(CreateProperty::class)
        ->fillForm(['title' => 'Bright 3 bed apartment', 'purpose' => 'sale', 'property_type_id' => $type->id, 'property_status' => 'ready', 'price' => 8500000, 'size_sqft' => 1450])
        ->call('create')
        ->assertHasNoFormErrors();

    $p = Property::firstWhere('title', 'Bright 3 bed apartment');
    expect($p->slug)->toBe('bright-3-bed-apartment')->and($p->created_by)->not->toBeNull()->and($p->publish_status->value)->toBe('draft');
});

it('validates required property fields and rejects negative prices', function () {
    $this->actingAs(userWithRole(RoleName::PropertyManager));

    Livewire::test(CreateProperty::class)
        ->fillForm(['title' => '', 'price' => -5])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required', 'price']);
});

it('blocks publishing for users without listings.publish and shows problems otherwise', function () {
    $property = Property::factory()->create(['description' => 'short']);

    $this->actingAs(userWithRole(RoleName::SalesManager));
    $this->get("/admin/properties/{$property->id}/edit")->assertForbidden();

    $this->actingAs(userWithRole(RoleName::PropertyManager));
    Livewire::test(EditProperty::class, ['record' => $property->id])
        ->callAction('publish')
        ->assertNotified('Not ready to publish');
    expect($property->fresh()->isPublished())->toBeFalse();
});

it('records a verification through the admin action and writes an audit entry', function () {
    $dev = Developer::factory()->create();
    $this->actingAs(userWithRole(RoleName::PropertyManager));

    Livewire::test(EditDeveloper::class, ['record' => $dev->id])
        ->callAction('verify', ['method' => 'Document check', 'notes' => 'ok']);

    expect($dev->fresh()->is_verified)->toBeTrue()
        ->and(AuditLog::where('event', 'listing.verified')->exists())->toBeTrue();
});

it('lets only publishers feature listings', function () {
    $dev = Developer::factory()->create();
    $this->actingAs(userWithRole(RoleName::PropertyManager));

    Livewire::test(EditDeveloper::class, ['record' => $dev->id])
        ->callAction('feature', ['is_featured' => true, 'featured_until' => now()->addWeek()->toDateTimeString()]);
    expect($dev->fresh()->is_featured)->toBeTrue();
});
