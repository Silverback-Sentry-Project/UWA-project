<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    protected $table = 'news_articles';
    protected $primaryKey = 'article_id';
    public $timestamps = false;

    protected $fillable = [
        'firestore_doc_id',
        'author_id',
        'title',
        'excerpt',
        'body',
        'category',
        'source',
        'read_time',
        'theme',
        'published',
        'published_at',
    ];

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'user_id');
    }
}
