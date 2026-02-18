<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_factions', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('series_id')->constrained('sw_series')->cascadeOnDelete();

            $table->string('slug')->index(); // republic, confederacy, rog-republic
            $table->string('name');
            $table->unsignedInteger('sort')->default(0)->index();
            $table->json('meta')->nullable(); // colors/icons/etc (host app decides meaning)

            $table->timestamps();

            $table->unique(['series_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_factions');
    }
};