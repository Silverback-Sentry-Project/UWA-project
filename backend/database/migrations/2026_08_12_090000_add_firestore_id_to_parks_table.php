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
        Schema::table('parks', function (Blueprint $table) {
            // Mirrors the firestore_doc_id columns added to incidents/wildlife_sightings/sos_alerts
            // (2026_08_01_140000_add_bridge_sync_fields.php) - parks was the one bridged entity still
            // matched by fuzzy park_name string comparison (FirestoreSyncMapper::resolveParkId)
            // instead of a real shared identifier.
            $table->string('firestore_id', 128)->nullable()->unique()->after('park_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parks', function (Blueprint $table) {
            $table->dropColumn('firestore_id');
        });
    }
};
