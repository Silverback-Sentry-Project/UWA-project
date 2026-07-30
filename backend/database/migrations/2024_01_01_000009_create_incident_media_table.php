<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_media', function (Blueprint $table) {
            $table->id('media_id');
            $table->foreignId('incident_id')->constrained('incidents', 'incident_id')->cascadeOnDelete();
            $table->enum('media_type', ['Image', 'Video', 'Audio']);
            $table->string('file_path', 255);
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_media');
    }
};
