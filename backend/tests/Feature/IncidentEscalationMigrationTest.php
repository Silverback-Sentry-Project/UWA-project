<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IncidentEscalationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_sets_is_escalated_and_restores_prior_status_from_history()
    {
        [$parkId, $userId] = $this->rebuildIncidentsTableToPreMigrationSchema();

        $withHistoryId = DB::table('incidents')->insertGetId([
            'reported_by' => $userId,
            'park_id' => $parkId,
            'incident_type' => 'Wildlife Sighting',
            'description' => 'Escalated after being assigned',
            'latitude' => 0,
            'longitude' => 0,
            'status' => 'Escalated',
            'created_at' => now(),
        ]);

        DB::table('incident_status_history')->insert([
            [
                'incident_id' => $withHistoryId,
                'updated_by' => $userId,
                'old_status' => 'New',
                'new_status' => 'Assigned',
                'updated_at' => now()->subMinutes(10),
            ],
            [
                'incident_id' => $withHistoryId,
                'updated_by' => $userId,
                'old_status' => 'Assigned',
                'new_status' => 'Escalated',
                'updated_at' => now()->subMinutes(5),
            ],
        ]);

        $withoutHistoryId = DB::table('incidents')->insertGetId([
            'reported_by' => $userId,
            'park_id' => $parkId,
            'incident_type' => 'Wildlife Sighting',
            'description' => 'Escalated with no status history',
            'latitude' => 0,
            'longitude' => 0,
            'status' => 'Escalated',
            'created_at' => now(),
        ]);

        $this->runEscalationMigration();

        $this->assertDatabaseHas('incidents', [
            'incident_id' => $withHistoryId,
            'status' => 'Assigned',
            'is_escalated' => true,
        ]);

        $this->assertDatabaseHas('incidents', [
            'incident_id' => $withoutHistoryId,
            'status' => 'In Progress',
            'is_escalated' => true,
        ]);
    }

    public function test_status_column_no_longer_accepts_escalated_after_migration()
    {
        [$parkId, $userId] = $this->rebuildIncidentsTableToPreMigrationSchema();
        $this->runEscalationMigration();

        $this->expectException(\Throwable::class);

        DB::table('incidents')->insert([
            'reported_by' => $userId,
            'park_id' => $parkId,
            'incident_type' => 'Wildlife Sighting',
            'description' => 'Should be rejected by the DB constraint',
            'latitude' => 0,
            'longitude' => 0,
            'status' => 'Escalated',
            'created_at' => now(),
        ]);
    }

    /**
     * RefreshDatabase already runs every migration, including the escalation one,
     * so `incidents` starts out on the *new* schema. Rebuild it to the schema that
     * existed immediately before that migration so a legacy 'Escalated' row can be
     * seeded, then the migration re-run in isolation against that legacy data.
     *
     * @return array{0: int, 1: int} [parkId, userId]
     */
    private function rebuildIncidentsTableToPreMigrationSchema(): array
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('incidents');
        Schema::create('incidents', function (Blueprint $table) {
            $table->id('incident_id');
            $table->string('firestore_doc_id', 128)->nullable()->unique();
            $table->unsignedBigInteger('reported_by');
            $table->unsignedBigInteger('park_id');
            $table->enum('incident_type', [
                'Wildlife Sighting', 'Crop Damage', 'Livestock Loss',
                'Property Damage', 'Human Injury', 'Human Fatality',
            ]);
            $table->text('description');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('village', 150)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('sub_county', 100)->nullable();
            $table->string('parish', 100)->nullable();
            $table->enum('status', ['New', 'Assigned', 'In Progress', 'Resolved', 'Escalated'])->default('New');
            $table->string('source_system', 32)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::enableForeignKeyConstraints();

        $parkId = DB::table('parks')->insertGetId(['park_name' => 'Test Park', 'district' => 'Test District']);
        $userId = DB::table('users')->insertGetId([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'escalation-migration-test@example.com',
            'password_hash' => 'hash',
            'account_status' => 'Active',
            'created_at' => now(),
        ]);

        return [$parkId, $userId];
    }

    private function runEscalationMigration(): void
    {
        (require database_path('migrations/2026_08_11_090000_add_is_escalated_to_incidents_table.php'))->up();
    }
}
