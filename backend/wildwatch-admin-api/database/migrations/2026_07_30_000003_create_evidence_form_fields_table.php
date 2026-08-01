<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_form_fields', function (Blueprint $table) {
            $table->id('field_id');
            $table->foreignId('form_id')->constrained('evidence_forms', 'form_id')->cascadeOnDelete();
            $table->string('label', 255);
            $table->enum('field_type', ['text', 'textarea', 'number', 'date', 'select', 'image'])->default('text');
            $table->json('options')->nullable(); // choices for 'select' fields
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['form_id', 'position'], 'idx_evidence_form_fields_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_form_fields');
    }
};
