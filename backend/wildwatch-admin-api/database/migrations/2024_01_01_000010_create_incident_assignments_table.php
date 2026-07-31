<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_assignments', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->foreignId('incident_id')->constrained('incidents', 'incident_id')->cascadeOnDelete();
            $table->foreignId('ranger_id')->constrained('users', 'user_id');
            $table->foreignId('assigned_by')->nullable()->constrained('users', 'user_id');
            $table->timestamp('assigned_at')->useCurrent();
            $table->enum('assignment_status', ['Assigned', 'Accepted', 'Rejected', 'Completed'])->default('Assigned');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_assignments');
    }
};
