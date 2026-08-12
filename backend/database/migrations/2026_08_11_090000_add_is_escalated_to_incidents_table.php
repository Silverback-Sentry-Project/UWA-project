<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->boolean('is_escalated')->default(false)->after('status');
        });

        // Backfill: rows currently sitting in the (soon to be removed) 'Escalated'
        // status become is_escalated = true, with status restored to whatever
        // operational state they were in immediately before being escalated -
        // looked up from incident_status_history rather than assumed, since an
        // incident could have escalated from New, Assigned, or In Progress.
        $escalated = DB::table('incidents')->where('status', 'Escalated')->get(['incident_id']);

        foreach ($escalated as $incident) {
            $priorStatus = DB::table('incident_status_history')
                ->where('incident_id', $incident->incident_id)
                ->where('new_status', '!=', 'Escalated')
                ->orderByDesc('updated_at')
                ->orderByDesc('status_history_id')
                ->value('new_status');

            DB::table('incidents')->where('incident_id', $incident->incident_id)->update([
                'is_escalated' => true,
                'status' => $priorStatus ?? 'In Progress',
            ]);
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->enum('status', ['New', 'Assigned', 'In Progress', 'Resolved'])
                ->default('New')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->enum('status', ['New', 'Assigned', 'In Progress', 'Resolved', 'Escalated'])
                ->default('New')
                ->change();
        });

        DB::table('incidents')->where('is_escalated', true)->update(['status' => 'Escalated']);

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('is_escalated');
        });
    }
};
