<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Campaign> */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => \App\Models\Organization::factory(),
            'manager_id' => \App\Models\User::factory(),
            'title' => 'Christmas '.fake()->year().' '.fake()->word(),
            'description' => fake()->sentence(),
            'theme' => 'classic',
            'status' => 'draft',
            'donations_enabled' => true,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(30),
        ];
    }
}
