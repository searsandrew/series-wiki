<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('series_id')->constrained('sw_series')->cascadeOnDelete();

            $table->string('slug')->index(); // species-default
            $table->string('name'); // Species (Default)
            $table->string('entry_type')->nullable()->index(); // species/faction/ship/etc (null = generic)
            $table->boolean('is_default')->default(false)->index();

            $table->json('settings')->nullable();

            $table->timestamps();

            $table->unique(['series_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_templates');
    }
};