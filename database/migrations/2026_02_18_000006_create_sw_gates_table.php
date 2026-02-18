<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_gates', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('work_id')->constrained('sw_works')->cascadeOnDelete();

            $table->string('key'); // "1", "2", "epilogue"
            $table->unsignedInteger('position'); // ordering integer
            $table->string('label'); // "Chapter 1"

            $table->timestamps();

            $table->unique(['work_id', 'key']);
            $table->index(['work_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_gates');
    }
};