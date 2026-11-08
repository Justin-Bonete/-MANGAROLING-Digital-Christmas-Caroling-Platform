<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->string('artist', 150)->nullable();
            $t->string('category', 50)->nullable();
            $t->string('file_path', 500);
            $t->unsignedInteger('duration_seconds')->nullable();
            $t->string('license_status', 30)->default('unknown');
            $t->boolean('is_active')->default(true);
            $t->timestampsTz();

            $t->index(['is_active', 'license_status']);
        });
        DB::statement("ALTER TABLE songs ADD CONSTRAINT songs_license_chk CHECK (license_status IN ('original','public_domain','licensed','unlicensed','unknown'))");

        Schema::create('campaign_songs', function (Blueprint $t) {
            $t->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $t->foreignId('song_id')->constrained()->restrictOnDelete();
            $t->primary(['campaign_id', 'song_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_songs');
        Schema::dropIfExists('songs');
    }
};
