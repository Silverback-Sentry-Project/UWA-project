<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Feed header images only. Replaces the original Firebase Storage-based upload
// (FirebaseService::uploadFeedImage(), removed) - Cloud Storage for Firebase requires the
// Blaze plan and this project's Firebase project (wildwatch-82abc) is on Spark, so no
// default bucket has ever existed there; every upload attempt threw deep in the SDK.
// Cloudinary has no equivalent billing-plan gate on its free tier.
class CloudinaryService
{
    public function uploadFeedImage(string $articleId, UploadedFile $file): string
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            throw new \RuntimeException(
                'Cloudinary is not configured. Set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, '.
                'and CLOUDINARY_API_SECRET (from the Cloudinary dashboard).'
            );
        }

        $timestamp = time();
        $publicId = "feed/{$articleId}/".Str::uuid()->toString();

        // Cloudinary's signed-upload scheme: every param except file/cloud_name/api_key/
        // signature/resource_type is sorted and joined as "key=value&key=value...", then
        // the api_secret is appended (not included as a param) before hashing - see
        // https://cloudinary.com/documentation/authentication_signatures.
        $paramsToSign = ['public_id' => $publicId, 'timestamp' => $timestamp];
        ksort($paramsToSign);
        $signatureBase = collect($paramsToSign)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode('&');
        $signature = sha1($signatureBase.$apiSecret);

        $response = Http::attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
            ->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                'api_key' => $apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'public_id' => $publicId,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Cloudinary upload failed: '.$response->body());
        }

        return $response->json('secure_url');
    }
}
