<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_status_history', function (Blueprint $table) {
            $table->id('status_history_id');
            $table->foreignId('incident_id')->constrained('incidents', 'incident_id')->cascadeOnDelete();
            $table->foreignId('updated_by')->constrained('users', 'user_id');
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50);
            $table->text('remarks')->nullable();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_status_history');
    }
};
