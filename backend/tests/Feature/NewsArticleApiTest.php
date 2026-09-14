<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\Role;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class NewsArticleApiTest extends TestCase
{
    use RefreshDatabase;

    private $firebaseMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firebaseMock = $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldReceive('syncFeedArticle')->andReturnNull();
            $mock->shouldReceive('deleteFeedArticle')->andReturnNull();
        });

        $this->mock(CloudinaryService::class, function ($mock) {
            $mock->shouldReceive('uploadFeedImage')->andReturn('https://res.cloudinary.com/test/image/upload/feed/1/fake.jpg');
        });
    }

    private function wardenToken(): string
    {
        $role = Role::firstOrCreate(['role_name' => 'Park Warden']);
        $user = User::create([
            'first_name' => 'Alice',
            'last_name' => 'Warden',
            'email' => 'alice-'.uniqid().'@example.com',
            'password_hash' => 'hash',
            'account_status' => 'Active',
        ]);
        $user->roles()->attach($role->role_id);

        return $user->createToken('test-token')->plainTextToken;
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

    public function test_warden_can_publish_article_with_any_mobile_theme()
    {
        // Regression for the theme-enum narrowing: validation has always accepted the five
        // mobile ArticleTheme values but the DB column only ever allowed FOREST/WILDLIFE/
        // SECURITY, so SUNSET/SKY 500'd on the real Postgres CHECK/enum. The widening
        // migration (2026_09_13_000002) converts the column to a plain varchar. This runs
        // against SQLite in the test suite, exercising that conversion path.
        foreach (['FOREST', 'WILDLIFE', 'SECURITY', 'SUNSET', 'SKY'] as $theme) {
            $token = $this->wardenToken();

            $response = $this->postJson('/api/news-articles', [
                'title' => "Theme {$theme} Article",
                'content' => 'Body for '.$theme,
                'excerpt' => 'Excerpt for '.$theme,
                'category' => 'Wildlife Update',
                'theme' => $theme,
            ], [
                'Authorization' => "Bearer $token",
            ]);

            $response->assertStatus(201);
            $this->assertDatabaseHas('news_articles', ['theme' => $theme]);
        }
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

    public function test_warden_can_update_news_article()
    {
        $token = $this->wardenToken();
        $article = NewsArticle::create([
            'title' => 'Original title',
            'excerpt' => 'Original excerpt',
            'category' => 'Wildlife Update',
            'author_id' => User::first()->user_id,
            'published' => true,
            'published_at' => now(),
        ]);

        $response = $this->patchJson("/api/news-articles/{$article->article_id}", [
            'title' => 'Updated title',
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(200)->assertJsonPath('title', 'Updated title');
        $this->assertDatabaseHas('news_articles', [
            'article_id' => $article->article_id,
            'title' => 'Updated title',
        ]);
    }

    public function test_unpublishing_an_article_removes_its_firestore_mirror()
    {
        $token = $this->wardenToken();
        $article = NewsArticle::create([
            'title' => 'Live article',
            'excerpt' => 'Excerpt',
            'category' => 'Wildlife Update',
            'author_id' => User::first()->user_id,
            'published' => true,
            'published_at' => now(),
            'firestore_doc_id' => 'existing-doc-id',
        ]);

        $response = $this->patchJson("/api/news-articles/{$article->article_id}", [
            'published' => false,
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(200);
        $this->firebaseMock->shouldHaveReceived('deleteFeedArticle')->with('existing-doc-id');
    }

    public function test_warden_can_delete_news_article()
    {
        $token = $this->wardenToken();
        $article = NewsArticle::create([
            'title' => 'To delete',
            'excerpt' => 'Excerpt',
            'category' => 'Wildlife Update',
            'author_id' => User::first()->user_id,
            'published' => true,
            'published_at' => now(),
        ]);

        $response = $this->deleteJson("/api/news-articles/{$article->article_id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('news_articles', ['article_id' => $article->article_id]);
    }

    public function test_warden_can_upload_article_image()
    {
        $token = $this->wardenToken();
        $article = NewsArticle::create([
            'title' => 'Article with image',
            'excerpt' => 'Excerpt',
            'category' => 'Wildlife Update',
            'author_id' => User::first()->user_id,
            'published' => true,
            'published_at' => now(),
        ]);

        $response = $this->post("/api/news-articles/{$article->article_id}/image", [
            'image' => UploadedFile::fake()->create('cover.jpg', 10, 'image/jpeg'),
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(200);
        $this->assertNotNull($article->fresh()->image_url);
    }

    public function test_warden_image_upload_registers_media_with_derived_renditions()
    {
        $token = $this->wardenToken();
        $article = NewsArticle::create([
            'title' => 'Article with image',
            'excerpt' => 'Excerpt',
            'category' => 'Wildlife Update',
            'author_id' => User::first()->user_id,
            'published' => true,
            'published_at' => now(),
        ]);

        $response = $this->post("/api/news-articles/{$article->article_id}/image", [
            'image' => UploadedFile::fake()->create('cover.jpg', 10, 'image/jpeg'),
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('media_registry', [
            'owner_type' => NewsArticle::class,
            'owner_id' => $article->article_id,
            'original_url' => 'https://res.cloudinary.com/test/image/upload/feed/1/fake.jpg',
            'thumbnail_url' => 'https://res.cloudinary.com/test/image/upload/w_300,f_auto,q_auto/feed/1/fake.jpg',
            'preview_url' => 'https://res.cloudinary.com/test/image/upload/w_800,f_auto,q_auto/feed/1/fake.jpg',
        ]);
    }

    public function test_deleting_an_article_soft_deletes_its_registered_renditions()
    {
        $token = $this->wardenToken();
        $article = NewsArticle::create([
            'title' => 'With image',
            'excerpt' => 'Excerpt',
            'category' => 'Wildlife Update',
            'author_id' => User::first()->user_id,
            'published' => true,
            'published_at' => now(),
        ]);

        $this->post("/api/news-articles/{$article->article_id}/image", [
            'image' => UploadedFile::fake()->create('cover.jpg', 10, 'image/jpeg'),
        ], ['Authorization' => "Bearer $token"])->assertStatus(200);

        $this->deleteJson("/api/news-articles/{$article->article_id}", [], [
            'Authorization' => "Bearer $token",
        ])->assertStatus(204);

        $this->assertSoftDeleted('media_registry', [
            'owner_type' => NewsArticle::class,
            'owner_id' => $article->article_id,
        ]);
    }

    public function test_image_upload_failure_returns_a_clean_503_not_a_raw_500()
    {
        // Overrides setUp()'s default mock - simulates Cloudinary throwing (e.g. missing
        // credentials, or the upload API rejecting the request).
        $this->mock(CloudinaryService::class, function ($mock) {
            $mock->shouldReceive('uploadFeedImage')->andThrow(new \RuntimeException('Cloudinary is not configured.'));
        });

        $token = $this->wardenToken();
        $article = NewsArticle::create([
            'title' => 'Article with image',
            'excerpt' => 'Excerpt',
            'category' => 'Wildlife Update',
            'author_id' => User::first()->user_id,
            'published' => true,
            'published_at' => now(),
        ]);

        $response = $this->post("/api/news-articles/{$article->article_id}/image", [
            'image' => UploadedFile::fake()->create('cover.jpg', 10, 'image/jpeg'),
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(503);
        $this->assertNull($article->fresh()->image_url);
    }
}
