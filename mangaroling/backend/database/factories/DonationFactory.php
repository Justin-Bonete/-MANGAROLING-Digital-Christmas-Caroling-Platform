<?php

namespace Database\Factories;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Donation> */
class DonationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invitation_id' => \App\Models\Invitation::factory(),
            'payment_account_id' => \App\Models\PaymentAccount::factory(),
            'payment_method' => 'gcash',
            'payment_account_snapshot' => ['provider' => 'gcash', 'account_name' => 'Test', 'account_reference' => '09000000000'],
            'amount' => fake()->randomFloat(2, 50, 2000),
            'currency' => 'PHP',
            'reference_number' => strtoupper(Str::random(12)),
            'status' => 'pending',
            'submitted_at' => now(),
        ];
    }
}
