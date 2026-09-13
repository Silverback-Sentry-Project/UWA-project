<?php

namespace App\Services;

use App\Models\MediaRegistry;

// Derives the thumbnail/preview renditions the portal actually displays, and records
// them in media_registry so consumers never re-derive the transform themselves.
//
// Deliberately no GD/Imagick dependency: this project's runtime has neither extension
// (confirmed via `php -m`), so renditions are produced as Cloudinary transform URLs
// (e.g. .../image/upload/w_300,f_auto,q_auto/v123/...) rather than locally resized
// copies. That keeps the "request the right size for the context" win of the original
// ww-website pipeline without a local image-processing stack.
class MediaRenditionService
{
    public const THUMBNAIL_TRANSFORMS = 'w_300,f_auto,q_auto';

    public const PREVIEW_TRANSFORMS = 'w_800,f_auto,q_auto';

    public function register(?string $ownerType, ?int $ownerId, string $originalUrl, string $kind = 'image'): MediaRegistry
    {
        return MediaRegistry::firstOrCreate(
            [
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'original_url' => $originalUrl,
            ],
            [
                'kind' => $kind,
                'thumbnail_url' => $this->derivativeUrl($originalUrl, self::THUMBNAIL_TRANSFORMS),
                'preview_url' => $this->derivativeUrl($originalUrl, self::PREVIEW_TRANSFORMS),
            ]
        );
    }

    public function derivativeUrl(string $originalUrl, string $transforms): ?string
    {
        $marker = 'image/upload/';
        $pos = strpos($originalUrl, $marker);

        if ($pos === false) {
            return null;
        }

        $prefix = substr($originalUrl, 0, $pos + strlen($marker));

        return $prefix.$transforms.'/'.substr($originalUrl, $pos + strlen($marker));
    }
}