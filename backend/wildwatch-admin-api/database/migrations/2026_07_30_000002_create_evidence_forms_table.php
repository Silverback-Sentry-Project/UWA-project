<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_forms', function (Blueprint $table) {
            $table->id('form_id');
            $table->foreignId('park_id')->constrained('parks', 'park_id')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('status', ['Draft', 'Published'])->default('Draft');
            $table->timestamps();

            $table->index('park_id', 'idx_evidence_forms_park');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_forms');
    }
};
