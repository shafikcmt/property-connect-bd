<?php

namespace Database\Factories;

use App\Enums\RequestType;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lead> */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->name(), 'phone' => '+88017'.fake()->numerify('########'), 'request_type' => RequestType::Contact, 'consent_given_at' => now()];
    }
}
