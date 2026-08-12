<?php

namespace Tests\Feature;

use App\Models\EvidenceForm;
use App\Models\EvidenceFormSubmission;
use App\Models\Park;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForwardedFormApiTest extends TestCase
{
    use RefreshDatabase;

    private User $uwaOfficial;
    private string $uwaToken;
    private Park $park;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['role_name' => 'UWA Official']);
        Role::create(['role_name' => 'Gamepark Officer']);

        $this->park = Park::create(['park_name' => 'Bwindi', 'district' => 'Kanungu']);

        $this->uwaOfficial = User::create([
            'first_name' => 'Uwa', 'last_name' => 'Official', 'email' => 'uwa@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
        ]);
        $this->uwaOfficial->roles()->attach(Role::where('role_name', 'UWA Official')->value('role_id'));
        $this->uwaToken = $this->uwaOfficial->createToken('test-token')->plainTextToken;
    }

    private function makeForwardedSubmission(): EvidenceFormSubmission
    {
        $form = EvidenceForm::create([
            'park_id' => $this->park->park_id,
            'created_by' => $this->uwaOfficial->user_id,
            'title' => 'Snare evidence',
            'status' => 'Published',
        ]);

        return EvidenceFormSubmission::create([
            'form_id' => $form->form_id,
            'park_id' => $this->park->park_id,
            'submitted_by_name' => 'Jane Farmer',
            'status' => 'Forwarded',
            'verified_by' => $this->uwaOfficial->user_id,
            'verified_at' => now(),
            'forwarded_at' => now(),
        ]);
    }

    public function test_uwa_official_sees_forwarded_submissions_across_parks()
    {
        $this->makeForwardedSubmission();

        $response = $this->getJson('/api/forwarded-forms', ['Authorization' => "Bearer $this->uwaToken"]);

        $response->assertOk();
        $this->assertCount(1, $response->json());
    }

    public function test_gamepark_officer_is_forbidden_from_forwarded_forms()
    {
        $gamepark = User::create([
            'first_name' => 'Game', 'last_name' => 'Park', 'email' => 'gamepark@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
            'park_id' => $this->park->park_id,
        ]);
        $gamepark->roles()->attach(Role::where('role_name', 'Gamepark Officer')->value('role_id'));
        $token = $gamepark->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/forwarded-forms', ['Authorization' => "Bearer $token"]);

        $response->assertStatus(403);
    }

    public function test_show_404s_for_a_submission_that_is_not_forwarded()
    {
        $form = EvidenceForm::create([
            'park_id' => $this->park->park_id,
            'created_by' => $this->uwaOfficial->user_id,
            'title' => 'Draft form',
            'status' => 'Draft',
        ]);
        $submission = EvidenceFormSubmission::create([
            'form_id' => $form->form_id,
            'park_id' => $this->park->park_id,
            'status' => 'Submitted',
        ]);

        $response = $this->getJson("/api/forwarded-forms/{$submission->submission_id}", [
            'Authorization' => "Bearer $this->uwaToken",
        ]);

        $response->assertStatus(404);
    }
}
