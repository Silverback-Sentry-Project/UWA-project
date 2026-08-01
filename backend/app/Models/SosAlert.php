<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SosAlert extends Model
{
    protected $table = 'sos_alerts';
    protected $primaryKey = 'sos_id';
    public $timestamps = false;

    protected $fillable = [
        'reported_by', 'park_id', 'emergency_type', 'description',
        'latitude', 'longitude', 'status', 'resolved_at',
        'firestore_doc_id', 'source_system',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }

    public function park()
    {
        return $this->belongsTo(Park::class, 'park_id', 'park_id');
    }
}
