<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Enums\Purpose;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Property> */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(4),
            'description' => fake()->paragraphs(2, true),
            'purpose' => Purpose::Sale,
            'property_type_id' => PropertyType::firstOrCreate(['name' => 'Apartment'], ['group' => 'residential'])->id,
            'location_id' => Location::factory(),
            'price' => fake()->numberBetween(30, 200) * 100000,
            'size_sqft' => fake()->numberBetween(600, 3000),
            'bedrooms' => fake()->numberBetween(1, 5),
            'bathrooms' => fake()->numberBetween(1, 4),
            'publish_status' => PublishStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['publish_status' => PublishStatus::Published, 'published_at' => now()]);
    }
}
