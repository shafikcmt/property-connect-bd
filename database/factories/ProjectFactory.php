<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Enums\PublishStatus;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'developer_id' => Developer::factory(),
            'location_id' => Location::factory(),
            'name' => fake()->unique()->words(2, true).' Residence',
            'description' => fake()->paragraphs(3, true),
            'status' => ProjectStatus::Ongoing,
            'publish_status' => PublishStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['publish_status' => PublishStatus::Published, 'published_at' => now()]);
    }
}
