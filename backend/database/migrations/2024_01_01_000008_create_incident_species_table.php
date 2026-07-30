<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_species', function (Blueprint $table) {
            $table->foreignId('incident_id')->constrained('incidents', 'incident_id')->cascadeOnDelete();
            $table->foreignId('species_id')->constrained('species', 'species_id');
            $table->integer('number_affected')->default(1);
            $table->primary(['incident_id', 'species_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_species');
    }
};
