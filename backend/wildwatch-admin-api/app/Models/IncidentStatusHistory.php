<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentStatusHistory extends Model
{
    protected $table = 'incident_status_history';
    protected $primaryKey = 'status_history_id';
    public $timestamps = false;

    protected $fillable = ['incident_id', 'updated_by', 'old_status', 'new_status', 'remarks'];

    protected $casts = ['updated_at' => 'datetime'];

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }
}
