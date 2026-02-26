<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_entry_snapshots', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('entry_id')->constrained('sw_entries')->cascadeOnDelete();

            $table->string('mode')->default('full')->index(); // 'safe' or 'full'
            $table->string('hash')->index();
            $table->longText('text');

            $table->timestamps();

            $table->index(['entry_id', 'mode', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_entry_snapshots');
    }
};