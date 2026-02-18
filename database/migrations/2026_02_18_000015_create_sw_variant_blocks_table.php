<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_variant_blocks', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('variant_id')->constrained('sw_entry_variants')->cascadeOnDelete();

            $table->string('key')->index();
            $table->string('label')->nullable();
            $table->string('format')->default('markdown');

            $table->longText('body_safe')->nullable();
            $table->longText('body_full')->nullable();

            $table->string('locked_mode')->default('safe');

            $table->foreignUlid('required_gate_id')
                ->nullable()
                ->constrained('sw_gates')
                ->nullOnDelete();

            $table->unsignedInteger('sort')->default(0)->index();

            $table->timestamps();

            $table->index(['variant_id', 'key', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_variant_blocks');
    }
};