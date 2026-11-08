<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->restrictOnDelete();
            $t->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $t->string('title', 200);
            $t->text('description')->nullable();
            $t->string('theme', 50)->default('classic');
            $t->string('status', 20)->default('draft');
            $t->boolean('donations_enabled')->default(true);
            $t->text('donation_message')->nullable();
            $t->timestampTz('starts_at');
            $t->timestampTz('ends_at');
            $t->timestampsTz();

            $t->index(['organization_id', 'status']);
            $t->index('manager_id');
        });
        DB::statement('ALTER TABLE campaigns ADD CONSTRAINT campaigns_dates_chk CHECK (ends_at > starts_at)');
        DB::statement("ALTER TABLE campaigns ADD CONSTRAINT campaigns_status_chk CHECK (status IN ('draft','scheduled','active','paused','completed','archived'))");

        // Open question from the spec resolved: unique per organization (case-insensitive), not global.
        Schema::create('recipients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->restrictOnDelete();
            $t->string('name', 150);
            $t->string('email', 255);
            $t->string('nickname', 100)->nullable();
            $t->string('relationship', 100)->nullable();
            $t->timestampsTz();
        });
        DB::statement('CREATE UNIQUE INDEX recipients_org_email_uq ON recipients (organization_id, lower(email))');

        Schema::create('campaign_recipients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $t->foreignId('recipient_id')->constrained()->restrictOnDelete();
            $t->string('group_label', 100)->nullable();
            $t->string('consent_status', 30)->default('pending');
            $t->timestampTz('consented_at')->nullable();
            $t->timestampTz('opted_out_at')->nullable();   // suppression: never email when set
            $t->jsonb('contact_preferences')->nullable();
            $t->timestampTz('created_at')->nullable();

            $t->unique(['campaign_id', 'recipient_id']);
        });
        DB::statement("ALTER TABLE campaign_recipients ADD CONSTRAINT cr_consent_chk CHECK (consent_status IN ('pending','granted','withdrawn','opted_out'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('recipients');
        Schema::dropIfExists('campaigns');
    }
};
