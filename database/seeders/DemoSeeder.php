<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Enums\ProjectStatus;
use App\Enums\PublishStatus;
use App\Models\Amenity;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Services\ImageProcessor;
use Database\Seeders\Demo\DemoArt;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Clearly-labelled DEMO content so the site can be built and reviewed before real
 * listings exist. Refuses to run in production. Images are procedural artwork,
 * flagged is_demo; developer names say "Demo"; contact details use reserved
 * example domains/numbers. Nothing here is a real listing, price or verification.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder must never run in production.');
        }
        if (Developer::where('name', 'like', 'Demo %')->exists()) {
            $this->command?->info('Demo data already present — skipping.');

            return;
        }

        $this->call(ReferenceDataSeeder::class);
        $img = app(ImageProcessor::class);
        $staff = User::first() ?? User::factory()->create(['name' => 'Demo Staff', 'email' => 'demo-staff@example.test']);

        $dhakaDiv = Location::where('path', 'bangladesh/dhaka-division')->first();
        $ctgDiv = Location::where('path', 'bangladesh/chattogram-division')->first();
        $dhaka = Location::create(['parent_id' => $dhakaDiv->id, 'type' => LocationType::City, 'name' => 'Dhaka', 'name_bn' => 'ঢাকা', 'is_featured' => true,
            'description' => 'Bangladesh\'s capital and largest city, with residential neighbourhoods ranging from established central areas to fast-growing northern and eastern suburbs.']);
        $ctg = Location::create(['parent_id' => $ctgDiv->id, 'type' => LocationType::City, 'name' => 'Chattogram', 'name_bn' => 'চট্টগ্রাম', 'is_featured' => true,
            'description' => 'The country\'s main port city, known for its hills, sea access and a growing residential market.']);

        $areaDefs = [
            [$dhaka, 'Gulshan', 'গুলশান', 23.7925, 90.4078, 'An established upscale residential and commercial district popular with diplomats, executives and international businesses.'],
            [$dhaka, 'Banani', 'বনানী', 23.7937, 90.4066, 'A central residential and dining area next to Gulshan, with a mix of apartments, offices and restaurants.'],
            [$dhaka, 'Dhanmondi', 'ধানমন্ডি', 23.7461, 90.3742, 'A long-established residential neighbourhood centred on its lake, with schools, clinics and shopping nearby.'],
            [$dhaka, 'Uttara', 'উত্তরা', 23.8759, 90.3795, 'A planned northern suburb divided into numbered sectors, well connected to the airport and the metro line.'],
            [$dhaka, 'Bashundhara R/A', 'বসুন্ধরা', 23.8147, 90.4256, 'A large planned residential area on the eastern side of the city with wide roads and many apartment developments.'],
            [$dhaka, 'Mirpur', 'মিরপুর', 23.8223, 90.3654, 'A large, densely populated residential area in north-west Dhaka offering a wide range of budgets.'],
            [$ctg, 'Khulshi', 'খুলশী', 22.3633, 91.8106, 'A leafy residential neighbourhood in Chattogram popular with families.'],
            [$ctg, 'Agrabad', 'আগ্রাবাদ', 22.3269, 91.8128, 'The city\'s main commercial centre, with offices, banks and apartments.'],
        ];
        $areas = [];
        foreach ($areaDefs as [$parent, $name, $bn, $lat, $lng, $desc]) {
            $areas[$name] = Location::create(['parent_id' => $parent->id, 'type' => LocationType::Area, 'name' => $name, 'name_bn' => $bn,
                'latitude' => $lat, 'longitude' => $lng, 'description' => $desc, 'is_featured' => in_array($name, ['Gulshan', 'Banani', 'Dhanmondi', 'Uttara', 'Bashundhara R/A', 'Khulshi'], true)]);
        }

        $amenityIds = Amenity::pluck('id')->all();
        $seed = 1;
        $art = fn (Developer|Project|Property $m, string $style, int $n = 1, string $alt = 'Illustrative artwork (demo)') => collect(range(1, $n))->each(
            fn ($i) => $img->store(DemoArt::render($seed++, $style), $m, 'gallery', alt: $alt, demo: true)
        );

        $devs = [];
        foreach ([
            ['Demo Horizon Developments', 2008, true, 'Residential and mixed-use projects across Dhaka.'],
            ['Demo Riverside Builders', 1999, true, 'Mid-market apartment communities with a focus on livability.'],
            ['Demo Crescent Properties', 2014, false, 'Boutique residential buildings in established neighbourhoods.'],
            ['Demo Meridian Group', 2005, false, 'Commercial and residential developments in Chattogram and Dhaka.'],
        ] as $i => [$name, $year, $verified, $summary]) {
            $d = Developer::create([
                'name' => $name, 'summary' => $summary, 'established_year' => $year, 'email' => 'info@'.str($name)->slug().'.example.test', 'phone' => '+8801000000000', 'show_contact_publicly' => false,
                'about' => $summary.' This is clearly labelled demo content used to preview the website; it does not describe a real company, and no real developer details, prices or verification are implied.',
            ]);
            $img->store(DemoArt::render(900 + $i, 'tower', 600, 600), $d, 'logo', alt: $name.' logo (demo)', demo: true);
            $d->forceFill(['publish_status' => PublishStatus::Published, 'is_featured' => $i < 3])->save();
            if ($verified) {
                $d->markVerified($staff, 'DEMO DATA', 'Placeholder record — not a real verification.');
            }
            $devs[] = $d;
        }

        $projectDefs = [
            ['The Meridian Residences', 0, 'Gulshan', ProjectStatus::Ongoing, 62, 14, 84, '+2 years', 'tower'],
            ['Lakeview Heights', 1, 'Dhanmondi', ProjectStatus::Ready, 100, 10, 48, '-6 months', 'tower'],
            ['Skyline Gardens', 0, 'Bashundhara R/A', ProjectStatus::Upcoming, 5, 18, 120, '+3 years', 'skyline'],
            ['Crescent Court', 2, 'Banani', ProjectStatus::Ongoing, 40, 9, 36, '+1 year', 'tower'],
            ['Harbour View Towers', 3, 'Agrabad', ProjectStatus::Ready, 100, 16, 96, '-1 year', 'skyline'],
            ['Uttara Greens', 1, 'Uttara', ProjectStatus::Ongoing, 28, 12, 60, '+2 years', 'house'],
        ];
        $projects = [];
        foreach ($projectDefs as $i => [$name, $dev, $area, $status, $progress, $floors, $units, $handover, $style]) {
            $p = Project::create([
                'developer_id' => $devs[$dev]->id, 'location_id' => $areas[$area]->id, 'name' => $name, 'status' => $status,
                'construction_progress' => $progress, 'total_floors' => $floors, 'total_units' => $units, 'handover_on' => now()->modify($handover),
                'summary' => 'A demo residential project used to preview the website layout.',
                'description' => "{$name} is demo content: an illustrative project in {$area} that shows how project pages present status, unit types, amenities and developer information. All names, figures and imagery are placeholders and do not represent a real project. Replace this with verified details supplied by the developer before publishing real projects.",
                'price_from' => 6_500_000 + $i * 1_200_000, 'price_to' => 14_000_000 + $i * 2_000_000, 'price_verified' => $i < 3,
                'available_units_verified' => $i < 2, 'payment_plan' => 'Demo payment plan: booking, instalments during construction, balance at handover.',
            ]);
            $art($p, $style, 4, "{$name} — illustrative artwork (demo)");
            $p->amenities()->sync(collect($amenityIds)->shuffle()->take(8));
            foreach ([['Type A — 2 bed', 2, 2, 1100, 1250], ['Type B — 3 bed', 3, 3, 1500, 1750], ['Type C — 4 bed', 4, 4, 2100, 2400]] as $k => [$un, $bd, $ba, $smin, $smax]) {
                $ut = $p->unitTypes()->create(['name' => $un, 'bedrooms' => $bd, 'bathrooms' => $ba, 'size_sqft_min' => $smin, 'size_sqft_max' => $smax, 'sort_order' => $k,
                    'price_from' => $smin * 7200, 'price_to' => $smax * 8200]);
                foreach (range(1, 3) as $u) {
                    $p->units()->create(['unit_type_id' => $ut->id, 'unit_no' => chr(65 + $k).'-'.($u + 2)."0{$u}", 'floor' => $u + 2, 'size_sqft' => $smin, 'price' => $smin * 7200, 'status' => $u === 1 ? 'sold' : 'available']);
                }
            }
            $p->forceFill(['publish_status' => PublishStatus::Published, 'published_at' => now(), 'is_featured' => $i < 4])->save();
            if ($i < 3) {
                $p->markVerified($staff, 'DEMO DATA', 'Placeholder record — not a real verification.');
            }
            $projects[] = $p;
        }

        $types = PropertyType::pluck('id', 'name');
        $propDefs = [
            ['3 Bedroom Apartment', 'Apartment', 'Gulshan', 'sale', 18_500_000, 1850, 3, 3, 'tower', 0],
            ['Elegant 4 Bedroom Duplex', 'Duplex', 'Banani', 'sale', 32_000_000, 3100, 4, 4, 'tower', null],
            ['Cozy 2 Bedroom Flat near the Lake', 'Flat', 'Dhanmondi', 'sale', 9_800_000, 1150, 2, 2, 'tower', 1],
            ['Modern Family Apartment', 'Apartment', 'Uttara', 'sale', 8_400_000, 1300, 3, 2, 'house', 5],
            ['Sky Penthouse with Terrace', 'Penthouse', 'Gulshan', 'sale', 58_000_000, 4200, 4, 5, 'tower', 3],
            ['Residential Plot, 5 Katha', 'Land / Plot', 'Bashundhara R/A', 'sale', 21_000_000, 3600, null, null, 'skyline', null],
            ['Furnished 3 Bed Apartment for Rent', 'Apartment', 'Gulshan', 'rent', 95_000, 1900, 3, 3, 'tower', null],
            ['Spacious Flat for Rent', 'Flat', 'Mirpur', 'rent', 28_000, 1000, 2, 2, 'skyline', null],
            ['Office Space, Prime Location', 'Office', 'Banani', 'rent', 180_000, 2500, null, 2, 'skyline', null],
            ['Ground-floor Retail Shop', 'Shop', 'Agrabad', 'sale', 14_500_000, 900, null, 1, 'skyline', 4],
            ['Hillside Family Villa', 'Villa', 'Khulshi', 'sale', 45_000_000, 4800, 5, 5, 'house', null],
            ['Contemporary Residential House', 'Residential House', 'Khulshi', 'sale', 27_500_000, 3000, 4, 4, 'house', null],
            ['Compact 1 Bedroom Studio', 'Apartment', 'Dhanmondi', 'rent', 32_000, 650, 1, 1, 'tower', null],
            ['Executive 3 Bed Apartment', 'Apartment', 'Banani', 'sale', 24_000_000, 2100, 3, 3, 'tower', 3],
            ['Corner Apartment, Park View', 'Apartment', 'Bashundhara R/A', 'sale', 11_200_000, 1550, 3, 3, 'tower', 2],
            ['Warehouse with Loading Bay', 'Warehouse', 'Agrabad', 'rent', 220_000, 8000, null, 2, 'skyline', null],
        ];
        foreach ($propDefs as $i => [$title, $type, $area, $purpose, $price, $size, $beds, $baths, $style, $proj]) {
            $p = Property::create([
                'title' => $title, 'purpose' => $purpose, 'property_type_id' => $types[$type], 'location_id' => $areas[$area]->id,
                'project_id' => $proj !== null ? $projects[$proj]->id : null, 'developer_id' => $proj !== null ? $projects[$proj]->developer_id : null,
                'price' => $price, 'price_negotiable' => $i % 3 === 0, 'rent_period' => $purpose === 'rent' ? 'monthly' : null, 'size_sqft' => $size,
                'bedrooms' => $beds, 'bathrooms' => $baths, 'floor_no' => $beds ? ($i % 8) + 2 : null, 'total_floors' => $beds ? 12 : null,
                'facing' => ['south', 'east', 'north_east', 'south_east'][$i % 4], 'parking_spaces' => $beds ? 1 + $i % 2 : null,
                'furnishing' => $purpose === 'rent' ? ['furnished', 'semi', 'unfurnished'][$i % 3] : null, 'property_status' => 'ready',
                'latitude' => $areas[$area]->latitude, 'longitude' => $areas[$area]->longitude,
                'description' => "This is a demo listing used to preview how {$title} appears on the website. The figures, features and imagery are placeholders — they are not a real property, price or offer. Replace with verified details before publishing real listings.",
                'nearby' => ['Demo: supermarket — 500 m', 'Demo: school — 800 m', 'Demo: hospital — 2 km'],
            ]);
            $art($p, $style, 3 + $i % 3, "{$title} — illustrative artwork (demo)");
            $p->amenities()->sync(collect($amenityIds)->shuffle()->take(6));
            $p->forceFill(['publish_status' => PublishStatus::Published, 'published_at' => now()->subDays($i), 'is_featured' => $i < 4 || $i === 9])->save();
            if ($i % 3 === 0) {
                $p->markVerified($staff, 'DEMO DATA', 'Placeholder record — not a real verification.');
            }
        }

        $this->command?->info('Demo data created: '.Property::count().' properties, '.Project::count().' projects, '.Developer::count().' developers.');
    }
}
