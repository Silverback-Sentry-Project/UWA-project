<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentRoute extends Model
{
    protected $table = 'incident_routes';
    protected $primaryKey = 'route_id';
    public $timestamps = false;

    protected $fillable = ['incident_id', 'ranger_id', 'route_data'];

    protected $casts = ['created_at' => 'datetime'];

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }

    public function ranger()
    {
        return $this->belongsTo(User::class, 'ranger_id', 'user_id');
    }
}
