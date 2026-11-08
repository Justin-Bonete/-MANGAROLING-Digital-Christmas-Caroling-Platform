<?php

namespace Database\Factories;

use App\Models\PaymentAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PaymentAccount> */
class PaymentAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => \App\Models\Campaign::factory(),
            'provider' => 'gcash',
            'account_name' => fake()->name(),
            'account_reference' => '09'.fake()->numerify('#########'),
            'instructions' => 'Send via GCash, then enter the reference number.',
            'is_active' => true,
        ];
    }
}
