<?php

use App\Enums\LocationType;
use App\Enums\PublishStatus;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Media;
use App\Models\Project;
use App\Models\Property;
use App\Models\User;
use App\Services\ImageProcessor;
use App\Services\ListingPublisher;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function fakeImage(int $w = 1200, int $h = 800, string $name = 'a.jpg'): UploadedFile
{
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 30, 90, 140));
    $path = tempnam(sys_get_temp_dir(), 'img').'.jpg';
    imagejpeg($im, $path);

    return new UploadedFile($path, $name, 'image/jpeg', null, true);
}

it('builds unique location paths and updates descendants when a slug changes', function () {
    $dhaka = Location::create(['type' => LocationType::City, 'name' => 'Dhaka City']);
    $gulshan = Location::create(['type' => LocationType::Area, 'name' => 'Gulshan', 'parent_id' => $dhaka->id]);
    $other = Location::create(['type' => LocationType::City, 'name' => 'Other']);
    $dupe = Location::create(['type' => LocationType::Area, 'name' => 'Gulshan', 'parent_id' => $other->id]);

    expect($gulshan->path)->toBe('dhaka-city/gulshan')
        ->and($dupe->path)->toBe('other/gulshan'); // same slug allowed under a different parent

    $dhaka->update(['name' => 'Dhaka Metro']);
    expect($gulshan->fresh()->path)->toBe('dhaka-metro/gulshan');
    expect(Location::within($dhaka->fresh())->count())->toBe(2);
});

it('keeps slugs unique and records slug history on rename', function () {
    $a = Developer::factory()->create(['name' => 'Acme Homes']);
    $b = Developer::factory()->create(['name' => 'Acme Homes']);

    expect($a->slug)->toBe('acme-homes')->and($b->slug)->toBe('acme-homes-2');

    $a->update(['name' => 'Acme Living']);
    expect($a->fresh()->slug)->toBe('acme-living')
        ->and($a->slugHistories()->pluck('slug')->all())->toBe(['acme-homes']);
});

it('assigns a public code and tracks price changes on properties', function () {
    $p = Property::factory()->create(['price' => 5_000_000]);
    expect($p->fresh()->public_code)->toStartWith('PC-');

    $p->update(['price' => 5_500_000]);
    expect($p->fresh()->price_updated_at)->not->toBeNull();
});

it('computes price per sqft only for sales with price and size', function () {
    expect(Property::factory()->make(['price' => 6_000_000, 'size_sqft' => 1200])->pricePerSqft())->toBe(5000.0)
        ->and(Property::factory()->make(['purpose' => 'rent', 'price' => 30000, 'size_sqft' => 1200])->pricePerSqft())->toBeNull();
});

it('hides private owner data from serialisation', function () {
    $p = Property::factory()->create(['owner_phone' => '+8801700000000', 'owner_name' => 'Secret']);
    expect(json_encode($p->fresh()))->not->toContain('+8801700000000')->not->toContain('Secret');
});

it('only treats unexpired featured flags as featured', function () {
    Property::factory()->published()->create(['is_featured' => true, 'featured_until' => now()->addDay()]);
    Property::factory()->published()->create(['is_featured' => true, 'featured_until' => now()->subDay()]);
    Property::factory()->published()->create(['is_featured' => true, 'featured_until' => null]);
    Property::factory()->published()->create(['is_featured' => false]);

    expect(Property::featured()->count())->toBe(2);
});

it('exposes only published, unexpired properties through the visible scope', function () {
    foreach ([PublishStatus::Draft, PublishStatus::PendingReview, PublishStatus::Rejected, PublishStatus::Archived, PublishStatus::Sold] as $s) {
        Property::factory()->create(['publish_status' => $s]);
    }
    Property::factory()->published()->create(['expires_at' => now()->subDay()]);
    $ok = Property::factory()->published()->create();
    Property::factory()->published()->create()->delete();

    expect(Property::visible()->pluck('id')->all())->toBe([$ok->id]);
});

