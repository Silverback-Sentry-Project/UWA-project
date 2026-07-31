<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_form_submissions', function (Blueprint $table) {
            $table->id('submission_id');
            $table->foreignId('form_id')->constrained('evidence_forms', 'form_id')->cascadeOnDelete();
            $table->foreignId('park_id')->constrained('parks', 'park_id')->cascadeOnDelete();

            // Residents don't have portal accounts yet, so submitter identity is
            // captured as free text for now rather than a user_id foreign key.
            $table->string('submitted_by_name', 255)->nullable();
            $table->string('submitted_by_contact', 255)->nullable();

            $table->enum('status', ['Submitted', 'Verified', 'Forwarded', 'Rejected'])->default('Submitted');
            $table->foreignId('verified_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->text('verification_notes')->nullable();

            $table->timestamps();

            $table->index(['park_id', 'status'], 'idx_evidence_submissions_park_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_form_submissions');
    }
};
