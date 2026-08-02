<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentMedia extends Model
{
    protected $table = 'incident_media';
    protected $primaryKey = 'media_id';
    public $timestamps = false;

    protected $fillable = ['incident_id', 'media_type', 'file_path'];

    protected $casts = ['uploaded_at' => 'datetime'];

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }
}
