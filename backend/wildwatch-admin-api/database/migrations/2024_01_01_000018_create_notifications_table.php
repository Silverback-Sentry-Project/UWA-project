<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('message');
            $table->enum('notification_type', ['SOS', 'Incident', 'Assignment', 'Compensation', 'General']);
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'is_read'], 'idx_notifications_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
