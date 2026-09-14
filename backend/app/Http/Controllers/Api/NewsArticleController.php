<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MediaRegistry;
use App\Models\NewsArticle;
use App\Services\CloudinaryService;
use App\Services\FirebaseService;
use App\Services\MediaRenditionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NewsArticleController extends Controller
{
    public function __construct(
        private readonly FirebaseService $firebase,
        private readonly CloudinaryService $cloudinary,
        private readonly MediaRenditionService $renditions,
    ) {
    }

    // This whole controller sits behind auth:sanctum + warden_or_uwa (routes/api.php), so
    // unlike a public-facing feed endpoint there's no reason to hide drafts here - the portal
    // composer (portal.feed.tsx) needs to list and edit unpublished articles, not just
    // published ones. Public/mobile reads happen via Firestore's /feed collection instead
    // (see NewsArticleObserver), never this REST endpoint.
    public function index(Request $request)
    {
        $query = NewsArticle::with('author');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return response()->json(
            $query->latest('published_at')->paginate($request->integer('per_page', 25))
        );
    }

    public function show(NewsArticle $newsArticle)
    {
        $newsArticle->load('author');

        return response()->json($newsArticle);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $article = NewsArticle::create([
            ...$validator->validated(),
            'author_id' => $request->user()->user_id,
            'source' => $request->input('source', 'Uganda Wildlife Authority'),
            'read_time' => $request->input('read_time', '3 min'),
            'theme' => $request->input('theme', 'FOREST'),
            'published' => $request->boolean('published', true),
            'published_at' => $request->input('published_at', now()),
        ]);

        return response()->json($article->fresh('author'), 201);
    }

    public function update(Request $request, NewsArticle $newsArticle)
    {
        $validator = Validator::make($request->all(), $this->rules(partial: true));

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $newsArticle->update($validator->validated());

        return response()->json($newsArticle->fresh('author'));
    }

    public function destroy(Request $request, NewsArticle $newsArticle)
    {
        // Cascade a soft delete to the article's registered renditions so a removed
        // article no longer keeps derived thumbnails/previews alive in the registry.
        MediaRegistry::where('owner_type', NewsArticle::class)
            ->where('owner_id', $newsArticle->article_id)
            ->update(['deleted_by' => $request->user()->user_id]);
        MediaRegistry::where('owner_type', NewsArticle::class)
            ->where('owner_id', $newsArticle->article_id)
            ->delete();

        $newsArticle->delete();

        return response()->json(null, 204);
    }

    // Proxied through Laravel (not a direct client upload) so the same warden_or_uwa auth
    // gate that protects every other write in this controller also protects who can push
    // files in - see CloudinaryService::uploadFeedImage(). Was Firebase Storage originally;
    // switched because that requires the Blaze plan and this project's Firebase project is
    // on Spark, so no bucket was ever provisioned there (confirmed live: both possible
    // default bucket names 404 from Firebase's own Storage REST API).
    public function uploadImage(Request $request, NewsArticle $newsArticle)
    {
        $validator = Validator::make($request->all(), [
            'image' => ['required', 'image', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $url = $this->cloudinary->uploadFeedImage((string) $newsArticle->article_id, $request->file('image'));
        } catch (\Throwable $e) {
            Log::error('News article image upload failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Image upload is unavailable - check Cloudinary configuration. '.
                    'The article was saved without an image.',
            ], 503);
        }

        $newsArticle->update(['image_url' => $url]);

        // Record the original plus derived rendition URLs so feed consumers can request
        // the right size per context (thumbnail vs preview) without re-deriving, and so
        // admin can audit every image the portal has pushed out.
        $this->renditions->register(NewsArticle::class, $newsArticle->article_id, $url);

        return response()->json($newsArticle->fresh('author'));
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'title' => [$required, 'string', 'max:255'],
            'excerpt' => [$required, 'string'],
            'body' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'category' => [$required, 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:150'],
            'read_time' => ['nullable', 'string', 'max:20'],
            // Mirrors mobile's ArticleTheme enum (core/database/ArticleEntity.kt) exactly -
            // the original 3-value list here meant SUNSET/SKY could never actually be set
            // from the portal even though the mobile app already knows how to render them.
            'theme' => ['nullable', 'in:FOREST,WILDLIFE,SECURITY,SUNSET,SKY'],
            'park_id' => ['nullable', 'integer', 'exists:parks,park_id'],
            'published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
