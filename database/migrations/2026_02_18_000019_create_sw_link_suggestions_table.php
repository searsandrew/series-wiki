<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_link_suggestions', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Source entry where the suggestion was found
            $table->foreignUlid('entry_id')->constrained('sw_entries')->cascadeOnDelete();

            // Optional: which block key it was found in (we use key not id so it survives templating/overwrites)
            $table->string('block_key')->nullable()->index();

            // Target entry to suggest linking to
            $table->foreignUlid('suggested_entry_id')->constrained('sw_entries')->cascadeOnDelete();

            $table->string('anchor_text'); // usually the target title (matched phrase)
            $table->unsignedInteger('occurrences')->default(1);
            $table->decimal('confidence', 5, 4)->default(0.5000)->index();

            // new|accepted|dismissed
            $table->string('status')->default('new')->index();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['entry_id', 'block_key', 'suggested_entry_id', 'anchor_text'], 'sw_link_suggestions_unique');
            $table->index(['entry_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_link_suggestions');
    }
};