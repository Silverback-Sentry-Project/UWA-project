<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compensation_claims', function (Blueprint $table) {
            $table->id('claim_id');
            $table->foreignId('incident_id')->unique()->constrained('incidents', 'incident_id');
            $table->foreignId('claimant_id')->constrained('users', 'user_id');
            $table->decimal('estimated_amount', 15, 2)->nullable();
            $table->enum('claim_status', ['Submitted', 'Under Review', 'Approved', 'Rejected', 'Paid'])->default('Submitted');
            $table->foreignId('reviewed_by')->nullable()->constrained('users', 'user_id');
            $table->foreignId('approved_by')->nullable()->constrained('users', 'user_id');
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('claim_status', 'idx_claim_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_claims');
    }
};
