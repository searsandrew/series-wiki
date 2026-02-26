<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('series_id')->constrained('sw_series')->cascadeOnDelete();

            $table->foreignUlid('template_id')
                ->nullable()
                ->constrained('sw_templates')
                ->nullOnDelete();

            $table->string('slug')->index();
            $table->string('title');
            $table->string('type')->default('page')->index(); // species/faction/character/ship/event/etc
            $table->string('status')->default('published')->index(); // draft/published/archived
            $table->text('summary')->nullable();
            $table->json('meta')->nullable(); // structured per-entry data (infobox/search/export)

            $table->timestamps();

            $table->unique(['series_id', 'slug']);
            $table->index('template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_entries');
    }
};