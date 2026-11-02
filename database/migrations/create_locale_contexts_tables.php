<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Tipi\Localization\Config\LocalizationConfig;

return new class extends Migration
{
    public function up(): void
    {
        $context = new (app(LocalizationConfig::class)->contextModel);
        $schema = $context->getConnection()->getSchemaBuilder();

        $schema->create($context->getTable(), function (Blueprint $table) {
            $table->string('name', 64)->primary();
            $table->boolean('all_locales')->default(false);
            $table->string('default_locale', 16)->nullable();
        });

        $schema->create($context->membershipTable(), function (Blueprint $table) use ($context) {
            $table->string('context', 64);
            $table->string('locale', 16)->index();
            $table->primary(['context', 'locale']);
            $table->foreign('context')
                ->references('name')
                ->on($context->getTable())
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $context = new (app(LocalizationConfig::class)->contextModel);
        $schema = $context->getConnection()->getSchemaBuilder();

        $schema->dropIfExists($context->membershipTable());
        $schema->dropIfExists($context->getTable());
    }
};
