<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $t->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $t->string('recipient_email', 255);
            $t->string('provider_message_id', 255)->nullable();
            $t->string('status', 30);
            $t->text('error')->nullable();
            $t->timestampTz('sent_at')->nullable();
            $t->timestampTz('created_at')->useCurrent();

            $t->index(['invitation_id', 'created_at']);
        });

        // Email-level opt-out list, independent of any single campaign.
        Schema::create('suppressions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->string('email', 255);
            $t->string('reason', 50)->default('opt_out');
            $t->timestampTz('created_at')->useCurrent();
        });
        DB::statement('CREATE UNIQUE INDEX suppressions_org_email_uq ON suppressions (organization_id, lower(email))');

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('action', 100);
            $t->string('entity_type', 100);
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->jsonb('details')->nullable();
            $t->timestampTz('created_at')->useCurrent();

            $t->index(['created_at', 'user_id']);
            $t->index(['entity_type', 'entity_id']);
        });

        // BR-011: history tables are append-only, enforced by the database itself.
        DB::unprepared(<<<'SQL'
        CREATE OR REPLACE FUNCTION prevent_row_mutation() RETURNS trigger AS $$
        BEGIN
            RAISE EXCEPTION '% is append-only', TG_TABLE_NAME;
        END;
        $$ LANGUAGE plpgsql;
        SQL);
        foreach (['donation_verification_logs', 'audit_logs'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_immutable BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION prevent_row_mutation()");
        }
    }

    public function down(): void
    {
        foreach (['donation_verification_logs', 'audit_logs'] as $table) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$table}_immutable ON {$table}");
        }
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_row_mutation()');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('suppressions');
        Schema::dropIfExists('email_logs');
    }
};
