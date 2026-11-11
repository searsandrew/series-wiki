<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sw_template_sections')) {
            $this->repairIndexes();

            return;
        }

        Schema::create('sw_template_sections', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('template_id')->constrained('sw_templates')->cascadeOnDelete();

            $table->string('key')->index(); // overview, history, biology
            $table->string('label')->nullable(); // Overview, History, Biology
            $table->string('format')->default('markdown');
            $table->string('type')->default('text')->index(); // text|image|map|chart|etc
            $table->json('data')->nullable(); // default payload for the block

            // Optional starter content
            $table->longText('body_safe')->nullable();
            $table->longText('body_full')->nullable();

            // Default locked behavior for blocks created from this section
            $table->string('locked_mode')->default('safe');

            // Optional gate requirement for this section (rare)
            $table->foreignUlid('required_gate_id')->nullable();

            $table->unsignedInteger('sort')->default(0)->index();

            $table->timestamps();

            $table->index(['template_id', 'key', 'sort']);
        });
    }

    /** Restore indexes left unfinished when MySQL rejected the original gate constraint. */
    private function repairIndexes(): void
    {
        $existing = array_column(Schema::getIndexes('sw_template_sections'), 'name');
        $indexes = [
            'sw_template_sections_key_index' => ['key'],
            'sw_template_sections_type_index' => ['type'],
            'sw_template_sections_sort_index' => ['sort'],
            'sw_template_sections_template_id_key_sort_index' => ['template_id', 'key', 'sort'],
        ];
        foreach ($indexes as $name => $columns) {
            if (! in_array($name, $existing, true)) {
                Schema::table('sw_template_sections', function (Blueprint $table) use ($name, $columns): void {
                    $table->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_template_sections');
    }
};
