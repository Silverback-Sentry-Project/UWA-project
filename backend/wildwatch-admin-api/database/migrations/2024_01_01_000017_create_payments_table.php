<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->foreignId('claim_id')->unique()->constrained('compensation_claims', 'claim_id');
            $table->decimal('amount_paid', 15, 2);
            $table->enum('payment_method', ['Bank Transfer', 'Mobile Money', 'Cheque', 'Cash']);
            $table->string('transaction_reference', 100)->nullable();
            $table->dateTime('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
