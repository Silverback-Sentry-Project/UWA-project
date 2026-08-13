<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['role_name' => 'System Administrator']);

        $this->user = User::create([
            'first_name' => 'Notif', 'last_name' => 'Owner', 'email' => 'owner@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
        ]);
        $this->user->roles()->attach(Role::where('role_name', 'System Administrator')->value('role_id'));
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        $this->otherUser = User::create([
            'first_name' => 'Someone', 'last_name' => 'Else', 'email' => 'else@example.com',
            'password_hash' => Hash::make('password123'), 'account_status' => 'Active',
        ]);
    }

    public function test_index_returns_only_the_authenticated_users_notifications_as_a_plain_array()
    {
        Notification::create([
            'user_id' => $this->user->user_id, 'title' => 'Mine', 'message' => 'For me',
            'notification_type' => 'General',
        ]);
        Notification::create([
            'user_id' => $this->otherUser->user_id, 'title' => 'Not mine', 'message' => 'Not for me',
            'notification_type' => 'General',
        ]);

        $response = $this->getJson('/api/notifications', ['Authorization' => "Bearer $this->token"]);

        $response->assertOk();
        $body = $response->json();
        $this->assertIsArray($body);
        $this->assertCount(1, $body);
        $this->assertSame('Mine', $body[0]['title']);
    }

    public function test_index_orders_newest_first()
    {
        // created_at is deliberately not mass-assignable (see Notification::$fillable) -
        // backdate directly to make the ordering unambiguous instead.
        $older = Notification::create([
            'user_id' => $this->user->user_id, 'title' => 'Older', 'message' => '.',
            'notification_type' => 'General',
        ]);
        $older->forceFill(['created_at' => now()->subHour()])->save();

        $newer = Notification::create([
            'user_id' => $this->user->user_id, 'title' => 'Newer', 'message' => '.',
            'notification_type' => 'General',
        ]);

        $response = $this->getJson('/api/notifications', ['Authorization' => "Bearer $this->token"]);

        $response->assertOk();
        $this->assertSame($newer->notification_id, $response->json('0.notification_id'));
        $this->assertSame($older->notification_id, $response->json('1.notification_id'));
    }

    public function test_mark_read_updates_is_read()
    {
        $notification = Notification::create([
            'user_id' => $this->user->user_id, 'title' => 'Mine', 'message' => '.',
            'notification_type' => 'General',
        ]);

        $response = $this->patchJson(
            "/api/notifications/{$notification->notification_id}/read",
            [],
            ['Authorization' => "Bearer $this->token"],
        );

        $response->assertOk();
        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_cannot_mark_another_users_notification_as_read()
    {
        $notification = Notification::create([
            'user_id' => $this->otherUser->user_id, 'title' => 'Not mine', 'message' => '.',
            'notification_type' => 'General',
        ]);

        $response = $this->patchJson(
            "/api/notifications/{$notification->notification_id}/read",
            [],
            ['Authorization' => "Bearer $this->token"],
        );

        $response->assertStatus(404);
        $this->assertFalse($notification->fresh()->is_read);
    }

    public function test_notifications_require_authentication()
    {
        $response = $this->getJson('/api/notifications');

        $response->assertStatus(401);
    }
}
