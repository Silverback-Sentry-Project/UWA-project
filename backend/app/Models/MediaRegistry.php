<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaRegistry extends Model
{
    use SoftDeletes;

    protected $table = 'media_registry';

    protected $primaryKey = 'registry_id';

    public $timestamps = false;

    protected $fillable = [
        'owner_type', 'owner_id', 'kind', 'original_url',
        'thumbnail_url', 'preview_url', 'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->morphTo();
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'user_id');
    }
}