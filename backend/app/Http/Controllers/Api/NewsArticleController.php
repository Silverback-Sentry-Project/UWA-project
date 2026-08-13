<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsArticleController extends Controller
{
    public function __construct(private readonly FirebaseService $firebase)
    {
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

    public function destroy(NewsArticle $newsArticle)
    {
        $newsArticle->delete();

        return response()->json(null, 204);
    }

    // Proxied through Laravel (not a direct client->Firebase upload) so the same
    // warden_or_uwa auth gate that protects every other write in this controller also
    // protects who can push files into the feed/ Storage path - see FirebaseService::
    // uploadFeedImage() and storage.rules' matching comment on why client writes are denied.
    public function uploadImage(Request $request, NewsArticle $newsArticle)
    {
        $validator = Validator::make($request->all(), [
            'image' => ['required', 'image', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $url = $this->firebase->uploadFeedImage((string) $newsArticle->article_id, $request->file('image'));
        $newsArticle->update(['image_url' => $url]);

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
