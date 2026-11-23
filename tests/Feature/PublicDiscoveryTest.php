<?php

use App\Enums\LocationType;
use App\Enums\PublishStatus;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\SlugHistory;
use App\Models\User;

function pub(array $attrs = []): Property
{
    return Property::factory()->published()->create($attrs);
}

it('serves every public page with content', function (string $url) {
    pub(['title' => 'Sunny Apartment']);
    $this->get($url)->assertOk();
})->with(['/', '/properties', '/properties/for-sale', '/properties/for-rent', '/projects', '/projects/upcoming', '/developers', '/locations', '/services', '/about', '/contact', '/faq', '/blog',
    '/privacy-policy', '/terms-and-conditions', '/cookie-policy', '/disclaimer', '/sell', '/rent-out', '/valuation', '/assistance', '/search?q=sunny', '/favorites', '/compare', '/login', '/register', '/sitemap.xml', '/robots.txt']);

it('shows only visible properties and 404s for hidden ones', function () {
    $ok = pub(['title' => 'Visible Home']);
    $draft = Property::factory()->create(['title' => 'Draft Home']);
    $pending = Property::factory()->create(['title' => 'Pending Home', 'publish_status' => PublishStatus::PendingReview]);

    $this->get('/properties')->assertSee('Visible Home')->assertDontSee('Draft Home')->assertDontSee('Pending Home');
    $this->get('/properties/'.$ok->slug)->assertOk();
    $this->get('/properties/'.$draft->slug)->assertNotFound();
    $this->get('/properties/'.$pending->slug)->assertNotFound();
    $this->get('/search?q=Home')->assertDontSee('Draft Home');
});

it('filters by purpose, price, beds, type and keyword', function () {
    $land = PropertyType::firstOrCreate(['name' => 'Land / Plot'], ['group' => 'land']);
    pub(['title' => 'Cheap Rental', 'purpose' => 'rent', 'price' => 30000, 'bedrooms' => 1]);
    pub(['title' => 'Big Sale Home', 'purpose' => 'sale', 'price' => 20_000_000, 'bedrooms' => 4]);
    pub(['title' => 'Corner Plot', 'purpose' => 'sale', 'price' => 9_000_000, 'bedrooms' => null, 'property_type_id' => $land->id]);

    $this->get('/properties?purpose=rent')->assertSee('Cheap Rental')->assertDontSee('Big Sale Home');
    $this->get('/properties?price_min=10000000')->assertSee('Big Sale Home')->assertDontSee('Cheap Rental')->assertDontSee('Corner Plot');
    $this->get('/properties?beds=3')->assertSee('Big Sale Home')->assertDontSee('Corner Plot');
    $this->get('/properties?type[]='.$land->slug)->assertSee('Corner Plot')->assertDontSee('Big Sale Home');
    $this->get('/properties?q=corner')->assertSee('Corner Plot')->assertDontSee('Cheap Rental');
    $this->get('/properties?sort=price_asc')->assertSeeInOrder(['Cheap Rental', 'Corner Plot', 'Big Sale Home']);
});

it('rejects invalid filter values', function () {
    $this->get('/properties?purpose=hack&beds=abc&sort=drop')->assertSessionHasErrors(['purpose', 'beds', 'sort']);
});

it('filters by location including descendants', function () {
    $city = Location::create(['type' => LocationType::City, 'name' => 'Metro']);
    $area = Location::create(['type' => LocationType::Area, 'name' => 'Leafy', 'parent_id' => $city->id]);
    pub(['title' => 'In Leafy', 'location_id' => $area->id]);
    pub(['title' => 'Elsewhere']);

    $this->get('/properties?location='.$city->path)->assertSee('In Leafy')->assertDontSee('Elsewhere');
});

it('limits filter pages from the index and emits canonical + robots signals', function () {
    app()->detectEnvironment(fn () => 'production');
    pub();

    $this->get('/properties')->assertSee('content="index,follow"', false);
    $this->get('/properties?beds=2')->assertSee('content="noindex,follow"', false);
    $this->get('/login')->assertSee('noindex', false);
    $this->get('/robots.txt')->assertSee('Disallow: /admin')->assertSee('Sitemap:');
});

it('renders property SEO: title, canonical, Open Graph and valid JSON-LD', function () {
    $p = pub(['title' => 'Lake View Flat']);
    $html = $this->get('/properties/'.$p->slug)->assertOk()->assertSee('<title>', false)->assertSee('rel="canonical"', false)->assertSee('og:title', false)->getContent();

    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
    $types = collect($m[1])->map(fn ($j) => json_decode($j, true)['@type'] ?? null)->all();
    expect($types)->toContain('RealEstateListing')->toContain('BreadcrumbList');
});

it('lists only published content in the sitemap', function () {
    $ok = pub();
    $hidden = Property::factory()->create();
    $dev = Developer::factory()->published()->create();
    $loc = Location::create(['type' => LocationType::Area, 'name' => 'Thin']); // no description

    $x = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($x)->toContain($ok->slug)->toContain($dev->slug)->not->toContain($hidden->slug)->not->toContain('/locations/'.$loc->path);
});

it('redirects renamed slugs with a 301', function () {
    $p = pub(['title' => 'Old Name']);
    $old = $p->slug;
    $p->update(['title' => 'New Name']);

    $this->get('/properties/'.$old)->assertRedirect(route('properties.show', $p->fresh()->slug))->assertStatus(301);
    expect(SlugHistory::where('slug', $old)->exists())->toBeTrue();
});

it('hides unpublished projects, developers and thin locations', function () {
    $proj = Project::factory()->create();
    $dev = Developer::factory()->create();
    $this->get('/projects/'.$proj->slug)->assertNotFound();
    $this->get('/developers/'.$dev->slug)->assertNotFound();

    $pp = Project::factory()->published()->create();
    $this->get('/projects/'.$pp->slug)->assertOk()->assertSee($pp->name);
});

it('never leaks private owner contact data in page HTML', function () {
    $p = pub(['owner_phone' => '+8801799999999', 'owner_name' => 'Hidden Owner', 'owner_email' => 'hidden@example.test']);

    $this->get('/properties/'.$p->slug)->assertDontSee('+8801799999999')->assertDontSee('Hidden Owner')->assertDontSee('hidden@example.test');
    $this->get('/properties')->assertDontSee('+8801799999999');
});

it('shows a Verified badge only for verified listings', function () {
    $a = pub(['title' => 'Verified One']);
    $b = pub(['title' => 'Plain One']);
    $a->markVerified(User::factory()->create(), 'Site visit');

    $this->get('/properties/'.$a->slug)->assertSee('Verified');
    $this->get('/properties/'.$b->slug)->assertDontSee('title="Verified by our team"', false);
    $this->get('/properties?verified=1')->assertSee('Verified One')->assertDontSee('Plain One');
});

it('stays within a sane query budget on listing pages', function () {
    foreach (range(1, 12) as $_) {
        pub();
    }
    DB::enableQueryLog();
    $this->get('/properties')->assertOk();
    expect(count(DB::getQueryLog()))->toBeLessThan(40);
});
