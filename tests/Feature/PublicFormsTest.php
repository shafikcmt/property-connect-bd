<?php

use App\Enums\PublishStatus;
use App\Enums\RoleName;
use App\Mail\NewInquiryMail;
use App\Models\BlogPost;
use App\Models\Favorite;
use App\Models\Lead;
use App\Models\NewsletterSubscriber;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\SavedSearch;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function valid(array $extra = []): array
{
    return array_merge(['name' => 'Karim Hasan', 'phone' => '01712345678', 'email' => 'Karim@Example.com', 'message' => 'Please call me', 'consent' => '1', '_t' => now()->subMinute()->timestamp], $extra);
}

it('creates a lead from a property inquiry with normalised phone, consent and attribution', function () {
    $p = Property::factory()->published()->create();
    $this->withSession(['utm' => ['utm_source' => 'facebook', 'utm_campaign' => 'launch', 'landing_url' => 'https://x.test/']])
        ->post('/inquiry', valid(['property_id' => $p->id]))->assertSessionHas('status');

    $lead = Lead::firstOrFail();
    expect($lead->phone)->toBe('+8801712345678')->and($lead->email)->toBe('karim@example.com')->and($lead->property_id)->toBe($p->id)
        ->and($lead->request_type->value)->toBe('property_inquiry')->and($lead->consent_given_at)->not->toBeNull()
        ->and($lead->utm_source)->toBe('facebook')->and($lead->ip_hash)->not->toContain('127.0.0.1');
});

it('validates inquiry input and requires consent', function () {
    $this->post('/inquiry', ['name' => '', 'phone' => '123'])->assertSessionHasErrors(['name', 'phone', 'consent']);
    $this->flushSession();
    $this->post('/inquiry', valid(['consent' => null]))->assertSessionHasErrors('consent');
    expect(Lead::count())->toBe(0);
});

it('rejects inquiries for unpublished properties', function () {
    $draft = Property::factory()->create();
    $this->post('/inquiry', valid(['property_id' => $draft->id]))->assertSessionHasErrors('property_id');
});

it('silently drops honeypot submissions and too-fast submissions', function () {
    $this->post('/inquiry', valid(['website' => 'http://spam.test']))->assertSessionHas('status');
    expect(Lead::count())->toBe(0);

    $this->flushSession();
    $this->post('/inquiry', valid(['_t' => now()->timestamp]))->assertSessionHasErrors('message');
    expect(Lead::count())->toBe(0);
});

it('rate limits repeated submissions from one phone', function () {
    foreach (range(1, 4) as $i) {
        $this->post('/inquiry', valid(['message' => "m$i"]))->assertSessionHasNoErrors();
    }
    $this->post('/inquiry', valid())->assertStatus(429);
});

it('collapses rapid duplicate inquiries into one lead', function () {
    $p = Property::factory()->published()->create();
    $this->post('/inquiry', valid(['property_id' => $p->id]));
    $this->post('/inquiry', valid(['property_id' => $p->id]));
    expect(Lead::count())->toBe(1);
});

it('creates a site visit with a lead and validates the date window', function () {
    $p = Property::factory()->published()->create();
    $this->post('/site-visit', valid(['property_id' => $p->id, 'preferred_date' => now()->addDays(3)->toDateString(), 'preferred_window' => 'morning']))->assertSessionHas('status');
    expect(SiteVisit::count())->toBe(1)->and(SiteVisit::first()->status->value)->toBe('requested')->and(Lead::first()->request_type->value)->toBe('site_visit');

    $this->post('/site-visit', valid(['property_id' => $p->id, 'preferred_date' => now()->toDateString(), 'preferred_window' => 'morning']))->assertSessionHasErrors('preferred_date');
    $this->post('/site-visit', valid(['property_id' => $p->id, 'preferred_date' => now()->addDays(90)->toDateString(), 'preferred_window' => 'noon']))->assertSessionHasErrors(['preferred_date', 'preferred_window']);
});

it('captures sell, valuation and assistance requests as typed leads', function (string $kind, string $type) {
    $this->post("/requests/$kind", valid(['area' => 'Gulshan', 'expected_price' => 5000000]))->assertSessionHas('status');
    $lead = Lead::firstOrFail();
    expect($lead->request_type->value)->toBe($type)->and($lead->meta['area'])->toBe('Gulshan');
})->with([['sell', 'sell'], ['rent-out', 'rent'], ['valuation', 'valuation'], ['assistance', 'assistance']]);

it('keeps lead contact data out of serialisation and public pages', function () {
    $lead = Lead::factory()->create();
    expect(json_encode($lead))->not->toContain($lead->phone);
    $this->get('/properties')->assertDontSee($lead->phone);
});

it('subscribes to the newsletter once and ignores the honeypot', function () {
    $this->post('/newsletter', ['email' => 'a@example.com', 'consent' => '1'])->assertSessionHas('status');
    $this->post('/newsletter', ['email' => 'a@example.com', 'consent' => '1']);
    $this->post('/newsletter', ['email' => 'bot@example.com', 'consent' => '1', 'website' => 'x']);
    $this->post('/newsletter', ['email' => 'b@example.com'])->assertSessionHasErrors('consent');
    expect(NewsletterSubscriber::count())->toBe(1);
});

