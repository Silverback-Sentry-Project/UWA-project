<?php

namespace Tests\Feature;

use App\Models\MediaRegistry;
use App\Models\Role;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaRegistryApiTest extends TestCase
{
    use RefreshDatabase;

    private const ORIGINAL_URL = 'https://res.cloudinary.com/demo/image/upload/v1/images/fake.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(CloudinaryService::class, function ($mock) {
            $mock->shouldReceive('upload')->andReturn(self::ORIGINAL_URL);
        });
    }

    private function portalToken(): string
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

    public function test_anonymous_cannot_access_the_registry(): void
    {
        $this->getJson('/api/media')->assertStatus(401);
        $this->postJson('/api/media')->assertStatus(401);
        $this->getJson('/api/media/proxy?url=https%3A%2F%2F93.184.216.34%2Fa.jpg')->assertStatus(401);
    }

    public function test_store_registers_image_with_derived_renditions(): void
    {
        $token = $this->portalToken();

        $response = $this->post('/api/media', [
            'image' => UploadedFile::fake()->create('cover.jpg', 10, 'image/jpeg'),
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(201)
            ->assertJsonPath('original_url', self::ORIGINAL_URL)
            ->assertJsonPath(
                'thumbnail_url',
                'https://res.cloudinary.com/demo/image/upload/w_300,f_auto,q_auto/v1/images/fake.jpg'
            )
            ->assertJsonPath(
                'preview_url',
                'https://res.cloudinary.com/demo/image/upload/w_800,f_auto,q_auto/v1/images/fake.jpg'
            );

        $this->assertDatabaseHas('media_registry', [
            'original_url' => self::ORIGINAL_URL,
            'kind' => 'image',
        ]);
    }

    public function test_destroy_soft_deletes_and_restore_revives(): void
    {
        $token = $this->portalToken();
        $registry = MediaRegistry::create([
            'original_url' => self::ORIGINAL_URL,
            'thumbnail_url' => 'https://res.cloudinary.com/demo/image/upload/w_300/fake.jpg',
            'preview_url' => 'https://res.cloudinary.com/demo/image/upload/w_800/fake.jpg',
        ]);

        $delete = $this->deleteJson("/api/media/{$registry->registry_id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        $delete->assertStatus(204);
        $this->assertSoftDeleted('media_registry', ['registry_id' => $registry->registry_id]);

        $restore = $this->postJson("/api/media/{$registry->registry_id}/restore", [], [
            'Authorization' => "Bearer $token",
        ]);

        $restore->assertStatus(200);
        $this->assertNotSoftDeleted('media_registry', ['registry_id' => $registry->registry_id]);
    }

    public function test_deleted_rows_are_visible_via_the_trashed_filter(): void
    {
        $token = $this->portalToken();
        $registry = MediaRegistry::create([
            'original_url' => self::ORIGINAL_URL,
            'thumbnail_url' => 'https://res.cloudinary.com/demo/image/upload/w_300/fake.jpg',
            'preview_url' => 'https://res.cloudinary.com/demo/image/upload/w_800/fake.jpg',
        ]);
        $registry->delete();

        $this->getJson('/api/media?trashed=1', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJsonPath('data.0.registry_id', $registry->registry_id);

        $this->getJson('/api/media', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_proxy_rejects_a_private_url_before_any_fetch(): void
    {
        $token = $this->portalToken();

        $response = $this->getJson('/api/media/proxy?url='.urlencode('http://169.254.169.254/latest/meta-data/'), [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(400);
    }

    public function test_proxy_requires_a_missing_url_param_to_be_422(): void
    {
        $token = $this->portalToken();

        $this->getJson('/api/media/proxy', ['Authorization' => "Bearer $token"])->assertStatus(422);
    }

    public function test_proxy_streams_a_public_image_with_cache_headers(): void
    {
        $token = $this->portalToken();
        Http::fake([
            'https://93.184.216.34/*' => Http::response("\xFF\xD8\xFF\xE0\x00\x10JFIF", 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $response = $this->getJson('/api/media/proxy?url='.urlencode('https://93.184.216.34/photo.jpg'), [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('max-age=86400', $response->headers->get('Cache-Control'));
    }

    public function test_proxy_rejects_a_non_image_response(): void
    {
        $token = $this->portalToken();
        Http::fake([
            'https://93.184.216.34/*' => Http::response('<html>not an image</html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $response = $this->getJson('/api/media/proxy?url='.urlencode('https://93.184.216.34/not-image'), [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(422);
    }

    public function test_proxy_rejects_a_non_200_origin_response(): void
    {
        $token = $this->portalToken();
        Http::fake([
            'https://93.184.216.34/*' => Http::response('redirecting', 302, [
                'Content-Type' => 'text/html',
                'Location' => 'http://127.0.0.1/secret',
            ]),
        ]);

        $response = $this->getJson('/api/media/proxy?url='.urlencode('https://93.184.216.34/redirect'), [
            'Authorization' => "Bearer $token",
        ]);

        // The guard allowed the public origin, but redirects are disabled and the 302 is
        // never followed - so the internal Location target is never requested.
        $response->assertStatus(502);
    }
}