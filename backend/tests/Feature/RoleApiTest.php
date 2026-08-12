<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_portal_user_can_list_roles()
    {
        Role::create(['role_name' => 'System Administrator']);
        Role::create(['role_name' => 'Ranger']);

        $admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'User', 'email' => 'admin@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
        ]);
        $admin->roles()->attach(Role::where('role_name', 'System Administrator')->value('role_id'));
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/roles', ['Authorization' => "Bearer $token"]);

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_roles_endpoint_requires_authentication()
    {
        $response = $this->getJson('/api/roles');

        $response->assertStatus(401);
    }
}
