<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MediaRegistry;
use App\Services\CloudinaryService;
use App\Services\MediaRenditionService;
use App\Services\SsrfGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

// Admin-managed registry of every image the portal stores plus derived renditions
// (MediaRenditionService), and the on-request image proxy that lets the portal render
// remote image content server-side without pointing browsers at arbitrary endpoints.
// The proxy applies the SsrfGuard so a proxied URL can never reach private/link-local
// infrastructure, disables redirects, caps the payload, and only passes image/* through.
class MediaController extends Controller
{
    private const MAX_PROXY_BYTES = 10 * 1024 * 1024;

    private const PROXY_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly MediaRenditionService $renditions,
        private readonly CloudinaryService $cloudinary,
        private readonly SsrfGuard $ssrf,
    ) {
    }

    public function index(Request $request)
    {
        $query = MediaRegistry::with('owner');

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        return $query->latest('created_at')->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => ['required', 'image', 'max:10240'],
            'kind' => ['sometimes', 'in:image,audio,video,document'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $url = $this->cloudinary->upload('images', $request->file('image'));
        } catch (\Throwable $e) {
            Log::error('Media registry upload failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Upload is unavailable - check Cloudinary configuration.',
            ], 503);
        }

        $registry = $this->renditions->register(null, null, $url, $request->input('kind', 'image'));

        return response()->json($registry, 201);
    }

    public function destroy(Request $request, MediaRegistry $mediaRegistry): JsonResponse
    {
        $mediaRegistry->update(['deleted_by' => $request->user()->user_id]);
        $mediaRegistry->delete();

        return response()->json(null, 204);
    }

    public function restore(int $mediaRegistry): JsonResponse
    {
        $registry = MediaRegistry::withTrashed()->findOrFail($mediaRegistry);
        $registry->restore();

        return response()->json($registry);
    }

    public function proxy(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'url', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $url = $request->input('url');

        try {
            $this->ssrf->assertSafe($url);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        try {
            // Redirects disabled: a compliant-system check is only meaningful on the URL we
            // were handed; following Location headers after the guard runs would let an
            // attacker hop to an internal address that was never classified.
            $response = Http::timeout(self::PROXY_TIMEOUT_SECONDS)
                ->withOptions(['allow_redirects' => false])
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('Media proxy fetch failed: '.$e->getMessage());

            return response()->json(['message' => 'Could not fetch the remote image.'], 502);
        }

        if ($response->status() !== 200 || $response->failed()) {
            return response()->json(['message' => 'The remote image could not be retrieved.'], 502);
        }

        $contentType = $response->header('Content-Type');
        if (! $contentType || ! str_starts_with(strtolower(explode(';', $contentType)[0]), 'image/')) {
            return response()->json(['message' => 'The URL did not return an image.'], 422);
        }

        $body = $response->body();
        if (strlen($body) > self::MAX_PROXY_BYTES) {
            return response()->json(['message' => 'The image is too large to proxy.'], 413);
        }

        return response($body, 200, [
            'Content-Type' => explode(';', $contentType)[0],
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}