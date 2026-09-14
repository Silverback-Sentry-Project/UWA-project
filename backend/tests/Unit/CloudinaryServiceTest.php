<?php

namespace Tests\Unit;

use App\Services\CloudinaryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudinaryServiceTest extends TestCase
{
    public function test_upload_feed_image_throws_a_clear_error_when_not_configured(): void
    {
        config([
            'services.cloudinary.cloud_name' => null,
            'services.cloudinary.api_key' => null,
            'services.cloudinary.api_secret' => null,
        ]);

        $service = new CloudinaryService;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cloudinary is not configured');

        $service->uploadFeedImage('1', UploadedFile::fake()->create('cover.jpg', 10));
    }

    public function test_upload_feed_image_sends_a_correctly_signed_request_and_returns_the_secure_url(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'demo-cloud',
            'services.cloudinary.api_key' => 'demo-key',
            'services.cloudinary.api_secret' => 'demo-secret',
        ]);

        Http::fake([
            'api.cloudinary.com/*' => Http::response([
                'secure_url' => 'https://res.cloudinary.com/demo-cloud/image/upload/v1/feed/1/fake.jpg',
            ], 200),
        ]);

        $service = new CloudinaryService;
        $url = $service->uploadFeedImage('1', UploadedFile::fake()->create('cover.jpg', 10));

        $this->assertSame('https://res.cloudinary.com/demo-cloud/image/upload/v1/feed/1/fake.jpg', $url);

        Http::assertSent(function ($request) {
            // Http::attach() sends a list of multipart parts (each {name, contents, ...}),
            // not an associative array - Request::data()/offsetGet don't key by field name
            // for multipart bodies, so pull values out by their "name" part ourselves.
            $fields = collect($request->data())
                ->filter(fn ($part) => is_array($part) && array_key_exists('name', $part) && is_scalar($part['contents'] ?? null))
                ->mapWithKeys(fn ($part) => [$part['name'] => $part['contents']]);

            $signedParams = $fields->only(['public_id', 'timestamp'])->sortKeys();
            $expectedBase = $signedParams->map(fn ($value, $key) => "{$key}={$value}")->implode('&');
            $expectedSignature = sha1($expectedBase.'demo-secret');

            return $request->url() === 'https://api.cloudinary.com/v1_1/demo-cloud/image/upload'
                && $fields['api_key'] === 'demo-key'
                && $fields['signature'] === $expectedSignature
                && str_starts_with($fields['public_id'], 'feed/1/');
        });
    }

    public function test_upload_feed_image_throws_when_cloudinary_rejects_the_upload(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'demo-cloud',
            'services.cloudinary.api_key' => 'demo-key',
            'services.cloudinary.api_secret' => 'demo-secret',
        ]);

        Http::fake([
            'api.cloudinary.com/*' => Http::response(['error' => ['message' => 'Invalid signature']], 401),
        ]);

        $service = new CloudinaryService;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cloudinary upload failed');

        $service->uploadFeedImage('1', UploadedFile::fake()->create('cover.jpg', 10));
    }
}
