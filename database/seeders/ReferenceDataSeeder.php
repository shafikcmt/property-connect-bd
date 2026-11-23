<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Models\Amenity;
use App\Models\Location;
use App\Models\PropertyType;
use Illuminate\Database\Seeder;

/** Production-safe, idempotent reference data (no listings, no demo content). */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['Apartment', 'residential'], ['Flat', 'residential'], ['Duplex', 'residential'], ['Penthouse', 'residential'],
            ['Residential House', 'residential'], ['Villa', 'residential'], ['Land / Plot', 'land'],
            ['Commercial Property', 'commercial'], ['Office', 'commercial'], ['Shop', 'commercial'],
            ['Industrial Property', 'industrial'], ['Warehouse', 'industrial'],
        ];
        foreach ($types as $i => [$name, $group]) {
            PropertyType::firstOrCreate(['name' => $name], ['group' => $group, 'sort_order' => $i, 'is_active' => true]);
        }

        $amenities = [
            'Building' => ['Lift / Elevator', 'Generator backup', 'Security & CCTV', 'Gated community', 'Rooftop terrace', 'Community hall'],
            'Lifestyle' => ['Gymnasium', 'Swimming pool', 'Children\'s play area', 'Garden', 'Prayer space', 'Indoor games'],
            'Utilities' => ['Gas supply', 'Water reserve', 'Intercom', 'Fire safety', 'Car parking', 'Visitor parking'],
        ];
        foreach ($amenities as $group => $names) {
            foreach ($names as $name) {
                Amenity::firstOrCreate(['name' => $name], ['group' => $group, 'is_active' => true]);
            }
        }

        $country = Location::firstOrCreate(['path' => 'bangladesh'], ['type' => LocationType::Country, 'name' => 'Bangladesh', 'name_bn' => 'বাংলাদেশ']);
        foreach (['Dhaka' => 'ঢাকা', 'Chattogram' => 'চট্টগ্রাম', 'Rajshahi' => 'রাজশাহী', 'Khulna' => 'খুলনা', 'Barishal' => 'বরিশাল', 'Sylhet' => 'সিলেট', 'Rangpur' => 'রংপুর', 'Mymensingh' => 'ময়মনসিংহ'] as $name => $bn) {
            Location::firstOrCreate(['path' => 'bangladesh/'.str($name)->slug().'-division'], [
                'parent_id' => $country->id, 'type' => LocationType::Division, 'name' => $name.' Division', 'name_bn' => $bn.' বিভাগ',
            ]);
        }
    }
}
