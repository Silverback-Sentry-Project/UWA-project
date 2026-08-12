<?php

namespace Tests\Feature;

use App\Mail\PersonnelInviteMail;
use App\Models\EvidenceForm;
use App\Models\EvidenceFormSubmission;
use App\Models\Incident;
use App\Models\Park;
use App\Models\Role;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GameparkApiTest extends TestCase
{
    use RefreshDatabase;

    private Park $park;
    private Park $otherPark;
    private User $gamepark;
    private string $gameparkToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldReceive('syncIncidentDocument')->andReturnNull();
        });

        Role::create(['role_name' => 'Gamepark Officer']);
        Role::create(['role_name' => 'System Administrator']);
        Role::create(['role_name' => 'Ranger']);
        Role::create(['role_name' => 'UWA Official']);

        $this->park = Park::create(['park_name' => 'Bwindi', 'district' => 'Kanungu']);
        $this->otherPark = Park::create(['park_name' => 'Kibale', 'district' => 'Kabarole']);

        $this->gamepark = User::create([
            'first_name' => 'Game', 'last_name' => 'Park', 'email' => 'gamepark@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
            'park_id' => $this->park->park_id,
        ]);
        $this->gamepark->roles()->attach(Role::where('role_name', 'Gamepark Officer')->value('role_id'));
        $this->gameparkToken = $this->gamepark->createToken('test-token')->plainTextToken;
    }

    private function adminToken(): string
    {
        $admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'User', 'email' => 'admin@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
        ]);
        $admin->roles()->attach(Role::where('role_name', 'System Administrator')->value('role_id'));

        return $admin->createToken('test-token')->plainTextToken;
    }

    // --- Forms ---

    public function test_gamepark_can_create_and_list_forms()
    {
        $response = $this->postJson('/api/gamepark/forms', [
            'title' => 'Human-Wildlife Conflict Evidence',
            'status' => 'Draft',
            'fields' => [
                ['label' => 'What happened?', 'field_type' => 'textarea', 'is_required' => true],
                ['label' => 'Photo', 'field_type' => 'image', 'is_required' => false],
            ],
        ], ['Authorization' => "Bearer $this->gameparkToken"]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('evidence_forms', ['title' => 'Human-Wildlife Conflict Evidence', 'park_id' => $this->park->park_id]);
        $this->assertDatabaseHas('evidence_form_fields', ['label' => 'Photo', 'field_type' => 'image']);

        $list = $this->getJson('/api/gamepark/forms', ['Authorization' => "Bearer $this->gameparkToken"]);
        $list->assertOk();
        $this->assertCount(1, $list->json());
    }

    public function test_non_gamepark_account_is_forbidden_from_gamepark_forms()
    {
        $response = $this->getJson('/api/gamepark/forms', ['Authorization' => 'Bearer ' . $this->adminToken()]);

        $response->assertStatus(403);
    }

    public function test_gamepark_cannot_update_another_parks_form()
    {
        $otherForm = EvidenceForm::create([
            'park_id' => $this->otherPark->park_id,
            'created_by' => $this->gamepark->user_id,
            'title' => 'Not yours',
            'status' => 'Draft',
        ]);

        $response = $this->patchJson("/api/gamepark/forms/{$otherForm->form_id}", [
            'title' => 'Hijacked',
        ], ['Authorization' => "Bearer $this->gameparkToken"]);

        $response->assertStatus(404);
    }

    public function test_gamepark_can_delete_own_form()
    {
        $form = EvidenceForm::create([
            'park_id' => $this->park->park_id,
            'created_by' => $this->gamepark->user_id,
            'title' => 'To delete',
            'status' => 'Draft',
        ]);

        $response = $this->deleteJson("/api/gamepark/forms/{$form->form_id}", [], [
            'Authorization' => "Bearer $this->gameparkToken",
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('evidence_forms', ['form_id' => $form->form_id]);
    }

    // --- Submissions ---

    public function test_gamepark_can_verify_and_forward_a_submission()
    {
        $form = EvidenceForm::create([
            'park_id' => $this->park->park_id,
            'created_by' => $this->gamepark->user_id,
            'title' => 'Evidence form',
            'status' => 'Published',
        ]);
        $submission = EvidenceFormSubmission::create([
            'form_id' => $form->form_id,
            'park_id' => $this->park->park_id,
            'submitted_by_name' => 'Resident',
            'status' => 'Submitted',
        ]);

        $verify = $this->postJson("/api/gamepark/submissions/{$submission->submission_id}/verify", [
            'decision' => 'verify',
            'notes' => 'Checks out',
        ], ['Authorization' => "Bearer $this->gameparkToken"]);

        $verify->assertOk();
        $this->assertEquals('Verified', $submission->fresh()->status);

        $forward = $this->postJson("/api/gamepark/submissions/{$submission->submission_id}/forward", [], [
            'Authorization' => "Bearer $this->gameparkToken",
        ]);

        $forward->assertOk();
        $this->assertEquals('Forwarded', $submission->fresh()->status);
        $this->assertNotNull($submission->fresh()->forwarded_at);
    }

    public function test_cannot_forward_a_submission_that_was_never_verified()
    {
        $form = EvidenceForm::create([
            'park_id' => $this->park->park_id,
            'created_by' => $this->gamepark->user_id,
            'title' => 'Evidence form',
            'status' => 'Published',
        ]);
        $submission = EvidenceFormSubmission::create([
            'form_id' => $form->form_id,
            'park_id' => $this->park->park_id,
            'status' => 'Submitted',
        ]);

        $response = $this->postJson("/api/gamepark/submissions/{$submission->submission_id}/forward", [], [
            'Authorization' => "Bearer $this->gameparkToken",
        ]);

        $response->assertStatus(409);
    }

    public function test_gamepark_cannot_access_another_parks_submission()
    {
        $otherForm = EvidenceForm::create([
            'park_id' => $this->otherPark->park_id,
            'created_by' => $this->gamepark->user_id,
            'title' => 'Other park form',
            'status' => 'Published',
        ]);
        $otherSubmission = EvidenceFormSubmission::create([
            'form_id' => $otherForm->form_id,
            'park_id' => $this->otherPark->park_id,
            'status' => 'Submitted',
        ]);

        $response = $this->postJson("/api/gamepark/submissions/{$otherSubmission->submission_id}/verify", [
            'decision' => 'verify',
        ], ['Authorization' => "Bearer $this->gameparkToken"]);

        $response->assertStatus(404);
    }

    // --- Personnel ---

    public function test_gamepark_can_list_own_park_personnel()
    {
        $ranger = User::create([
            'first_name' => 'Ranger', 'last_name' => 'Joe', 'email' => 'ranger@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
            'park_id' => $this->park->park_id,
        ]);
        $ranger->roles()->attach(Role::where('role_name', 'Ranger')->value('role_id'));

        $otherRanger = User::create([
            'first_name' => 'Other', 'last_name' => 'Ranger', 'email' => 'other-ranger@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
            'park_id' => $this->otherPark->park_id,
        ]);
        $otherRanger->roles()->attach(Role::where('role_name', 'Ranger')->value('role_id'));

        $response = $this->getJson('/api/gamepark/personnel', ['Authorization' => "Bearer $this->gameparkToken"]);

        $response->assertOk();
        $ids = collect($response->json())->pluck('user_id');
        $this->assertContains($ranger->user_id, $ids->all());
        $this->assertNotContains($otherRanger->user_id, $ids->all());
    }

    public function test_gamepark_can_invite_field_staff()
    {
        Mail::fake();

        $rangerRoleId = Role::where('role_name', 'Ranger')->value('role_id');

        $response = $this->postJson('/api/gamepark/personnel/invite', [
            'first_name' => 'New',
            'last_name' => 'Ranger',
            'email' => 'new-ranger@example.com',
            'role_id' => $rangerRoleId,
        ], ['Authorization' => "Bearer $this->gameparkToken"]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('mail_sent'));
        $this->assertDatabaseHas('users', ['email' => 'new-ranger@example.com', 'park_id' => $this->park->park_id]);
        Mail::assertSent(PersonnelInviteMail::class);
    }

    public function test_gamepark_cannot_invite_a_non_field_role()
    {
        Mail::fake();

        $uwaRoleId = Role::where('role_name', 'UWA Official')->value('role_id');

        $response = $this->postJson('/api/gamepark/personnel/invite', [
            'first_name' => 'Sneaky',
            'last_name' => 'Invite',
            'email' => 'sneaky@example.com',
            'role_id' => $uwaRoleId,
        ], ['Authorization' => "Bearer $this->gameparkToken"]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
        Mail::assertNothingSent();
    }

    // --- Incident assignment ---

    public function test_gamepark_can_assign_ranger_to_own_park_incident()
    {
        $ranger = User::create([
            'first_name' => 'Ranger', 'last_name' => 'Joe', 'email' => 'ranger2@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
            'park_id' => $this->park->park_id,
        ]);
        $ranger->roles()->attach(Role::where('role_name', 'Ranger')->value('role_id'));

        $incident = Incident::create([
            'reported_by' => $this->gamepark->user_id,
            'park_id' => $this->park->park_id,
            'incident_type' => 'Wildlife Sighting',
            'description' => 'Elephant near village',
            'latitude' => 0,
            'longitude' => 0,
            'status' => 'New',
        ]);

        $response = $this->postJson("/api/gamepark/incidents/{$incident->incident_id}/assign", [
            'ranger_id' => $ranger->user_id,
        ], ['Authorization' => "Bearer $this->gameparkToken"]);

        $response->assertStatus(201);
        $this->assertEquals('Assigned', $incident->fresh()->status);
    }

    public function test_gamepark_cannot_assign_ranger_to_another_parks_incident()
    {
        $ranger = User::create([
            'first_name' => 'Ranger', 'last_name' => 'Joe', 'email' => 'ranger3@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
            'park_id' => $this->park->park_id,
        ]);
        $ranger->roles()->attach(Role::where('role_name', 'Ranger')->value('role_id'));

        $incident = Incident::create([
            'reported_by' => $this->gamepark->user_id,
            'park_id' => $this->otherPark->park_id,
            'incident_type' => 'Wildlife Sighting',
            'description' => 'Elephant near village',
            'latitude' => 0,
            'longitude' => 0,
            'status' => 'New',
        ]);

        $response = $this->postJson("/api/gamepark/incidents/{$incident->incident_id}/assign", [
            'ranger_id' => $ranger->user_id,
        ], ['Authorization' => "Bearer $this->gameparkToken"]);

        $response->assertStatus(403);
    }
}
