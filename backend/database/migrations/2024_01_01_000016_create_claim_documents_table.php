<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->foreignId('claim_id')->constrained('compensation_claims', 'claim_id')->cascadeOnDelete();
            $table->string('document_type', 100);
            $table->string('file_path', 255);
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_documents');
    }
};
