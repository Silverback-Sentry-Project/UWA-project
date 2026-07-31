<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentAssignment extends Model
{
    protected $table = 'incident_assignments';
    protected $primaryKey = 'assignment_id';
    public $timestamps = false;

    protected $fillable = ['incident_id', 'ranger_id', 'assigned_by', 'assignment_status'];

    protected $casts = ['assigned_at' => 'datetime'];

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }

    public function ranger()
    {
        return $this->belongsTo(User::class, 'ranger_id', 'user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'user_id');
    }
}
