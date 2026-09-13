<?php

namespace Tests\Unit;

use App\Services\MediaRenditionService;
use Tests\TestCase;

class MediaRenditionServiceTest extends TestCase
{
    public function test_derivative_url_inserts_the_transform_after_image_upload(): void
    {
        $service = new MediaRenditionService;

        $thumbnail = $service->derivativeUrl(
            'https://res.cloudinary.com/demo/image/upload/v123/feed/1/fake.jpg',
            MediaRenditionService::THUMBNAIL_TRANSFORMS
        );

        $this->assertSame(
            'https://res.cloudinary.com/demo/image/upload/w_300,f_auto,q_auto/v123/feed/1/fake.jpg',
            $thumbnail
        );
    }

    public function test_derivative_url_preserves_query_strings_and_segments(): void
    {
        $service = new MediaRenditionService;

        $preview = $service->derivativeUrl(
            'https://res.cloudinary.com/demo/image/upload/v1/media/a.jpg?foo=1',
            MediaRenditionService::PREVIEW_TRANSFORMS
        );

        $this->assertSame(
            'https://res.cloudinary.com/demo/image/upload/w_800,f_auto,q_auto/v1/media/a.jpg?foo=1',
            $preview
        );
    }

    public function test_derivative_url_returns_null_for_a_non_cloudinary_url(): void
    {
        $service = new MediaRenditionService;

        $this->assertNull($service->derivativeUrl('https://example.com/image.jpg', MediaRenditionService::THUMBNAIL_TRANSFORMS));
        $this->assertNull($service->derivativeUrl('', MediaRenditionService::THUMBNAIL_TRANSFORMS));
    }

    public function test_thumbnail_and_preview_transforms_differ_in_width(): void
    {
        $this->assertStringContainsString('w_300', MediaRenditionService::THUMBNAIL_TRANSFORMS);
        $this->assertStringContainsString('w_800', MediaRenditionService::PREVIEW_TRANSFORMS);
    }
}