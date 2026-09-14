<?php

namespace App\Console\Commands;

use App\Models\MediaRegistry;
use App\Models\NewsArticle;
use App\Services\MediaRenditionService;
use Illuminate\Console\Command;

class BackfillMediaDerivatives extends Command
{
    protected $signature = 'silverback-sentry:backfill-media';

    protected $description = 'Create media_registry rows and rendition URLs for existing news article images';

    public function handle(MediaRenditionService $renditions): int
    {
        $created = 0;
        $updated = 0;

        NewsArticle::query()
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->get()
            ->each(function (NewsArticle $article) use ($renditions, &$created) {
                $registered = $renditions->register(NewsArticle::class, $article->article_id, $article->image_url);
                if ($registered->wasRecentlyCreated) {
                    $created++;
                }
            });

        // Idempotently fill any rendition URLs missing on pre-existing registry rows (e.g.
        // rows created before the derivative columns were computed).
        MediaRegistry::query()
            ->get()
            ->each(function (MediaRegistry $row) use ($renditions, &$updated) {
                $thumbnail = $row->thumbnail_url ?? $renditions->derivativeUrl($row->original_url, MediaRenditionService::THUMBNAIL_TRANSFORMS);
                $preview = $row->preview_url ?? $renditions->derivativeUrl($row->original_url, MediaRenditionService::PREVIEW_TRANSFORMS);

                if ($thumbnail !== $row->thumbnail_url || $preview !== $row->preview_url) {
                    $row->update(['thumbnail_url' => $thumbnail, 'preview_url' => $preview]);
                    $updated++;
                }
            });

        $this->info("Media registry backfill complete: {$created} created, {$updated} rendition rows updated.");

        return self::SUCCESS;
    }
}