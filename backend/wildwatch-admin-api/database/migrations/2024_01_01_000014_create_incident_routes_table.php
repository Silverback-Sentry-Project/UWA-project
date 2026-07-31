<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_routes', function (Blueprint $table) {
            $table->id('route_id');
            $table->foreignId('incident_id')->constrained('incidents', 'incident_id')->cascadeOnDelete();
            $table->foreignId('ranger_id')->constrained('users', 'user_id');
            $table->longText('route_data');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_routes');
    }
};
