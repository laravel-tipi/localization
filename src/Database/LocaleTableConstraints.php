<?php

declare(strict_types=1);

namespace Tipi\Localization\Database;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LocaleTableConstraints
{
    public static function create(): void
    {
        match (DB::getDriverName()) {
            'mysql' => self::createForMySql(),
            'pgsql' => self::createForPostgreSql(),
            'sqlite' => self::createForSqlite(),

            default => throw new RuntimeException(
                'Unsupported database driver ['.DB::getDriverName().'].',
            ),
        };
    }

    public static function drop(): void
    {
        match (DB::getDriverName()) {
            'mysql' => self::dropForMySql(),
            'pgsql' => self::dropForPostgreSql(),
            'sqlite' => self::dropForSqlite(),

            default => throw new RuntimeException(
                'Unsupported database driver ['.DB::getDriverName().'].',
            ),
        };
    }

    private static function createForMySql(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE locales
            ADD COLUMN default_guard TINYINT
            GENERATED ALWAYS AS (
                CASE WHEN is_default = 1 THEN 1 ELSE NULL END
            ) STORED
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX locales_one_default
            ON locales (default_guard)
        SQL);
    }

    private static function createForPostgreSql(): void
    {
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX locales_one_default
            ON locales (is_default)
            WHERE is_default = true
        SQL);
    }

    private static function createForSqlite(): void
    {
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX locales_one_default
            ON locales (is_default)
            WHERE is_default = 1
        SQL);
    }

    private static function dropForMySql(): void
    {
        DB::statement(<<<'SQL'
            DROP INDEX locales_one_default
            ON locales
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE locales
            DROP COLUMN default_guard
        SQL);
    }

    private static function dropForPostgreSql(): void
    {
        DB::statement(<<<'SQL'
            DROP INDEX locales_one_default
        SQL);
    }

    private static function dropForSqlite(): void
    {
        DB::statement(<<<'SQL'
            DROP INDEX locales_one_default
        SQL);
    }
}
