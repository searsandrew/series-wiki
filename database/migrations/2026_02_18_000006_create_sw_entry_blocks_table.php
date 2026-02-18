<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_entry_blocks', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('entry_id')->constrained('sw_entries')->cascadeOnDelete();

            $table->string('key')->default('overview')->index(); // overview/history/biology/etc
            $table->string('label')->nullable(); // human friendly section title
            $table->string('format')->default('markdown'); // markdown/html/json

            // Spoiler system: safe + full
            $table->longText('body_safe')->nullable();
            $table->longText('body_full')->nullable();

            // 'safe' => show body_safe (or nothing), 'stub' => show stub text
            $table->string('locked_mode')->default('safe');

            $table->foreignUlid('required_gate_id')
                ->nullable()
                ->constrained('sw_gates')
                ->nullOnDelete();

            $table->unsignedInteger('sort')->default(0)->index();

            $table->timestamps();

            $table->index(['entry_id', 'key', 'sort']);
            $table->index(['required_gate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_entry_blocks');
    }
};