it('lets guests favorite via session and merges into the account on login', function () {
    $p = Property::factory()->published()->create();
    $this->postJson("/favorites/property/{$p->id}")->assertJson(['favorited' => true, 'count' => 1]);
    $this->get('/favorites')->assertSee($p->title);

    $user = User::factory()->create(['email' => 'f@example.com']);
    $user->assignRole(RoleName::Buyer->value);
    $this->post('/login', ['email' => 'f@example.com', 'password' => 'password-1234']);

    expect(Favorite::where('user_id', $user->id)->count())->toBe(1);
    $this->postJson("/favorites/property/{$p->id}")->assertJson(['favorited' => false]);
    expect(Favorite::count())->toBe(0);
});

it('refuses to favorite hidden listings', function () {
    $this->postJson('/favorites/property/'.Property::factory()->create()->id)->assertNotFound();
});

it('limits comparison to four properties', function () {
    $ids = Property::factory()->published()->count(5)->create()->pluck('id');
    foreach ($ids->take(4) as $id) {
        $this->postJson("/compare/$id")->assertOk();
    }
    $this->postJson('/compare/'.$ids[4])->assertStatus(422);
    $this->get('/compare')->assertOk()->assertSee('Price per sqft');
    $this->postJson('/compare/'.$ids[0])->assertJson(['compared' => false, 'count' => 3]);
});

it('tracks recently viewed properties', function () {
    $p = Property::factory()->published()->create(['title' => 'Seen Recently']);
    $this->get('/properties/'.$p->slug);
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['recent' => [$p->id]])->get('/account/recently-viewed')->assertSee('Seen Recently');
});

it('scopes account data to the signed-in user', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $mine = Lead::factory()->create(['user_id' => $a->id, 'name' => 'Mine Lead']);
    Lead::factory()->create(['user_id' => $b->id, 'name' => 'Theirs']);
    $b->savedSearches ?? null;
    SavedSearch::create(['user_id' => $b->id, 'name' => 'Their search', 'filters' => ['beds' => 3]]);

    $this->actingAs($a)->get('/account/inquiries')->assertOk();
    $this->actingAs($a)->get('/account/saved-searches')->assertDontSee('Their search');
    $other = SavedSearch::first();
    $this->actingAs($a)->delete("/account/saved-searches/{$other->id}")->assertNotFound();
    expect(SavedSearch::count())->toBe(1);
    $this->post('/logout');
    $this->get('/account')->assertRedirect(route('login'));
});

it('stores a submitted property as pending review and keeps it non-public', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $type = PropertyType::firstOrCreate(['name' => 'Apartment'], ['group' => 'residential']);
    $img = tempnam(sys_get_temp_dir(), 'i').'.jpg';
    $im = imagecreatetruecolor(800, 600);
    imagejpeg($im, $img);

    $this->actingAs($owner)->post('/submit-property', [
        'title' => 'My Own Apartment', 'purpose' => 'sale', 'property_type_id' => $type->id, 'price' => 7500000, 'size_sqft' => 1200,
        'description' => str_repeat('A lovely home near everything. ', 3), 'owner_name' => 'Owner', 'owner_phone' => '01811111111', 'consent' => '1',
        'images' => [new UploadedFile($img, 'a.jpg', 'image/jpeg', null, true)],
    ])->assertRedirect(route('account.submissions'));

    $p = Property::firstWhere('title', 'My Own Apartment');
    expect($p->publish_status->value)->toBe('pending_review')->and($p->submitted_by)->toBe($owner->id)->and($p->media()->count())->toBe(1);
    $this->get('/properties/'.$p->slug)->assertNotFound();
    $this->get('/properties')->assertDontSee('My Own Apartment');
});

it('requires login and rejects bad uploads when submitting a property', function () {
    $this->get('/submit-property')->assertRedirect(route('login'));
    $u = User::factory()->create();
    $this->actingAs($u)->post('/submit-property', ['title' => 'x'])->assertSessionHasErrors(['title', 'images', 'consent']);
    $bad = UploadedFile::fake()->create('evil.php', 10, 'text/x-php');
    $this->actingAs($u)->post('/submit-property', ['images' => [$bad]])->assertSessionHasErrors('images.0');
});

it('escapes hostile text in inquiries and listings', function () {
    $p = Property::factory()->published()->create(['title' => '<script>alert(1)</script> Nice']);
    $this->get('/properties')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
});

it('renders blog markdown without raw HTML', function () {
    $post = new BlogPost(['title' => 'Safe Post', 'body' => "Hello <script>alert(1)</script>\n\n[x](javascript:alert(1))"]);
    $post->publish_status = PublishStatus::Published;
    $post->published_at = now()->subDay();
    $post->save();

    $this->get('/blog/'.$post->slug)->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('href="javascript', false);
});

it('emails staff about a new inquiry only when an address is configured, without the message body', function () {
    Mail::fake();
    $p = Property::factory()->published()->create();

    $this->post('/inquiry', valid(['property_id' => $p->id, 'phone' => '01711111111']));
    Mail::assertNothingSent();

    app(SettingsService::class)->set('leads.notify_email', 'team@example.com');
    $this->post('/inquiry', valid(['property_id' => $p->id, 'phone' => '01722222222']));

    Mail::assertSent(NewInquiryMail::class, fn ($m) => $m->hasTo('team@example.com') && ! str_contains($m->render(), 'Please call me'));
});

it('still saves the lead when the alert mail fails', function () {
    app(SettingsService::class)->set('leads.notify_email', 'team@example.com');
    Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));
    $p = Property::factory()->published()->create();

    $this->post('/inquiry', valid(['property_id' => $p->id]))->assertSessionHas('status');
    expect(Lead::count())->toBe(1);
});
