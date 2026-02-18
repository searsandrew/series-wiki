<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_template_sections', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('template_id')->constrained('sw_templates')->cascadeOnDelete();

            $table->string('key')->index(); // overview, history, biology
            $table->string('label')->nullable(); // Overview, History, Biology
            $table->string('format')->default('markdown');

            // Optional starter content
            $table->longText('body_safe')->nullable();
            $table->longText('body_full')->nullable();

            // Default locked behavior for blocks created from this section
            $table->string('locked_mode')->default('safe');

            // Optional gate requirement for this section (rare)
            $table->foreignUlid('required_gate_id')
                ->nullable()
                ->constrained('sw_gates')
                ->nullOnDelete();

            $table->unsignedInteger('sort')->default(0)->index();

            $table->timestamps();

            $table->index(['template_id', 'key', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_template_sections');
    }
};