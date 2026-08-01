<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id('sos_id');
            $table->foreignId('reported_by')->constrained('users', 'user_id');
            $table->foreignId('park_id')->constrained('parks', 'park_id');
            $table->enum('emergency_type', [
                'Wildlife Attack', 'Dangerous Sighting', 'Human Injury', 'Property Threat', 'Other',
            ]);
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->enum('status', ['Pending', 'Responding', 'Resolved'])->default('Pending');
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('resolved_at')->nullable();

            $table->index('status', 'idx_sos_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sos_alerts');
    }
};
