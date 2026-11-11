<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasGateForeignKey = collect(Schema::getForeignKeys('sw_template_sections'))->contains(
            fn (array $key): bool => $key['columns'] === ['required_gate_id'] && $key['foreign_table'] === 'sw_gates'
        );
        if (! $hasGateForeignKey) {
            Schema::table('sw_template_sections', function (Blueprint $table): void {
                $table->foreign('required_gate_id')->references('id')->on('sw_gates')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('sw_template_sections', function (Blueprint $table): void {
            $table->dropForeign(['required_gate_id']);
        });
    }
};
