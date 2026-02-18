<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_entry_time_slices', function (Blueprint $table) {
            $table->foreignUlid('entry_id')->constrained('sw_entries')->cascadeOnDelete();
            $table->foreignUlid('time_slice_id')->constrained('sw_time_slices')->cascadeOnDelete();

            $table->primary(['entry_id', 'time_slice_id']);
            $table->index(['time_slice_id', 'entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_entry_time_slices');
    }
};