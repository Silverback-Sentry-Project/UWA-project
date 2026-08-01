<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('firestore_doc_id', 128)->nullable()->unique()->after('incident_id');
            $table->string('source_system', 32)->nullable()->after('status');
        });

        Schema::table('wildlife_sightings', function (Blueprint $table) {
            $table->string('firestore_doc_id', 128)->nullable()->unique()->after('sighting_id');
            $table->string('source_system', 32)->nullable()->after('notes');
            $table->string('approval_status', 32)->nullable()->after('source_system');
        });

        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->string('firestore_doc_id', 128)->nullable()->unique()->after('sos_id');
            $table->string('source_system', 32)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['firestore_doc_id', 'source_system']);
        });

        Schema::table('wildlife_sightings', function (Blueprint $table) {
            $table->dropColumn(['firestore_doc_id', 'source_system', 'approval_status']);
        });

        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->dropColumn(['firestore_doc_id', 'source_system']);
        });
    }
};
