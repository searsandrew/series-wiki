<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sw_series', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('slug')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sw_series');
    }
};