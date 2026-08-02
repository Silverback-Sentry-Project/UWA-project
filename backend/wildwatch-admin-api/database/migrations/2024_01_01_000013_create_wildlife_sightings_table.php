<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wildlife_sightings', function (Blueprint $table) {
            $table->id('sighting_id');
            $table->foreignId('ranger_id')->constrained('users', 'user_id');
            $table->foreignId('species_id')->constrained('species', 'species_id');
            $table->foreignId('park_id')->constrained('parks', 'park_id');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('number_seen')->default(1);
            $table->text('notes')->nullable();
            $table->timestamp('sighting_time')->useCurrent();

            $table->index('park_id', 'idx_sightings_park');
            $table->index('species_id', 'idx_sightings_species');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wildlife_sightings');
    }
};
