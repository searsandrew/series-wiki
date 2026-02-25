<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_type_neighbors', function (Blueprint $table) {
            // Composite PK so we don’t need an id (and it’s naturally unique).
            $table->foreignUlid('series_id')->constrained('sw_series')->cascadeOnDelete();

            $table->string('type')->index();           // e.g. ship
            $table->string('neighbor_type')->index();  // e.g. battle, faction
            $table->unsignedInteger('weight')->default(100)->index();

            $table->timestamps();

            $table->primary(['series_id', 'type', 'neighbor_type']);
            $table->index(['series_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_type_neighbors');
    }
};