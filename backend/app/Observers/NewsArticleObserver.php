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
        $this->syncOrRemove($article);
    }

    // Added alongside the portal composer's edit/publish-toggle UI - previously an edit made
    // after the initial create() never reached Firestore at all, so the mobile feed would
    // keep showing stale content indefinitely. wasChanged('published') matters specifically
    // for the false case: an article that goes from published -> unpublished needs its
    // Firestore mirror removed, not just left alone with stale data.
    public function updated(NewsArticle $article): void
    {
        if (! $article->published) {
            if ($article->wasChanged('published') && $article->firestore_doc_id) {
                $this->firebase->deleteFeedArticle($article->firestore_doc_id);
            }
            return;
        }

        $this->syncOrRemove($article);
    }

    public function deleted(NewsArticle $article): void
    {
        if ($article->firestore_doc_id) {
            $this->firebase->deleteFeedArticle($article->firestore_doc_id);
        }
    }

    private function syncOrRemove(NewsArticle $article): void
    {
        if (! $article->published) {
            return;
        }

        $isNewDoc = ! $article->firestore_doc_id;
        $docId = $article->firestore_doc_id ?: (string) $article->article_id;

        if ($isNewDoc) {
            $article->forceFill(['firestore_doc_id' => $docId])->saveQuietly();
        }

        $fields = [
            'title' => $article->title,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'imageUrl' => $article->image_url,
            'category' => $article->category,
            'source' => $article->source,
            'park_id' => (string) $article->park_id,
            'readTime' => $article->read_time,
            'theme' => Str::lower($article->theme),
            'publishedAt' => $article->published_at?->toIso8601String() ?? now()->toIso8601String(),
            'authorId' => (string) $article->author_id,
        ];

        // Only stamped on the very first sync - syncFeedArticle() merges rather than
        // overwrites, so setting these on every edit would silently zero out real engagement
        // counts once a like/comment feature exists on top of this same Firestore doc.
        if ($isNewDoc) {
            $fields['likes'] = 0;
            $fields['comments'] = 0;
        }

        $this->firebase->syncFeedArticle($docId, $fields);
    }
}
