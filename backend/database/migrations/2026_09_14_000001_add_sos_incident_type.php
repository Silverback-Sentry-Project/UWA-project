<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add 'SOS' to incidents.incident_type (the portal can now surface mobile SOS reports,
 * which FirestoreSyncMapper folds into this enum - see BRIDGE-CONTRACT.md).
 *
 * The column is an enum, which Laravel renders as a varchar + CHECK constraint on
 * Postgres (named incidents_incident_type_check) and a plain CHECK on SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        $values = ['Wildlife Sighting', 'Crop Damage', 'Livestock Loss', 'Property Damage', 'Human Injury', 'Human Fatality', 'SOS'];
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE incidents DROP CONSTRAINT IF EXISTS incidents_incident_type_check');
            DB::statement('ALTER TABLE incidents ALTER COLUMN incident_type TYPE varchar(50) USING incident_type::varchar');
            DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_incident_type_check CHECK (incident_type IN ('Wildlife Sighting', 'Crop Damage', 'Livestock Loss', 'Property Damage', 'Human Injury', 'Human Fatality', 'SOS'))");
            DB::statement('ALTER TABLE incidents ALTER COLUMN incident_type SET NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE incidents MODIFY incident_type ENUM('Wildlife Sighting', 'Crop Damage', 'Livestock Loss', 'Property Damage', 'Human Injury', 'Human Fatality', 'SOS') NOT NULL");
        } else {
            // SQLite can't alter CHECK constraints in place; Laravel's ->change() rebuilds
            // the table with the new constrained column definition.
            Schema::table('incidents', function (Blueprint $table) use ($values) {
                $table->enum('incident_type', $values)->change();
            });
        }
    }

    public function down(): void
    {
        $values = ['Wildlife Sighting', 'Crop Damage', 'Livestock Loss', 'Property Damage', 'Human Injury', 'Human Fatality'];
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE incidents DROP CONSTRAINT IF EXISTS incidents_incident_type_check');
            DB::statement('ALTER TABLE incidents ALTER COLUMN incident_type TYPE varchar(50) USING incident_type::varchar');
            DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_incident_type_check CHECK (incident_type IN ('Wildlife Sighting', 'Crop Damage', 'Livestock Loss', 'Property Damage', 'Human Injury', 'Human Fatality'))");
            DB::statement('ALTER TABLE incidents ALTER COLUMN incident_type SET NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE incidents MODIFY incident_type ENUM('Wildlife Sighting', 'Crop Damage', 'Livestock Loss', 'Property Damage', 'Human Injury', 'Human Fatality') NOT NULL");
        } else {
            Schema::table('incidents', function (Blueprint $table) use ($values) {
                $table->enum('incident_type', $values)->change();
            });
        }
    }
};
