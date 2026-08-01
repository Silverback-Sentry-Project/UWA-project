<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $table = 'incidents';
    protected $primaryKey = 'incident_id';
    public $timestamps = false;

    protected $fillable = [
        'reported_by', 'park_id', 'incident_type', 'description',
        'latitude', 'longitude', 'village', 'status',
        'firestore_doc_id', 'source_system',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }

    public function park()
    {
        return $this->belongsTo(Park::class, 'park_id', 'park_id');
    }

    public function species()
    {
        return $this->belongsToMany(Species::class, 'incident_species', 'incident_id', 'species_id')
            ->withPivot('number_affected');
    }

    public function media()
    {
        return $this->hasMany(IncidentMedia::class, 'incident_id', 'incident_id');
    }

    public function assignments()
    {
        return $this->hasMany(IncidentAssignment::class, 'incident_id', 'incident_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(IncidentStatusHistory::class, 'incident_id', 'incident_id')
            ->orderBy('updated_at');
    }

    public function rangerReport()
    {
        return $this->hasOne(RangerReport::class, 'incident_id', 'incident_id');
    }

    public function compensationClaim()
    {
        return $this->hasOne(CompensationClaim::class, 'incident_id', 'incident_id');
    }
}
