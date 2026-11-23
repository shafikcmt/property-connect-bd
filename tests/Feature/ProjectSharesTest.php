<?php

use App\Enums\RoleName;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\RelationManagers\InstallmentsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\UpdatesRelationManager;
use App\Models\Developer;
use App\Models\Project;
use App\Services\ImageProcessor;
use App\Support\Color;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function shareProject(array $attrs = [], array $dev = []): Project
{
    $developer = Developer::factory()->published()->create(array_merge(['name' => 'Nabo Builders'], $dev));

    return Project::factory()->published()->create(array_merge([
        'developer_id' => $developer->id, 'name' => 'Nabo Digonto 90', 'is_share_project' => true,
        'total_shares' => 90, 'sold_shares' => 90, 'registered_shares' => 60, 'price_per_share' => 900000, 'buildings_count' => 2, 'land_area_shatangsho' => 35.5,
    ], $attrs));
}

function withLogo(Developer $developer): void
{
    Storage::fake('public');
    $path = tempnam(sys_get_temp_dir(), 'l').'.png';
    imagepng(imagecreatetruecolor(300, 300), $path);
    app(ImageProcessor::class)->store($path, $developer, 'logo');
}

it('shows share counts and per-share price only when verified', function () {
    $project = shareProject();

    $this->get("/projects/{$project->slug}")->assertOk()->assertSee('Land shares')->assertSee('Total shares')
        ->assertDontSee('Registered deeds: 60')->assertDontSee('Price per share')->assertSee('Before you pay anything');

    $project->update(['shares_verified' => true]);
    $this->get("/projects/{$project->slug}")->assertSee('Registered deeds: 60 of 90 shareholders', false)->assertDontSee('Price per share');

    $project->update(['price_verified' => true]);
    $this->get("/projects/{$project->slug}")->assertSee('Price per share');
});

it('does not show a share section on ordinary projects', function () {
    $project = Project::factory()->published()->create();

    $this->get("/projects/{$project->slug}")->assertOk()->assertDontSee('Land shares')->assertDontSee('Before you pay anything');
});

it('lists the instalment schedule and only published timeline updates, newest first', function () {
    $project = shareProject();
    $project->installments()->create(['label' => 'Land share price', 'timing' => 'At booking', 'amount' => 900000, 'sort_order' => 1]);
    $project->installments()->create(['label' => 'Construction instalment', 'timing' => 'Monthly', 'sort_order' => 2]);
    $project->updates()->create(['title' => 'First deed batch', 'happened_on' => '2026-05-01']);
    $project->updates()->create(['title' => 'Second deed batch', 'happened_on' => '2026-09-01']);
    $project->updates()->create(['title' => 'Draft note', 'happened_on' => '2026-10-01', 'is_published' => false]);

    $this->get("/projects/{$project->slug}")->assertSeeInOrder(['Land share price', 'Construction instalment'])
        ->assertSeeInOrder(['Second deed batch', 'First deed batch'])->assertDontSee('Draft note');
});

it('renders a co-branded landing page that still says it is marketed by the platform', function () {
    $project = shareProject(['branded_landing' => true], ['brand_color' => '#14532d']);
    withLogo($project->developer);

    $this->get("/projects/{$project->slug}")->assertOk()
        ->assertSee('Marketed by')->assertSee('Nabo Builders')->assertSee('This is not the developer', false)
        ->assertSee('--color-brand-600: #14532d', false)->assertDontSee('Submit Property');
});

it('falls back to the normal page without a logo or when the developer is unpublished', function () {
    $noLogo = shareProject(['branded_landing' => true]);
    $this->get("/projects/{$noLogo->slug}")->assertOk()->assertSee('Submit Property');

    $unpublished = shareProject(['branded_landing' => true, 'name' => 'Other'], ['publish_status' => 'draft']);
    withLogo($unpublished->developer);
    expect($unpublished->fresh()->isBranded())->toBeFalse();
});

it('ignores brand colours that would fail text contrast', function () {
    $project = shareProject(['branded_landing' => true], ['brand_color' => '#ffee00']);
    withLogo($project->developer);

    expect(Color::contrastWithWhite('#ffee00'))->toBeLessThan(4.5);
    $this->get("/projects/{$project->slug}")->assertOk()->assertDontSee('--color-brand-600', false);
    expect(Developer::factory()->make(['brand_color' => 'red; background:url(x)'])->accessibleBrandColor())->toBeNull();
});

it('rejects impossible share counts at the database level', function () {
    expect(fn () => shareProject(['total_shares' => 10, 'sold_shares' => 20]))->toThrow(QueryException::class);
});

it('renders the instalment and update managers with real rows', function () {
    $project = shareProject();
    $project->installments()->create(['label' => 'Booking', 'amount' => 100000]);
    $project->updates()->create(['title' => 'Piling started', 'happened_on' => '2026-08-01']);

    $this->actingAs(userWithRole(RoleName::PropertyManager));
    Livewire::test(InstallmentsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])->assertSuccessful()->assertCountTableRecords(1);
    Livewire::test(UpdatesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])->assertSuccessful()->assertCountTableRecords(1);
    $this->get("/admin/projects/{$project->id}/edit")->assertOk();
});
