<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\Role;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsArticleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldReceive('syncFeedArticle')->andReturnNull();
        });
    }

    public function test_warden_can_create_news_article()
    {
        $role = Role::create(['role_name' => 'Park Warden']);
        $user = User::create([
            'first_name' => 'Alice',
            'last_name' => 'Warden',
            'email' => 'alice@example.com',
            'password_hash' => 'hash',
            'account_status' => 'Active',
        ]);
        $user->roles()->attach($role->role_id);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/news-articles', [
            'title' => 'New Elephant Herd Spotted',
            'content' => 'A new herd of elephants has been seen in the southern sector.',
            'excerpt' => 'New herd spotted in southern sector.',
            'category' => 'Wildlife Update',
        ], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('news_articles', ['title' => 'New Elephant Herd Spotted']);
    }

    public function test_regular_admin_cannot_create_news_article()
    {
        $role = Role::create(['role_name' => 'System Administrator']);
        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password_hash' => 'hash',
            'account_status' => 'Active',
        ]);
        $user->roles()->attach($role->role_id);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/news-articles', [
            'title' => 'Unauthorized Post',
            'content' => 'This should fail.',
        ], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(403);
    }
}
