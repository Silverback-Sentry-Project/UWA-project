<?php

namespace Tests\Feature;

use App\Models\CompensationClaim;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\Notification;
use App\Models\Park;
use App\Models\Role;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Rangers already get the equivalent of these via the mobile FCM path (see
 * BRIDGE-CONTRACT.md) - every trigger tested here is specifically about the portal's own
 * notification bell, so recipients are always portal-eligible roles.
 */
class NotificationTriggersTest extends TestCase
{
    use RefreshDatabase;

    private Park $park;

    private User $warden;

    private User $otherParkWarden;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldReceive('syncIncidentDocument')->andReturnNull();
        });

        Role::create(['role_name' => 'System Administrator']);
        Role::create(['role_name' => 'UWA Official']);
        Role::create(['role_name' => 'Park Warden']);
        Role::create(['role_name' => 'Gamepark Officer']);
        Role::create(['role_name' => 'Ranger']);

        $this->park = Park::create(['park_name' => 'Bwindi', 'district' => 'Kanungu']);
        $otherPark = Park::create(['park_name' => 'Kibale', 'district' => 'Kabarole']);

        $this->warden = User::create([
            'first_name' => 'Alice', 'last_name' => 'Warden', 'email' => 'warden@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
            'park_id' => $this->park->park_id,
        ]);
        $this->warden->roles()->attach(Role::where('role_name', 'Park Warden')->value('role_id'));

        $this->otherParkWarden = User::create([
            'first_name' => 'Bob', 'last_name' => 'Elsewhere', 'email' => 'other-warden@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
            'park_id' => $otherPark->park_id,
        ]);
        $this->otherParkWarden->roles()->attach(Role::where('role_name', 'Park Warden')->value('role_id'));
    }

    public function test_new_incident_notifies_only_that_parks_warden_with_type_incident()
    {
        Incident::create([
            'reported_by' => $this->warden->user_id,
            'park_id' => $this->park->park_id,
            'incident_type' => 'Crop Damage',
            'description' => 'Crops trampled',
            'latitude' => 0,
            'longitude' => 0,
            'village' => 'Buhoma',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->warden->user_id,
            'notification_type' => 'Incident',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->otherParkWarden->user_id,
        ]);
    }

    public function test_escalated_incident_notifies_with_type_sos()
    {
        Incident::create([
            'reported_by' => $this->warden->user_id,
            'park_id' => $this->park->park_id,
            'incident_type' => 'Crop Damage',
            'description' => 'Serious situation',
            'latitude' => 0,
            'longitude' => 0,
            'is_escalated' => true,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->warden->user_id,
            'notification_type' => 'SOS',
        ]);
    }

    public function test_human_injury_incident_notifies_with_type_sos_even_when_not_escalated()
    {
        Incident::create([
            'reported_by' => $this->warden->user_id,
            'park_id' => $this->park->park_id,
            'incident_type' => 'Human Injury',
            'description' => 'Person hurt',
            'latitude' => 0,
            'longitude' => 0,
            'is_escalated' => false,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->warden->user_id,
            'notification_type' => 'SOS',
        ]);
    }

    public function test_ranger_assignment_notifies_the_parks_warden()
    {
        $incident = Incident::create([
            'reported_by' => $this->warden->user_id,
            'park_id' => $this->park->park_id,
            'incident_type' => 'Crop Damage',
            'description' => 'Crops trampled',
            'latitude' => 0,
            'longitude' => 0,
        ]);
        Notification::query()->delete(); // clear the creation notification to isolate this test

        $ranger = User::create([
            'first_name' => 'Ranger', 'last_name' => 'Joe', 'email' => 'ranger@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
            'park_id' => $this->park->park_id,
        ]);
        $ranger->roles()->attach(Role::where('role_name', 'Ranger')->value('role_id'));

        IncidentAssignment::create([
            'incident_id' => $incident->incident_id,
            'ranger_id' => $ranger->user_id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->warden->user_id,
            'notification_type' => 'Assignment',
        ]);
    }

    public function test_claim_approval_notifies_the_claimant()
    {
        $uwaOfficial = User::create([
            'first_name' => 'Uwa', 'last_name' => 'Official', 'email' => 'uwa@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
        ]);
        $uwaOfficial->roles()->attach(Role::where('role_name', 'UWA Official')->value('role_id'));
        $token = $uwaOfficial->createToken('test-token')->plainTextToken;

        $claimant = User::create([
            'first_name' => 'Claim', 'last_name' => 'Ant', 'email' => 'claimant@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
        ]);

        $incident = Incident::create([
            'reported_by' => $claimant->user_id,
            'park_id' => $this->park->park_id,
            'incident_type' => 'Livestock Loss',
            'description' => 'Cattle killed',
            'latitude' => 0,
            'longitude' => 0,
        ]);

        $claim = CompensationClaim::create([
            'incident_id' => $incident->incident_id,
            'claimant_id' => $claimant->user_id,
            'estimated_amount' => 500000,
            'claim_status' => 'Submitted',
        ]);

        $this->postJson("/api/claims/{$claim->claim_id}/approve", [], ['Authorization' => "Bearer $token"])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $claimant->user_id,
            'notification_type' => 'Compensation',
        ]);
    }
}
