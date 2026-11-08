<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name', 150);
            $t->string('email', 255)->nullable();
            $t->string('status', 20)->default('active');
            $t->timestampsTz();
        });

        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name', 50)->unique();
            $t->text('description')->nullable();
        });

        // Extend the default Laravel users table (created by the skeleton migration).
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $t->boolean('is_active')->default(true);
        });

        Schema::create('user_roles', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->primary(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('organization_id');
            $t->dropColumn('is_active');
        });
        Schema::dropIfExists('roles');
        Schema::dropIfExists('organizations');
    }
};
