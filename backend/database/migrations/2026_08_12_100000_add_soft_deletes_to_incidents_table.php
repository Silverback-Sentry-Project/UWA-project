<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            // WildWatch-Platform-Plan.md §9.2 W5: IncidentController::destroy() previously did a
            // hard delete with no record of the deletion, who did it, or why. deleted_at makes
            // deletion recoverable; deleted_by records who did it (the "why" still isn't
            // captured - if that's ever needed, extend to a full audit-log entry instead).
            $table->softDeletes()->after('is_escalated');
            $table->foreignId('deleted_by')->nullable()->after('deleted_at')
                ->constrained('users', 'user_id')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropSoftDeletes();
        });
    }
};
