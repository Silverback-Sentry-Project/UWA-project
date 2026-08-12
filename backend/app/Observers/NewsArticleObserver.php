<?php

namespace App\Observers;

use App\Models\NewsArticle;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

class NewsArticleObserver
{
    public function __construct(private readonly FirebaseService $firebase)
    {
    }

    public function created(NewsArticle $article): void
    {
        if (! $article->published) {
            return;
        }

        $docId = $article->firestore_doc_id ?: (string) $article->article_id;

        if (! $article->firestore_doc_id) {
            $article->forceFill(['firestore_doc_id' => $docId])->saveQuietly();
        }

        $this->firebase->syncFeedArticle($docId, [
            'title' => $article->title,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'category' => $article->category,
            'source' => $article->source,
            'park_id' => (string) $article->park_id,
            'readTime' => $article->read_time,
            'theme' => Str::lower($article->theme),
            'likes' => 0,
            'comments' => 0,
            'publishedAt' => $article->published_at?->toIso8601String() ?? now()->toIso8601String(),
            'authorId' => (string) $article->author_id,
        ]);
    }
}
