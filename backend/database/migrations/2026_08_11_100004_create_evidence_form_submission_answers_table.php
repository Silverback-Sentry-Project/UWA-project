<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_form_submission_answers', function (Blueprint $table) {
            $table->id('answer_id');
            $table->foreignId('submission_id')->constrained('evidence_form_submissions', 'submission_id')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('evidence_form_fields', 'field_id')->cascadeOnDelete();
            $table->text('value')->nullable(); // text/number/date/select answer
            $table->string('image_path', 500)->nullable(); // stored evidence photo, when field_type is 'image'
            $table->timestamps();

            $table->index('submission_id', 'idx_evidence_answers_submission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_form_submission_answers');
    }
};
