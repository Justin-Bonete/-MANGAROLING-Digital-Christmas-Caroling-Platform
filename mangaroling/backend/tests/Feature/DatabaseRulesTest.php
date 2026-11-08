<?php

namespace Tests\Feature;

use App\Models\{AuditLog, Campaign, CampaignRecipient, Donation, DonationVerificationLog, Invitation, Organization, Recipient, Song, User};
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Verifies the business rules enforced by PostgreSQL itself (Phase 2). */
class DatabaseRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_end_must_be_after_start(): void
    {
        $this->expectException(QueryException::class);
        Campaign::factory()->create(['starts_at' => now()->addDays(5), 'ends_at' => now()->addDay()]);
    }

    public function test_recipient_email_unique_per_organization_case_insensitive(): void
    {
        $org = Organization::factory()->create();
        Recipient::factory()->create(['organization_id' => $org->id, 'email' => 'Maria@Example.com']);

        $this->expectException(QueryException::class);
        Recipient::factory()->create(['organization_id' => $org->id, 'email' => 'maria@example.com']);
    }

    public function test_same_email_allowed_in_different_organizations(): void
    {
        Recipient::factory()->create(['email' => 'shared@example.com']);
        Recipient::factory()->create(['email' => 'shared@example.com']);
        $this->assertSame(2, Recipient::where('email', 'shared@example.com')->count());
    }

    public function test_recipient_cannot_be_assigned_to_same_campaign_twice(): void
    {
        $cr = CampaignRecipient::factory()->create();
        $this->expectException(QueryException::class);
        CampaignRecipient::factory()->create(['campaign_id' => $cr->campaign_id, 'recipient_id' => $cr->recipient_id]);
    }

    public function test_only_one_live_invitation_per_campaign_recipient(): void
    {
        $cr = CampaignRecipient::factory()->create();
        Invitation::factory()->create(['campaign_recipient_id' => $cr->id]);

        $this->expectException(QueryException::class);
        Invitation::factory()->create(['campaign_recipient_id' => $cr->id]);
    }

    public function test_revoked_invitation_allows_replacement(): void
    {
        $cr = CampaignRecipient::factory()->create();
        Invitation::factory()->create(['campaign_recipient_id' => $cr->id, 'status' => 'revoked', 'revoked_at' => now()]);
        Invitation::factory()->create(['campaign_recipient_id' => $cr->id]);
        $this->assertSame(2, Invitation::where('campaign_recipient_id', $cr->id)->count());
    }

    public function test_token_hash_helper_and_access_rules(): void
    {
        $this->assertSame(64, strlen(Invitation::hashToken('abc')));
        $this->assertTrue(Invitation::factory()->make()->grantsAccess());
        $this->assertFalse(Invitation::factory()->make(['expires_at' => now()->subMinute()])->grantsAccess());
        $this->assertFalse(Invitation::factory()->make(['status' => 'revoked'])->grantsAccess());
    }

    public function test_donation_amount_must_be_positive(): void
    {
        $this->expectException(QueryException::class);
        Donation::factory()->create(['amount' => 0]);
    }

    public function test_same_transaction_reference_cannot_be_claimed_twice(): void
    {
        Donation::factory()->create(['reference_number' => 'REF123']);
        $this->expectException(QueryException::class);
        Donation::factory()->create(['reference_number' => 'REF123']);
    }

    public function test_non_pending_donation_requires_reviewer(): void
    {
        $this->expectException(QueryException::class);
        Donation::factory()->create(['status' => 'verified']);
    }

    public function test_official_totals_exclude_pending_and_rejected(): void
    {
        $reviewer = User::factory()->create();
        Donation::factory()->create(['amount' => 100, 'status' => 'pending']);
        Donation::factory()->create(['amount' => 200, 'status' => 'rejected', 'verified_at' => now(), 'verified_by' => $reviewer->id]);
        Donation::factory()->create(['amount' => 300, 'status' => 'verified', 'verified_at' => now(), 'verified_by' => $reviewer->id]);

        $this->assertSame('300.00', number_format((float) Donation::official()->sum('amount'), 2, '.', ''));
    }

    public function test_verification_history_is_immutable(): void
    {
        $reviewer = User::factory()->create();
        $log = DonationVerificationLog::create([
            'donation_id' => Donation::factory()->create()->id, 'verified_by' => $reviewer->id,
            'previous_status' => 'pending', 'new_status' => 'verified',
        ]);

        $this->expectException(QueryException::class);
        $log->update(['notes' => 'tampered']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = AuditLog::create(['action' => 'test', 'entity_type' => 'x']);
        $this->expectException(QueryException::class);
        $log->delete();
    }

    public function test_song_eligibility_requires_active_and_licensed(): void
    {
        $this->assertTrue(Song::factory()->make()->isEligible());
        $this->assertFalse(Song::factory()->make(['is_active' => false])->isEligible());
        $this->assertFalse(Song::factory()->make(['license_status' => 'unknown'])->isEligible());
    }

    public function test_campaign_eligible_songs_scope(): void
    {
        $campaign = Campaign::factory()->create();
        $campaign->songs()->attach([
            Song::factory()->create()->id,
            Song::factory()->create(['license_status' => 'unlicensed'])->id,
            Song::factory()->create(['is_active' => false])->id,
        ]);
        $this->assertCount(1, $campaign->eligibleSongs()->get());
    }
}