it('processes uploads into webp derivatives, stripping the original', function () {
    Storage::fake('public');
    $p = Property::factory()->create();

    $m = app(ImageProcessor::class)->store(fakeImage(2400, 1600), $p, 'gallery', alt: 'Living room');

    expect($m->width)->toBe(2400)->and($m->alt)->toBe('Living room');
    foreach (Media::SIZES as $size) {
        Storage::disk('public')->assertExists("{$m->path}/{$size}.webp");
    }
    expect($m->url(960))->toContain('960.webp');

    $m->delete();
    Storage::disk('public')->assertMissing("{$m->path}/960.webp");
});

it('rejects non-image uploads and keeps private documents off the public disk', function () {
    Storage::fake('public');
    Storage::fake('local');
    $p = Property::factory()->create();
    $txt = tempnam(sys_get_temp_dir(), 'x');
    file_put_contents($txt, '<?php echo 1;');

    expect(fn () => app(ImageProcessor::class)->store($txt, $p))->toThrow(InvalidArgumentException::class);

    $m = app(ImageProcessor::class)->store(fakeImage(), $p, 'documents', private: true);
    expect($m->is_private)->toBeTrue()->and($m->url())->toBeNull();
    Storage::disk('local')->assertExists("{$m->path}/480.webp");
    Storage::disk('public')->assertMissing("{$m->path}/480.webp");
});

it('refuses to publish incomplete listings and lists what is missing', function () {
    $publisher = app(ListingPublisher::class);
    $p = Property::factory()->create(['description' => 'too short', 'price' => null, 'size_sqft' => null]);

    expect($publisher->problems($p))->toHaveCount(4)
        ->and(fn () => $publisher->publish($p))->toThrow(RuntimeException::class);
    expect($p->fresh()->publish_status)->toBe(PublishStatus::Draft);
});

it('publishes a complete property', function () {
    Storage::fake('public');
    $p = Property::factory()->create();
    app(ImageProcessor::class)->store(fakeImage(), $p);

    app(ListingPublisher::class)->publish($p);

    expect($p->fresh()->publish_status)->toBe(PublishStatus::Published)->and($p->fresh()->published_at)->not->toBeNull();
});

it('requires a published developer and three images to publish a project', function () {
    Storage::fake('public');
    $project = Project::factory()->create(['developer_id' => Developer::factory()->create()->id]);
    $problems = app(ListingPublisher::class)->problems($project);

    expect($problems)->toContain('The developer must be published first.')->toContain('Add at least three images.');
});

it('derives verification from records and supports revocation', function () {
    $staff = User::factory()->create();
    $d = Developer::factory()->create();

    $d->markVerified($staff, 'Document check', 'Trade licence seen');
    expect($d->fresh()->is_verified)->toBeTrue()->and($d->verifications()->count())->toBe(1);
    expect($d->fresh()->publicEstablishedYear())->toBeNull(); // no year set

    $d->update(['established_year' => 1999]);
    expect($d->fresh()->publicEstablishedYear())->toBe(1999);

    $d->revokeVerification('Licence expired');
    expect($d->fresh()->is_verified)->toBeFalse()->and($d->fresh()->publicEstablishedYear())->toBeNull()
        ->and($d->verifications()->first()->status)->toBe('revoked');
});

it('shows a project price range only when verified', function () {
    $p = Project::factory()->make(['price_from' => 5_000_000, 'price_to' => 9_000_000, 'price_verified' => false]);
    expect($p->publicPriceRange())->toBeNull();
    $p->price_verified = true;
    expect($p->publicPriceRange())->toBe(['from' => 5000000.0, 'to' => 9000000.0]);
});

it('enforces database checks on property values', function () {
    expect(fn () => Property::factory()->create(['price' => -1]))->toThrow(QueryException::class);
});

it('refuses to run the demo seeder in production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new DemoSeeder)->run())->toThrow(RuntimeException::class, 'never run in production');
});

it('publishes a complete developer (logo + about) through the publisher', function () {
    Storage::fake('public');
    $dev = Developer::factory()->create();
    $im = imagecreatetruecolor(300, 300);
    $path = tempnam(sys_get_temp_dir(), 'l').'.png';
    imagepng($im, $path);
    app(ImageProcessor::class)->store($path, $dev, 'logo');

    app(ListingPublisher::class)->publish($dev);

    expect($dev->fresh()->publish_status)->toBe(PublishStatus::Published)->and($dev->fresh()->published_at)->not->toBeNull();
});
