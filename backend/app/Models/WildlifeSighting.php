<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WildlifeSighting extends Model
{
    protected $table = 'wildlife_sightings';
    protected $primaryKey = 'sighting_id';
    public $timestamps = false;

    protected $fillable = [
        'ranger_id', 'species_id', 'park_id', 'latitude', 'longitude', 'number_seen', 'notes',
        'firestore_doc_id', 'source_system', 'approval_status',
    ];

    protected $casts = ['sighting_time' => 'datetime'];

    public function ranger()
    {
        return $this->belongsTo(User::class, 'ranger_id', 'user_id');
    }

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id', 'species_id');
    }

    public function park()
    {
        return $this->belongsTo(Park::class, 'park_id', 'park_id');
    }
}
