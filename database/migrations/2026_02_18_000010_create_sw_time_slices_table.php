<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_time_slices', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('series_id')->constrained('sw_series')->cascadeOnDelete();

            $table->string('slug')->index(); // stellar-era, year-4201, first-war
            $table->string('name'); // Stellar Era, Year 4201, The First War
            $table->string('kind')->default('era')->index(); // year|range|era

            // Inclusive range (start_year <= end_year)
            $table->integer('start_year')->index();
            $table->integer('end_year')->index();

            $table->unsignedInteger('sort')->default(0)->index();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['series_id', 'slug']);
            $table->index(['series_id', 'start_year', 'end_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_time_slices');
    }
};