<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_blocks', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Polymorphic-ish owner (entry or entry variant)
            // We keep owner_type as a short string for package clarity.
            // Values: 'entry', 'variant'
            $table->string('owner_type')->index();
            $table->ulid('owner_id')->index();

            $table->string('key')->default('overview')->index();
            $table->string('label')->nullable();
            $table->string('format')->default('markdown');

            $table->string('type')->default('text')->index();
            $table->json('data')->nullable();

            $table->longText('body_safe')->nullable();
            $table->longText('body_full')->nullable();

            $table->string('locked_mode')->default('safe');

            $table->foreignUlid('required_gate_id')
                ->nullable()
                ->constrained('sw_gates')
                ->nullOnDelete();

            $table->unsignedInteger('sort')->default(0)->index();

            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'key']);
            $table->index(['required_gate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_blocks');
    }
};