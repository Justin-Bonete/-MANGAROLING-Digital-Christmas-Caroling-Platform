<?php

namespace Database\Factories;

use App\Models\CampaignRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CampaignRecipient> */
class CampaignRecipientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => \App\Models\Campaign::factory(),
            'recipient_id' => \App\Models\Recipient::factory(),
            'consent_status' => 'granted',
            'consented_at' => now(),
        ];
    }
}
