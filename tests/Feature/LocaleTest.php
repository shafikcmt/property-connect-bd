<?php

use App\Http\Middleware\LocalizeDigits;
use App\Models\Faq;
use App\Models\Location;
use App\Models\Project;
use App\Models\PropertyType;
use App\Support\Money;
use Database\Seeders\ReferenceDataSeeder;

function bn(): array
{
    return ['pcbd_locale' => 'bn'];
}

it('shows English by default and Bangla after choosing it, remembering the choice in a cookie', function () {
    $this->get('/')->assertOk()->assertSee('Find a home you will love,')->assertSee('lang="en"', false);

    $res = $this->from(url('/about'))->get('/language/bn')->assertRedirect(url('/about'));
    $res->assertCookie('pcbd_locale', 'bn', false);

    $this->withUnencryptedCookies(bn())->get('/')->assertOk()->assertSee('lang="bn"', false)->assertSee('এমন বাসা খুঁজুন যা আপনার ভালো লাগবে,')->assertDontSee('Find a home you will love');
});

it('rejects unknown languages and never redirects to another site', function () {
    $this->get('/language/fr')->assertNotFound();
    $this->withHeader('referer', 'https://evil.example/page')->get('/language/bn')->assertRedirect(route('home'));
});

it('follows the visitor language on error pages and keeps the admin panel English', function () {
    $this->withUnencryptedCookies(bn())->get('/no-such-page')->assertNotFound()->assertSee('lang="bn"', false);
    $this->withUnencryptedCookies(bn())->get('/admin/login')->assertOk()->assertSee('lang="en"', false);
    $this->withUnencryptedCookies(bn())->get('/erp/login')->assertOk()->assertSee('lang="en"', false);
    $this->get('/robots.txt')->assertOk();
});

it('can default to Bangla through PCBD_DEFAULT_LOCALE', function () {
    config(['pcbd.default_locale' => 'bn']);

    $this->get('/')->assertSee('lang="bn"', false);
});

it('turns digits into Bangla digits in visible text only', function () {
    $html = '<p class="h-5 w-10" data-x="12">১২ and 34 &#039;5&amp;</p><script>var a = 12;</script><textarea>78</textarea><a href="/p/9?x=1">Room 9</a>';

    expect(LocalizeDigits::convert($html))->toBe('<p class="h-5 w-10" data-x="12">১২ and ৩৪ &#039;৫&amp;</p><script>var a = 12;</script><textarea>78</textarea><a href="/p/9?x=1">Room ৯</a>');
});

it('writes prices with lakh and crore in the visitor language', function () {
    expect(Money::short(6_500_000))->toBe('৳ 65 Lac')->and(Money::short(14_000_000))->toBe('৳ 1.4 Cr');

    app()->setLocale('bn');
    expect(Money::short(6_500_000))->toBe('৳ ৬৫ লক্ষ')->and(Money::short(14_000_000))->toBe('৳ ১.৪ কোটি');
});

it('uses Bangla content where it exists and falls back to English where it does not', function () {
    $project = Project::factory()->published()->create(['name' => 'Green Court', 'name_bn' => 'গ্রিন কোর্ট', 'summary' => 'English summary', 'summary_bn' => null]);

    $this->get("/projects/{$project->slug}")->assertSee('Green Court')->assertDontSee('গ্রিন কোর্ট');
    $this->withUnencryptedCookies(bn())->get("/projects/{$project->slug}")->assertSee('গ্রিন কোর্ট')->assertDontSee('Green Court</h1>', false);

    app()->setLocale('bn');
    expect($project->fresh()->tr('summary'))->toBe('English summary');
});

it('translates seeded reference data, FAQs and area names', function () {
    $this->seed(ReferenceDataSeeder::class);
    $city = Location::factory()->create(['name' => 'Dhaka', 'name_bn' => 'ঢাকা']);
    app()->setLocale('bn');

    expect(Location::find($city->id)->displayName())->toBe('ঢাকা')
        ->and(PropertyType::firstWhere('name', 'Apartment')->displayName())->toBe('অ্যাপার্টমেন্ট')
        ->and(Faq::firstWhere('question', 'Do I need an account?')->tr('question', true))->toBe('আমার কি অ্যাকাউন্ট লাগবে?');
});

it('serves the Bangla legal pages and Bangla validation messages', function () {
    $this->withUnencryptedCookies(bn())->get('/privacy-policy')->assertOk()->assertSee('গোপনীয়তা নীতি')->assertSee('যে তথ্য আমরা সংগ্রহ করি');

    $this->withUnencryptedCookies(bn())->post('/inquiry', ['_t' => now()->subMinute()->timestamp])->assertSessionHasErrors();
    app()->setLocale('bn');
    expect(__('validation.required', ['attribute' => __('validation.attributes.phone')]))->toBe('ফোন নম্বর দেওয়া আবশ্যক।');
});

it('has a Bangla translation for every translatable string used on the public site', function () {
    $bn = json_decode(file_get_contents(lang_path('bn.json')), true);
    $missing = [];
    foreach (['views' => resource_path('views'), 'app' => app_path()] as $dir) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.php') || str_contains($file->getPathname(), 'Filament') || str_contains($file->getPathname(), 'Console')) {
                continue;
            }
            preg_match_all("/__\(\s*'((?:[^'\\\\]|\\\\.)*)'/", file_get_contents($file->getPathname()), $m);
            foreach ($m[1] as $key) {
                $key = str_replace("\\'", "'", $key);
                if (! isset($bn[$key]) && ! str_contains($key, '.') && ! in_array($key, ['PUT', 'DELETE', 'D, M j, Y'], true) && ! str_starts_with($key, ':')) {
                    $missing[$key] = true;
                }
            }
        }
    }

    expect(array_keys($missing))->toBe([]);
});
