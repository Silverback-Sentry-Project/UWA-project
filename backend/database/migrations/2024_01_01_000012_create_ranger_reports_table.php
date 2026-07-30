<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranger_reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->foreignId('incident_id')->unique()->constrained('incidents', 'incident_id')->cascadeOnDelete();
            $table->foreignId('ranger_id')->constrained('users', 'user_id');
            $table->text('actions_taken');
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamp('report_date')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranger_reports');
    }
};
