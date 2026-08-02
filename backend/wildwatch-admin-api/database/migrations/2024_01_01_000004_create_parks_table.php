<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parks', function (Blueprint $table) {
            $table->id('park_id');
            $table->string('park_name', 150)->unique();
            $table->string('district', 150)->nullable();
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parks');
    }
};
