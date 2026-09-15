<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The mobile app's HOLD TO CANCEL SOS writes status "cancelled" to Firestore, which
        // the bridge maps to a Laravel SOS alert so the reporter can deactivate their own
        // alert. The legacy (pre-bridge) enum never allowed it.
        DB::statement("ALTER TABLE sos_alerts MODIFY status ENUM('Pending', 'Responding', 'Resolved', 'Cancelled') NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sos_alerts MODIFY status ENUM('Pending', 'Responding', 'Resolved') NOT NULL DEFAULT 'Pending'");
    }
};