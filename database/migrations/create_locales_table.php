<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Tipi\Localization\Config\LocalizationConfig;

return new class extends Migration
{
    public function up(): void
    {
        $locale = new (app(LocalizationConfig::class)->localeModel);
        $connection = $locale->getConnection();
        $driver = $connection->getDriverName();
        $name = $locale->getTable();
        $oneDefault = "{$name}_one_default";

        if (! in_array($driver, ['sqlite', 'mysql', 'mariadb', 'pgsql'], true)) {
            throw new RuntimeException(
                "The locales table does not support the [{$driver}] database driver. Supported drivers: sqlite, mysql, mariadb, pgsql.",
            );
        }

        $connection->getSchemaBuilder()->create($name, function (Blueprint $table) use ($driver, $oneDefault) {
            $table->string('code', 16)->primary();
            $table->string('name');
            $table->string('native_name');
            $table->string('native_abbreviation', 3)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('text_direction', 3)->default('ltr');
            $table->unsignedInteger('position')->default(0)->index();
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('is_default')->default(false);

            // At most one default locale. MySQL and MariaDB have no partial
            // indexes, so index a column that is NULL for every other row.
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $table->boolean('default_guard')
                    ->nullable()
                    ->storedAs('CASE WHEN is_default = 1 THEN 1 ELSE NULL END')
                    ->unique($oneDefault);
            }
        });

        if ($driver === 'pgsql' || $driver === 'sqlite') {
            $grammar = $connection->getQueryGrammar();

            $connection->statement(sprintf(
                'CREATE UNIQUE INDEX %s ON %s (is_default) WHERE is_default = %s',
                $grammar->wrap($oneDefault),
                $grammar->wrapTable($name),
                $driver === 'pgsql' ? 'true' : '1',
            ));
        }
    }

    public function down(): void
    {
        $locale = new (app(LocalizationConfig::class)->localeModel);

        $locale->getConnection()->getSchemaBuilder()->dropIfExists($locale->getTable());
    }
};
