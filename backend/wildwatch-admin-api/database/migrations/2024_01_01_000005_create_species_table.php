<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('species', function (Blueprint $table) {
            $table->id('species_id');
            $table->string('common_name', 100)->unique();
            $table->string('scientific_name', 150)->nullable();
            $table->string('conservation_status', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('species');
    }
};
