<?php

namespace Database\Factories;

use App\Models\Recipient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Recipient> */
class RecipientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => \App\Models\Organization::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'nickname' => null,
            'relationship' => fake()->randomElement(['Ninang', 'Ninong', 'Friend', 'Relative']),
        ];
    }
}
