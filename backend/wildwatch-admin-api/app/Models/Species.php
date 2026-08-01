<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Species extends Model
{
    protected $table = 'species';
    protected $primaryKey = 'species_id';
    public $timestamps = false;

    protected $fillable = ['common_name', 'scientific_name', 'conservation_status'];
}
