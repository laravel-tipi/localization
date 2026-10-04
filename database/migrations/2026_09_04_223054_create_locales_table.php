<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->string('name');
            $table->string('native_name');
            $table->string('country_code', 2)->nullable();
            $table->string('text_direction', 3)->default('ltr');
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('is_default')->default(false);

            $table->unsignedTinyInteger('default_guard')
                ->nullable()
                ->storedAs('IF(is_default = 1, 1, NULL)');

            $table->unique('default_guard');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
