<?php

namespace Tests\Feature;

use App\Mail\PersonnelInviteMail;
use App\Models\Park;
use App\Models\Role;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserInviteApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldReceive('provisionMobileAccount')->andReturn('mock-firebase-uid');
        });

        Role::create(['role_name' => 'System Administrator']);
        Role::create(['role_name' => 'UWA Official']);
        Role::create(['role_name' => 'Ranger']);
        Role::create(['role_name' => 'Park Warden']);
        Role::create(['role_name' => 'Community Wildlife Officer']);

        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'User', 'email' => 'admin@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
        ]);
        $this->admin->roles()->attach(Role::where('role_name', 'System Administrator')->value('role_id'));
        $this->adminToken = $this->admin->createToken('test-token')->plainTextToken;
    }

    public function test_admin_can_invite_a_uwa_official_with_no_park()
    {
        Mail::fake();

        $roleId = Role::where('role_name', 'UWA Official')->value('role_id');

        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'New',
            'last_name' => 'Official',
            'email' => 'new-official@example.com',
            'role_id' => $roleId,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('mail_sent'));
        $this->assertDatabaseHas('users', ['email' => 'new-official@example.com', 'park_id' => null]);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $response->json('user.user_id'),
            'role_id' => $roleId,
        ]);
        Mail::assertSent(PersonnelInviteMail::class, fn ($mail) => $mail->recipientEmail === 'new-official@example.com');
    }

    public function test_admin_can_invite_a_ranger_pinned_to_a_park()
    {
        Mail::fake();

        $park = Park::create(['park_name' => 'Bwindi', 'district' => 'Kanungu', 'firestore_id' => 'bwindi-impenetrable']);
        $roleId = Role::where('role_name', 'Ranger')->value('role_id');

        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'New',
            'last_name' => 'Ranger',
            'email' => 'new.ranger@gmail.com',
            'role_id' => $roleId,
            'park_id' => $park->park_id,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'new.ranger@gmail.com',
            'park_id' => $park->park_id,
            'firebase_uid' => 'mock-firebase-uid',
        ]);
        Mail::assertSent(PersonnelInviteMail::class, fn ($mail) => $mail->hasMobileAccount === true);
    }

    public function test_ranger_invite_provisions_firebase_with_the_parks_firestore_id_and_lowercase_role()
    {
        Mail::fake();
        $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldReceive('provisionMobileAccount')
                ->once()
                ->with('claims.check@gmail.com', 'Claims Check', 'ranger', 'bwindi-impenetrable')
                ->andReturn('firebase-uid-123');
        });

        $park = Park::create(['park_name' => 'Bwindi', 'district' => 'Kanungu', 'firestore_id' => 'bwindi-impenetrable']);
        $roleId = Role::where('role_name', 'Ranger')->value('role_id');

        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'Claims',
            'last_name' => 'Check',
            'email' => 'claims.check@gmail.com',
            'role_id' => $roleId,
            'park_id' => $park->park_id,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'claims.check@gmail.com', 'firebase_uid' => 'firebase-uid-123']);
    }

    /**
     * Regression test: the mobile app only lets a Ranger session stand if it was
     * established via Google Sign-In with a @gmail.com address (see
     * AuthRepositoryImpl.violatesRangerSignInPolicy) - inviting a Ranger with any other
     * domain would provision an account that can never actually sign in.
     */
    public function test_ranger_invite_rejects_a_non_gmail_address()
    {
        Mail::fake();
        $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldNotReceive('provisionMobileAccount');
        });

        $roleId = Role::where('role_name', 'Ranger')->value('role_id');

        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'Wrong',
            'last_name' => 'Domain',
            'email' => 'wrong.domain@wildwatch.app',
            'role_id' => $roleId,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
        $this->assertDatabaseMissing('users', ['email' => 'wrong.domain@wildwatch.app']);
        Mail::assertNothingSent();
    }

    public function test_non_ranger_roles_are_not_restricted_to_gmail_addresses()
    {
        Mail::fake();

        $roleId = Role::where('role_name', 'UWA Official')->value('role_id');

        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'Any',
            'last_name' => 'Domain',
            'email' => 'any.domain@wildwatch.app',
            'role_id' => $roleId,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'any.domain@wildwatch.app']);
    }

    public function test_park_warden_invite_also_gets_a_mobile_account()
    {
        Mail::fake();

        $roleId = Role::where('role_name', 'Park Warden')->value('role_id');

        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'New',
            'last_name' => 'Warden',
            'email' => 'new-warden@example.com',
            'role_id' => $roleId,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'new-warden@example.com', 'firebase_uid' => 'mock-firebase-uid']);
    }

    public function test_inviting_a_role_with_no_mobile_equivalent_skips_firebase_entirely()
    {
        Mail::fake();
        $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldNotReceive('provisionMobileAccount');
        });

        $roleId = Role::where('role_name', 'Community Wildlife Officer')->value('role_id');

        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'Community',
            'last_name' => 'Officer',
            'email' => 'cwo-admin@example.com',
            'role_id' => $roleId,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'cwo-admin@example.com', 'firebase_uid' => null]);
        Mail::assertSent(PersonnelInviteMail::class, fn ($mail) => $mail->hasMobileAccount === false);
    }

    public function test_ranger_invite_rolls_back_the_mysql_row_when_firebase_provisioning_fails()
    {
        Mail::fake();
        $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldReceive('provisionMobileAccount')->andThrow(new \RuntimeException('Firebase unreachable'));
        });

        $roleId = Role::where('role_name', 'Ranger')->value('role_id');

        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'Doomed',
            'last_name' => 'Ranger',
            'email' => 'doomed.ranger@gmail.com',
            'role_id' => $roleId,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(500);
        $this->assertDatabaseMissing('users', ['email' => 'doomed.ranger@gmail.com']);
        Mail::assertNothingSent();
    }

    public function test_invite_fails_when_email_already_taken()
    {
        Mail::fake();

        $roleId = Role::where('role_name', 'Ranger')->value('role_id');

        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'Dup',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'role_id' => $roleId,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(422);
        Mail::assertNothingSent();
    }

    public function test_invite_fails_with_missing_fields()
    {
        $response = $this->postJson('/api/users/invite', [
            'email' => 'incomplete@example.com',
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'last_name', 'role_id']);
    }

    public function test_invite_fails_with_invalid_role_id()
    {
        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'New',
            'last_name' => 'Person',
            'email' => 'invalid-role@example.com',
            'role_id' => 9999,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'invalid-role@example.com']);
    }

    public function test_account_created_even_when_mail_sending_fails()
    {
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('SMTP unreachable'));

        $roleId = Role::where('role_name', 'Ranger')->value('role_id');

        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'Offline',
            'last_name' => 'Mail',
            'email' => 'offline.mail@gmail.com',
            'role_id' => $roleId,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(201);
        $this->assertFalse($response->json('mail_sent'));
        $this->assertDatabaseHas('users', ['email' => 'offline.mail@gmail.com']);
    }

    public function test_invite_requires_authentication()
    {
        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'New',
            'last_name' => 'Person',
            'email' => 'noauth@example.com',
            'role_id' => Role::where('role_name', 'Ranger')->value('role_id'),
        ]);

        $response->assertStatus(401);
    }
}
