<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Models\Amenity;
use App\Models\Faq;
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

        $faqs = [
            ['Buying', 'How do I request a site visit?', 'Open any property or project page and use the "Request a site visit" form. Choose a preferred date and time and share your contact details. Our team will confirm the final schedule with you.'],
            ['Buying', 'What does the Verified badge mean?', 'A Verified badge appears only when our team has recorded how and when a listing, project or developer was checked. If something has not been verified, we do not label it as verified.'],
            ['Buying', 'Are the prices final?', 'Prices are provided by owners and developers and may be negotiable or change. Each listing shows whether the price is negotiable and when it was last updated. Please confirm final terms before committing.'],
            ['Buying', 'Can I compare properties?', 'Yes. Tick "Compare" on up to four property cards and open the comparison page to see price, size, price per sqft, bedrooms, amenities and more side by side.'],
            ['Selling & renting', 'How do I list my property?', 'Create a free account and use "Submit Property". Our team reviews every submission before it appears on the website. You can also use the Sell or Rent out forms if you prefer that we contact you first.'],
            ['Selling & renting', 'Is there a fee to submit a property?', 'Please contact us to discuss our current terms for selling and rental assistance.'],
            ['Privacy', 'Who can see my contact details?', 'Only authorised staff can see the details you submit in inquiry forms. They are never displayed publicly. See our Privacy Policy for more.'],
            ['General', 'Do I need an account?', 'No. You can browse, send inquiries and request site visits without an account. An account lets you keep favorites on all devices, save searches and track your inquiries.'],
        ];
        foreach ($faqs as $i => [$cat, $q, $a]) {
            Faq::firstOrCreate(['question' => $q], ['category' => $cat, 'answer' => $a, 'sort_order' => $i, 'is_published' => true]);
        }

        $country = Location::firstOrCreate(['path' => 'bangladesh'], ['type' => LocationType::Country, 'name' => 'Bangladesh', 'name_bn' => 'বাংলাদেশ']);
        foreach (['Dhaka' => 'ঢাকা', 'Chattogram' => 'চট্টগ্রাম', 'Rajshahi' => 'রাজশাহী', 'Khulna' => 'খুলনা', 'Barishal' => 'বরিশাল', 'Sylhet' => 'সিলেট', 'Rangpur' => 'রংপুর', 'Mymensingh' => 'ময়মনসিংহ'] as $name => $bn) {
            Location::firstOrCreate(['path' => 'bangladesh/'.str($name)->slug().'-division'], [
                'parent_id' => $country->id, 'type' => LocationType::Division, 'name' => $name.' Division', 'name_bn' => $bn.' বিভাগ',
            ]);
        }
    }
}
