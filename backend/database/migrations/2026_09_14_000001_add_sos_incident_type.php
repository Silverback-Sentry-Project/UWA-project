<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->enum('incident_type', [
                'Wildlife Sighting', 'Crop Damage', 'Livestock Loss',
                'Property Damage', 'Human Injury', 'Human Fatality',
                'SOS',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->enum('incident_type', [
                'Wildlife Sighting', 'Crop Damage', 'Livestock Loss',
                'Property Damage', 'Human Injury', 'Human Fatality',
            ])->change();
        });
    }
};
