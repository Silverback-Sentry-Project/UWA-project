<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Park;
use App\Models\Role;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IncidentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldReceive('syncIncidentDocument')->andReturnNull();
        });

        $role = Role::create(['role_name' => 'System Administrator']);
        $this->admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('password123'),
            'account_status' => 'Active',
        ]);
        $this->admin->roles()->attach($role->role_id);
        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    public function test_can_list_incidents()
    {
        $response = $this->getJson('/api/incidents', [
            'Authorization' => "Bearer $this->token",
        ]);

        $response->assertOk();
    }

    public function test_can_create_incident()
    {
        $park = Park::create([
            'park_name' => 'Bwindi Impenetrable National Park',
            'district' => 'Kanungu',
        ]);

        $payload = [
            'reported_by' => $this->admin->user_id,
            'park_id' => $park->park_id,
            'incident_type' => 'Wildlife Sighting',
            'description' => 'A group of elephants spotted.',
            'latitude' => -1.05,
            'longitude' => 29.7,
        ];

        $response = $this->postJson('/api/incidents', $payload, [
            'Authorization' => "Bearer $this->token",
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('incidents', ['description' => 'A group of elephants spotted.']);
    }

    public function test_can_update_incident_status()
    {
        $this->withoutExceptionHandling();
        $park = Park::create([
            'park_name' => 'Bwindi',
            'district' => 'Kanungu',
        ]);

        $incident = Incident::create([
            'reported_by' => $this->admin->user_id,
            'park_id' => $park->park_id,
            'incident_type' => 'Wildlife Sighting',
            'description' => 'Test incident',
            'latitude' => 0,
            'longitude' => 0,
            'status' => 'New',
        ]);

        $response = $this->patchJson("/api/incidents/{$incident->incident_id}/status", [
            'status' => 'In Progress',
            'remarks' => 'Starting response',
        ], [
            'Authorization' => "Bearer $this->token",
        ]);

        $response->assertOk();
        $this->assertEquals('In Progress', $incident->fresh()->status);
        $this->assertDatabaseHas('incident_status_history', [
            'incident_id' => $incident->incident_id,
            'new_status' => 'In Progress',
        ]);
    }

    public function test_can_assign_ranger_to_incident()
    {
        $park = Park::create(['park_name' => 'Bwindi', 'district' => 'Kanungu']);
        $ranger = User::create([
            'first_name' => 'Ranger',
            'last_name' => 'Joe',
            'email' => 'ranger@example.com',
            'password_hash' => 'hash',
            'account_status' => 'Active',
        ]);

        $incident = Incident::create([
            'reported_by' => $this->admin->user_id,
            'park_id' => $park->park_id,
            'incident_type' => 'Wildlife Sighting',
            'description' => 'Test incident',
            'latitude' => 0,
            'longitude' => 0,
            'status' => 'New',
        ]);

        $response = $this->postJson("/api/incidents/{$incident->incident_id}/assign", [
            'ranger_id' => $ranger->user_id,
        ], [
            'Authorization' => "Bearer $this->token",
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('incident_assignments', [
            'incident_id' => $incident->incident_id,
            'ranger_id' => $ranger->user_id,
        ]);
        $this->assertEquals('Assigned', $incident->fresh()->status);
    }
}
