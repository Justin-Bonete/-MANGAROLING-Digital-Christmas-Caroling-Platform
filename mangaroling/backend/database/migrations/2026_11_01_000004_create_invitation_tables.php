<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('campaign_recipient_id')->constrained('campaign_recipients')->cascadeOnDelete();
            // SHA-256 hex of the raw token (64 chars). Raw token is NEVER stored (BR-003).
            // Resend policy: rotate = revoke old invitation, create a new row/token.
            $t->char('token_hash', 64)->unique();
            $t->foreignId('selected_song_id')->nullable()->constrained('songs')->restrictOnDelete();
            $t->timestampTz('selected_at')->nullable();
            $t->string('status', 30)->default('draft');
            $t->timestampTz('expires_at')->nullable();
            $t->timestampTz('sent_at')->nullable();
            $t->timestampTz('delivered_at')->nullable();
            $t->timestampTz('first_opened_at')->nullable();
            $t->timestampTz('revoked_at')->nullable();
            $t->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampsTz();

            $t->index(['expires_at', 'status']);
        });
        DB::statement("ALTER TABLE invitations ADD CONSTRAINT invitations_status_chk CHECK (status IN ('draft','queued','sent','delivered','failed','opened','expired','revoked'))");
        // BR-014: at most one live invitation per campaign recipient.
        DB::statement("CREATE UNIQUE INDEX invitations_one_active_uq ON invitations (campaign_recipient_id) WHERE status NOT IN ('revoked','expired')");

        Schema::create('playback_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('song_id')->constrained()->restrictOnDelete();
            $t->string('event_type', 30);
            $t->timestampTz('created_at')->useCurrent();

            $t->index(['invitation_id', 'created_at']);
        });
        DB::statement("ALTER TABLE playback_events ADD CONSTRAINT playback_type_chk CHECK (event_type IN ('play','pause','replay','complete'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('playback_events');
        Schema::dropIfExists('invitations');
    }
};
