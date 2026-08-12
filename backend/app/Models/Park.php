<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Park extends Model
{
    protected $table = 'parks';
    protected $primaryKey = 'park_id';
    public $timestamps = false;

    protected $fillable = ['park_name', 'district', 'description', 'firestore_id'];

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'park_id', 'park_id');
    }

    public function sosAlerts()
    {
        return $this->hasMany(SosAlert::class, 'park_id', 'park_id');
    }

    public function wildlifeSightings()
    {
        return $this->hasMany(WildlifeSighting::class, 'park_id', 'park_id');
    }
}
