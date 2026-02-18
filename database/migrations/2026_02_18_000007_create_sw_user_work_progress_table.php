<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_user_work_progress', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Host app user IDs are unknown type; we store as string for max compatibility (works for ULID, UUID, and integer user IDs)
            $table->string('user_id')->index();

            $table->foreignUlid('work_id')->constrained('sw_works')->cascadeOnDelete();

            $table->unsignedInteger('max_gate_position')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'work_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_user_work_progress');
    }
};