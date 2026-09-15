<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The mobile app's HOLD TO CANCEL SOS writes status "cancelled" to Firestore, which
        // the bridge maps to a Laravel SOS alert so the reporter can deactivate their own
        // alert. The legacy (pre-bridge) enum never allowed it.
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Laravel renders $table->enum() on Postgres as varchar + a check constraint named
            // <table>_<column>_check - add the new value, don't drop/rebuild the whole column
            // (that would rewrite the table and lose the NOT NULL default dance for no gain).
            DB::statement('ALTER TABLE sos_alerts DROP CONSTRAINT IF EXISTS sos_alerts_status_check');
            DB::statement('ALTER TABLE sos_alerts ADD CONSTRAINT sos_alerts_status_check CHECK (status IN (\'Pending\', \'Responding\', \'Resolved\', \'Cancelled\'))');
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE sos_alerts MODIFY status ENUM('Pending', 'Responding', 'Resolved', 'Cancelled') NOT NULL DEFAULT 'Pending'");
        } else {
            // SQLite can't alter CHECK constraints in place; Laravel's ->change() rebuilds
            // the table with the new constrained column definition.
            Schema::table('sos_alerts', function (Blueprint $table) {
                $table->enum('status', ['Pending', 'Responding', 'Resolved', 'Cancelled'])->default('Pending')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE sos_alerts DROP CONSTRAINT IF EXISTS sos_alerts_status_check');
            DB::statement('ALTER TABLE sos_alerts ADD CONSTRAINT sos_alerts_status_check CHECK (status IN (\'Pending\', \'Responding\', \'Resolved\'))');
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE sos_alerts MODIFY status ENUM('Pending', 'Responding', 'Resolved') NOT NULL DEFAULT 'Pending'");
        } else {
            Schema::table('sos_alerts', function (Blueprint $table) {
                $table->enum('status', ['Pending', 'Responding', 'Resolved'])->default('Pending')->change();
            });
        }
    }
};