<?php

use App\Enums\PublishStatus;
use App\Enums\RoleName;
use App\Filament\Resources\Developers\Pages\EditDeveloper;
use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Filament\Resources\Properties\RelationManagers\MediaRelationManager;
use App\Filament\Widgets\OverviewStats;
use App\Models\AuditLog;
use App\Models\BlogPost;
use App\Models\Developer;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\Location;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyType;
use App\Services\ImageProcessor;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\Storage;
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

it('renders every admin list with real rows (guards against closure-injection bugs)', function () {
    $dev = Developer::factory()->published()->create();
    $loc = Location::factory()->create(['type' => 'area']);
    $project = Project::factory()->published()->create(['developer_id' => $dev->id, 'location_id' => $loc->id]);
    $project->unitTypes()->create(['name' => 'Type A', 'bedrooms' => 3]);
    $project->units()->create(['unit_no' => 'A-1', 'status' => 'available']);
    Property::factory()->published()->create(['location_id' => $loc->id, 'project_id' => $project->id, 'developer_id' => $dev->id]);
    Lead::factory()->create();
    Faq::create(['category' => 'General', 'question' => 'Q?', 'answer' => 'A']);
    $post = new BlogPost(['title' => 'Post', 'body' => 'Body']);
    $post->publish_status = PublishStatus::Published;
    $post->published_at = now();
    $post->save();
    $this->seed(ReferenceDataSeeder::class);

    $this->actingAs(userWithRole(RoleName::SuperAdmin));
    foreach (['properties', 'projects', 'developers', 'locations', 'property-types', 'amenities', 'leads', 'faqs', 'blog-posts', 'users', 'audit-logs'] as $r) {
        $this->get("/admin/$r")->assertOk();
    }
    $this->get("/admin/projects/{$project->id}/edit")->assertOk();
    $this->get('/admin/properties/'.Property::first()->id.'/edit')->assertOk();
    $this->get("/admin/developers/{$dev->id}/edit")->assertOk();
});

it('renders the media manager with uploaded images and private documents', function () {
    Storage::fake('public');
    Storage::fake('local');
    $property = Property::factory()->create();
    $im = imagecreatetruecolor(900, 600);
    $path = tempnam(sys_get_temp_dir(), 'm').'.jpg';
    imagejpeg($im, $path);
    app(ImageProcessor::class)->store($path, $property, 'gallery', alt: 'Front');
    app(ImageProcessor::class)->store($path, $property, 'documents', private: true);

    $this->actingAs(userWithRole(RoleName::PropertyManager));
    Livewire::test(MediaRelationManager::class, ['ownerRecord' => $property, 'pageClass' => EditProperty::class])
        ->assertSuccessful()->assertCountTableRecords(2);
});

it('shows only permitted real counts on the admin dashboard', function () {
    Property::factory()->published()->count(2)->create();
    Lead::factory()->create();

    $this->actingAs(userWithRole(RoleName::SalesManager));
    Livewire::test(OverviewStats::class)->assertSee('Published properties')->assertSee('New inquiries');

    $this->actingAs(userWithRole(RoleName::PropertyManager));
    Livewire::test(OverviewStats::class)->assertSee('Published properties')->assertDontSee('New inquiries');
});
