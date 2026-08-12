<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
        $role = Role::create(['role_name' => 'System Administrator']);
        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('password123'),
            'account_status' => 'Active',
        ]);
        $user->roles()->attach($role->role_id);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['user_id', 'full_name', 'email', 'roles']]);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('password123'),
            'account_status' => 'Active',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_non_admin_cannot_login_to_portal()
    {
        Role::create(['role_name' => 'Ranger']);
        $user = User::create([
            'first_name' => 'Ranger',
            'last_name' => 'Joe',
            'email' => 'ranger@example.com',
            'password_hash' => Hash::make('password123'),
            'account_status' => 'Active',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ranger@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_login_is_rate_limited_after_repeated_failures()
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('password123'),
            'account_status' => 'Active',
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        // 7th attempt within the same minute should be throttled, not re-checked against
        // credentials at all - regression test for WildWatch-Platform-Plan.md §9.2 W1
        // (previously unthrottled, brute-forceable).
        $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_authenticated_user_can_get_me_details()
    {
        $role = Role::create(['role_name' => 'System Administrator']);
        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('password123'),
            'account_status' => 'Active',
        ]);
        $user->roles()->attach($role->role_id);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/me', [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertOk()
            ->assertJson([
                'email' => 'admin@example.com',
                'full_name' => 'Admin User',
            ]);
    }

    public function test_user_can_logout()
    {
        $role = Role::create(['role_name' => 'System Administrator']);
        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('password123'),
            'account_status' => 'Active',
        ]);
        $user->roles()->attach($role->role_id);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertOk();
        $this->assertEmpty($user->tokens);
    }
}
