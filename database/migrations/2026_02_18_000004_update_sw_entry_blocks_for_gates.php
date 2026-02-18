<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sw_entry_blocks', function (Blueprint $table) {
            if (!Schema::hasColumn('sw_entry_blocks', 'body_safe')) {
                $table->longText('body_safe')->nullable()->after('format');
            }

            if (!Schema::hasColumn('sw_entry_blocks', 'body_full')) {
                $table->longText('body_full')->nullable()->after('body_safe');
            }

            if (!Schema::hasColumn('sw_entry_blocks', 'locked_mode')) {
                $table->string('locked_mode')->default(config('series-wiki.spoilers.default_locked_mode', 'safe'));
            }

            if (!Schema::hasColumn('sw_entry_blocks', 'required_gate_id')) {
                $table->foreignId('required_gate_id')
                    ->nullable()
                    ->constrained('sw_gates')
                    ->nullOnDelete()
                    ->after('locked_mode');
            }

            $table->index(['required_gate_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sw_entry_blocks', function (Blueprint $table) {
            if (Schema::hasColumn('sw_entry_blocks', 'required_gate_id')) {
                $table->dropConstrainedForeignId('required_gate_id');
            }

            if (Schema::hasColumn('sw_entry_blocks', 'locked_mode')) {
                $table->dropColumn('locked_mode');
            }

            if (Schema::hasColumn('sw_entry_blocks', 'body_safe')) {
                $table->dropColumn('body_safe');
            }

            if (Schema::hasColumn('sw_entry_blocks', 'body_full')) {
                $table->dropColumn('body_full');
            }
        });
    }
};