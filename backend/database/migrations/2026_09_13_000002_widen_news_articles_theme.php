<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widen news_articles.theme beyond the original three-value enum (FOREST, WILDLIFE,
 * SECURITY).
 *
 * The controller has always validated all five themes the mobile ArticleTheme enum
 * defines — FOREST/WILDLIFE/SECURITY/SUNSET/SKY — but the DB column never did, so
 * publishing a SUNSET or SKY article 500'd on the real Postgres enum/check while
 * test-suite SQLite silently tolerated it (BRIDGE-CONTRACT.md:78 asserts the enum was
 * widened 2026-08-13; only the validation rule actually was).
 *
 * The column is converted to a plain varchar: app-layer validation is the single
 * source of truth for allowed themes, which is what the controller already enforces.
 * The original column is `$table->enum(...)`, which Laravel renders as a varchar +
 * CHECK constraint on Postgres (named {table}_{column}_check) and a CHECK on SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE news_articles DROP CONSTRAINT IF EXISTS news_articles_theme_check');
            DB::statement('ALTER TABLE news_articles ALTER COLUMN theme TYPE varchar(20) USING theme::varchar');
            DB::statement("ALTER TABLE news_articles ALTER COLUMN theme SET DEFAULT 'FOREST'");
            DB::statement('ALTER TABLE news_articles ALTER COLUMN theme SET NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE news_articles MODIFY theme varchar(20) NOT NULL DEFAULT 'FOREST'");
        } else {
            // SQLite can't alter CHECK constraints in place; Laravel's ->change() rebuilds
            // the table with the new (unconstrained) column definition.
            Schema::table('news_articles', function (Blueprint $table) {
                $table->string('theme', 20)->default('FOREST')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE news_articles DROP CONSTRAINT IF EXISTS news_articles_theme_check');
            DB::statement('ALTER TABLE news_articles ALTER COLUMN theme TYPE varchar(10) USING theme::varchar');
            DB::statement("ALTER TABLE news_articles ADD CONSTRAINT news_articles_theme_check CHECK (theme IN ('FOREST', 'WILDLIFE', 'SECURITY'))");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE news_articles MODIFY theme ENUM('FOREST','WILDLIFE','SECURITY') NOT NULL DEFAULT 'FOREST'");
        } else {
            Schema::table('news_articles', function (Blueprint $table) {
                $table->enum('theme', ['FOREST', 'WILDLIFE', 'SECURITY'])->default('FOREST')->change();
            });
        }
    }
};
