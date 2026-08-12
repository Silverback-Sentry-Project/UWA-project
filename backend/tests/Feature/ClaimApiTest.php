<?php

namespace Tests\Feature;

use App\Models\CompensationClaim;
use App\Models\Incident;
use App\Models\Park;
use App\Models\Role;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClaimApiTest extends TestCase
{
    use RefreshDatabase;

    private User $uwaOfficial;
    private string $uwaToken;

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

        $this->uwaOfficial = User::create([
            'first_name' => 'Uwa',
            'last_name' => 'Official',
            'email' => 'uwa@example.com',
            'password_hash' => Hash::make('password123'),
            'account_status' => 'Active',
        ]);
        $this->uwaOfficial->roles()->attach(Role::where('role_name', 'UWA Official')->value('role_id'));
        $this->uwaToken = $this->uwaOfficial->createToken('test-token')->plainTextToken;
    }

    private function makeClaim(): CompensationClaim
    {
        $park = Park::create(['park_name' => 'Bwindi', 'district' => 'Kanungu']);
        $incident = Incident::create([
            'reported_by' => $this->uwaOfficial->user_id,
            'park_id' => $park->park_id,
            'incident_type' => 'Livestock Loss',
            'description' => 'Cattle killed by lion',
            'latitude' => 0,
            'longitude' => 0,
            'status' => 'Resolved',
        ]);

        return CompensationClaim::create([
            'incident_id' => $incident->incident_id,
            'claimant_id' => $this->uwaOfficial->user_id,
            'estimated_amount' => 500000,
            'claim_status' => 'Submitted',
        ]);
    }

    public function test_uwa_official_can_list_claims()
    {
        $this->makeClaim();

        $response = $this->getJson('/api/claims', ['Authorization' => "Bearer $this->uwaToken"]);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_park_warden_is_forbidden_from_claims()
    {
        $warden = User::create([
            'first_name' => 'Park', 'last_name' => 'Warden', 'email' => 'warden@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
        ]);
        $warden->roles()->attach(Role::where('role_name', 'Park Warden')->value('role_id'));
        $token = $warden->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/claims', ['Authorization' => "Bearer $token"]);

        $response->assertStatus(403);
    }

    public function test_gamepark_officer_is_forbidden_from_claims()
    {
        $park = Park::create(['park_name' => 'Kibale', 'district' => 'Kabarole']);
        $gamepark = User::create([
            'first_name' => 'Game', 'last_name' => 'Park', 'email' => 'gamepark@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active', 'park_id' => $park->park_id,
        ]);
        $gamepark->roles()->attach(Role::where('role_name', 'Gamepark Officer')->value('role_id'));
        $token = $gamepark->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/claims', ['Authorization' => "Bearer $token"]);

        $response->assertStatus(403);
    }

    public function test_can_approve_claim()
    {
        $claim = $this->makeClaim();

        $response = $this->postJson("/api/claims/{$claim->claim_id}/approve", [], [
            'Authorization' => "Bearer $this->uwaToken",
        ]);

        $response->assertOk();
        $this->assertEquals('Approved', $claim->fresh()->claim_status);
        $this->assertNotNull($claim->fresh()->approved_at);
    }

    public function test_can_reject_claim_with_reason()
    {
        $claim = $this->makeClaim();

        $response = $this->postJson("/api/claims/{$claim->claim_id}/reject", [
            'reason' => 'Insufficient evidence',
        ], ['Authorization' => "Bearer $this->uwaToken"]);

        $response->assertOk();
        $this->assertEquals('Rejected', $claim->fresh()->claim_status);
    }

    public function test_can_mark_approved_claim_as_paid()
    {
        $claim = $this->makeClaim();
        $claim->update(['claim_status' => 'Approved']);

        $response = $this->postJson("/api/claims/{$claim->claim_id}/mark-paid", [
            'amount_paid' => 450000,
            'payment_method' => 'Mobile Money',
            'transaction_reference' => 'TX123',
        ], ['Authorization' => "Bearer $this->uwaToken"]);

        $response->assertOk();
        $this->assertEquals('Paid', $claim->fresh()->claim_status);
        $this->assertDatabaseHas('payments', [
            'claim_id' => $claim->claim_id,
            'amount_paid' => 450000,
            'payment_method' => 'Mobile Money',
        ]);
    }

    public function test_cannot_approve_an_already_rejected_claim()
    {
        // Regression test for WildWatch-Platform-Plan.md §9.2 W6: previously no guard at all
        // meant a Rejected (or already-Paid) claim could be flipped back to Approved.
        $claim = $this->makeClaim();
        $claim->update(['claim_status' => 'Rejected']);

        $response = $this->postJson("/api/claims/{$claim->claim_id}/approve", [], [
            'Authorization' => "Bearer $this->uwaToken",
        ]);

        $response->assertStatus(422);
        $this->assertEquals('Rejected', $claim->fresh()->claim_status);
    }

    public function test_cannot_approve_an_already_paid_claim()
    {
        $claim = $this->makeClaim();
        $claim->update(['claim_status' => 'Paid']);

        $response = $this->postJson("/api/claims/{$claim->claim_id}/approve", [], [
            'Authorization' => "Bearer $this->uwaToken",
        ]);

        $response->assertStatus(422);
        $this->assertEquals('Paid', $claim->fresh()->claim_status);
    }

    public function test_cannot_reject_an_already_approved_claim()
    {
        $claim = $this->makeClaim();
        $claim->update(['claim_status' => 'Approved']);

        $response = $this->postJson("/api/claims/{$claim->claim_id}/reject", [
            'reason' => 'Changed my mind',
        ], ['Authorization' => "Bearer $this->uwaToken"]);

        $response->assertStatus(422);
        $this->assertEquals('Approved', $claim->fresh()->claim_status);
    }

    public function test_cannot_mark_unapproved_claim_as_paid()
    {
        $claim = $this->makeClaim();

        $response = $this->postJson("/api/claims/{$claim->claim_id}/mark-paid", [
            'amount_paid' => 100000,
            'payment_method' => 'Cash',
        ], ['Authorization' => "Bearer $this->uwaToken"]);

        $response->assertStatus(422);
    }
}
