<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable: only set for Gamepark portal accounts, scoping them to one park.
            // UWA main-portal staff leave this null (they aren't tied to a single park).
            $table->foreignId('park_id')->nullable()->after('user_id')
                ->constrained('parks', 'park_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('park_id');
        });
    }
};
