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

            $table->string('hash')->index();
            $table->longText('text'); // extracted plain-ish text used for link suggestion

            $table->timestamps();

            $table->index(['entry_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_entry_snapshots');
    }
};