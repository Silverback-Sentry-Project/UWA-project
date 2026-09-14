<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two accuracy/data-model fixes from the 2026-09-13 security & correctness pass
 * (IMPROVEMENT-REPORT.md / BRIDGE-CONTRACT.md known gaps):
 *
 * 1. Add the `severity` column to incidents. Mobile already writes severity to
 *    Firestore (FirestoreSyncMapper now maps it) but the portal column never existed,
 *    so the portal could not triage incoming incidents by severity at all.
 * 2. Make incident/sighting/SOS coordinates nullable. FirestoreSyncMapper used to
 *    fabricate (0.0, 0.0) for missing coordinates (phantom "null island" incidents),
 *    and now deliberately stores NULL instead. The columns must allow NULL to receive it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->enum('severity', ['low', 'medium', 'high', 'light'])->default('medium')->after('incident_type');
            $table->decimal('latitude', 10, 8)->nullable()->change();
            $table->decimal('longitude', 11, 8)->nullable()->change();
        });

        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->change();
            $table->decimal('longitude', 11, 8)->nullable()->change();
        });

        Schema::table('wildlife_sightings', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->change();
            $table->decimal('longitude', 11, 8)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('severity');
            $table->decimal('latitude', 10, 8)->nullable(false)->change();
            $table->decimal('longitude', 11, 8)->nullable(false)->change();
        });

        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable(false)->change();
            $table->decimal('longitude', 11, 8)->nullable(false)->change();
        });

        Schema::table('wildlife_sightings', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable(false)->change();
            $table->decimal('longitude', 11, 8)->nullable(false)->change();
        });
    }
};
