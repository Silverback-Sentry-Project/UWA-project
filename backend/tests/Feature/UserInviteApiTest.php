<?php

namespace Tests\Feature;

use App\Mail\PersonnelInviteMail;
use App\Models\Park;
use App\Models\Role;
use App\Models\User;
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

        Role::create(['role_name' => 'System Administrator']);
        Role::create(['role_name' => 'UWA Official']);
        Role::create(['role_name' => 'Ranger']);

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

        $park = Park::create(['park_name' => 'Bwindi', 'district' => 'Kanungu']);
        $roleId = Role::where('role_name', 'Ranger')->value('role_id');

        $response = $this->postJson('/api/users/invite', [
            'first_name' => 'New',
            'last_name' => 'Ranger',
            'email' => 'new-ranger@example.com',
            'role_id' => $roleId,
            'park_id' => $park->park_id,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'new-ranger@example.com', 'park_id' => $park->park_id]);
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
            'email' => 'offline-mail@example.com',
            'role_id' => $roleId,
        ], ['Authorization' => "Bearer $this->adminToken"]);

        $response->assertStatus(201);
        $this->assertFalse($response->json('mail_sent'));
        $this->assertDatabaseHas('users', ['email' => 'offline-mail@example.com']);
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
