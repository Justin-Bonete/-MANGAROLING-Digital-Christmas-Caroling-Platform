<?php

namespace Database\Factories;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Invitation> */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_recipient_id' => \App\Models\CampaignRecipient::factory(),
            'token_hash' => hash('sha256', Str::random(48)),
            'status' => 'sent',
            'expires_at' => now()->addDays(30),
            'sent_at' => now(),
        ];
    }
}
