<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RangerReport extends Model
{
    protected $table = 'ranger_reports';
    protected $primaryKey = 'report_id';
    public $timestamps = false;

    protected $fillable = ['incident_id', 'ranger_id', 'actions_taken', 'findings', 'recommendations'];

    protected $casts = ['report_date' => 'datetime'];

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }

    public function ranger()
    {
        return $this->belongsTo(User::class, 'ranger_id', 'user_id');
    }
}
