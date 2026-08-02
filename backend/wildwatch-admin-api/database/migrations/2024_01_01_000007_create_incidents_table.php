<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id('incident_id');
            $table->foreignId('reported_by')->constrained('users', 'user_id');
            $table->foreignId('park_id')->constrained('parks', 'park_id');
            $table->enum('incident_type', [
                'Wildlife Sighting', 'Crop Damage', 'Livestock Loss',
                'Property Damage', 'Human Injury', 'Human Fatality',
            ]);
            $table->text('description');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('village', 150)->nullable();
            $table->enum('status', ['New', 'Assigned', 'In Progress', 'Resolved', 'Escalated'])->default('New');
            $table->timestamp('created_at')->useCurrent();

            $table->index('park_id', 'idx_incidents_park');
            $table->index('status', 'idx_incidents_status');
            $table->index('created_at', 'idx_incidents_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
