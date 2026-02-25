<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_entry_aliases', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('entry_id')->constrained('sw_entries')->cascadeOnDelete();

            $table->string('alias')->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedInteger('sort')->default(0)->index();

            $table->timestamps();

            $table->unique(['entry_id', 'alias']);
            $table->index(['alias', 'entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_entry_aliases');
    }
};