<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_entry_variants', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('entry_id')->constrained('sw_entries')->cascadeOnDelete();

            // Optional: tie a variant to a faction (story agnostic; null means "non-faction variant")
            $table->foreignUlid('faction_id')->nullable()->constrained('sw_factions')->nullOnDelete();

            $table->string('variant_key')->index(); // republic, confederacy, neutral, historian, etc.
            $table->string('label')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedInteger('sort')->default(0)->index();

            $table->timestamps();

            $table->unique(['entry_id', 'variant_key']);
            $table->index(['entry_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_entry_variants');
    }
};