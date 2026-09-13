<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data-credibility metadata for the portal's reference data, borrowed from the
 * Wild-India-Atlas pattern (ranked borrowing list item 1):
 *
 * - `confidence` is a three-tier trust label on parks and species:
 *     official     - sourced from an authoritative record (UWA park registry, IUCN status)
 *     inferred     - derived/approximated (default; nothing earns "official" by default)
 *     unconfirmed  - surfaced in the portal but not yet verified
 * - `last_verified_at` records when the record was last checked, so the portal can show
 *   freshness alongside the provenance tier instead of presenting all data as equally
 *   current.
 *
 * Default is deliberately the conservative middle tier (inferred), not the strongest:
 * existing rows migrate to "inferred" and only rows explicitly written as official pass
 * that bar. last_verified_at is backfilled to now() for existing rows so no historical
 * record reads as "never verified" after this lands.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parks', function (Blueprint $table) {
            $table->enum('confidence', ['official', 'inferred', 'unconfirmed'])
                ->default('inferred')
                ->after('description');
            $table->timestamp('last_verified_at')->nullable()->after('confidence');
        });

        Schema::table('species', function (Blueprint $table) {
            $table->enum('confidence', ['official', 'inferred', 'unconfirmed'])
                ->default('inferred')
                ->after('conservation_status');
            $table->timestamp('last_verified_at')->nullable()->after('confidence');
        });

        // Treat the pre-existing canonical seed rows as verified at migration time so the
        // presence of the column never retroactively degrades them to "never checked".
        DB::table('parks')->update(['last_verified_at' => now()]);
        DB::table('species')->update(['last_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('parks', function (Blueprint $table) {
            $table->dropColumn(['confidence', 'last_verified_at']);
        });

        Schema::table('species', function (Blueprint $table) {
            $table->dropColumn(['confidence', 'last_verified_at']);
        });
    }
};