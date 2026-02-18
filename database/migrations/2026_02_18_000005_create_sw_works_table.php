<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_works', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('series_id')->constrained('sw_series')->cascadeOnDelete();

            $table->string('slug')->index();
            $table->string('title');
            $table->string('kind')->default('book'); // book/novella/short/etc

            $table->timestamps();

            $table->unique(['series_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_works');
    }
};