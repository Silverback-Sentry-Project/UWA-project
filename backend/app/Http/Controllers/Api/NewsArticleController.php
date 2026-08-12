<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsArticle::with('author')->where('published', true);

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
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['required', 'string'],
            'body' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:150'],
            'read_time' => ['nullable', 'string', 'max:20'],
            'theme' => ['nullable', 'in:FOREST,WILDLIFE,SECURITY'],
            'park_id' => ['nullable', 'integer', 'exists:parks,park_id'],
            'published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

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
}
