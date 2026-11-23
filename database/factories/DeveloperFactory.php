<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Developer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Developer> */
class DeveloperFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Developers',
            'about' => fake()->paragraph(6),
            'summary' => fake()->sentence(),
            'publish_status' => PublishStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['publish_status' => PublishStatus::Published]);
    }
}
