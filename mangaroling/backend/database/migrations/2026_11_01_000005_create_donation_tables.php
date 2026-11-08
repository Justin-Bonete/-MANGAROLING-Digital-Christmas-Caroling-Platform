<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $t->string('provider', 30);
            $t->string('account_name', 150);
            $t->string('account_reference', 150);   // GCash number or bank account no. (public instructions only)
            $t->text('instructions')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestampsTz();

            $t->index(['campaign_id', 'is_active']);
        });
        DB::statement("ALTER TABLE payment_accounts ADD CONSTRAINT pa_provider_chk CHECK (provider IN ('gcash','bank_transfer'))");

        Schema::create('donations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invitation_id')->constrained()->restrictOnDelete();
            $t->foreignId('payment_account_id')->constrained('payment_accounts')->restrictOnDelete();
            $t->string('payment_method', 30);
            // BR-015: frozen copy of provider/name/reference/instructions at submission time.
            $t->jsonb('payment_account_snapshot');
            $t->decimal('amount', 12, 2);
            $t->char('currency', 3)->default('PHP');
            $t->string('reference_number', 150);
            $t->string('proof_path', 500)->nullable();   // private disk only
            $t->string('idempotency_key', 100)->nullable();
            $t->string('status', 20)->default('pending');
            $t->timestampTz('submitted_at');
            $t->timestampTz('verified_at')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampsTz();

            $t->index(['status', 'submitted_at']);
            $t->index('payment_account_id');
            $t->unique(['invitation_id', 'idempotency_key']);
            // The same real-world transaction cannot be claimed twice.
            $t->unique(['payment_method', 'reference_number']);
        });
        DB::statement('ALTER TABLE donations ADD CONSTRAINT donations_amount_chk CHECK (amount > 0)');
        DB::statement("ALTER TABLE donations ADD CONSTRAINT donations_status_chk CHECK (status IN ('pending','verified','rejected'))");
        DB::statement("ALTER TABLE donations ADD CONSTRAINT donations_method_chk CHECK (payment_method IN ('gcash','bank_transfer'))");
        DB::statement("ALTER TABLE donations ADD CONSTRAINT donations_verified_chk CHECK ((status = 'pending') OR (verified_at IS NOT NULL AND verified_by IS NOT NULL))");

        Schema::create('donation_verification_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('donation_id')->constrained()->restrictOnDelete();
            $t->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $t->string('previous_status', 20);
            $t->string('new_status', 20);
            $t->text('notes')->nullable();
            $t->timestampTz('created_at')->useCurrent();

            $t->index(['donation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_verification_logs');
        Schema::dropIfExists('donations');
        Schema::dropIfExists('payment_accounts');
    }
};
