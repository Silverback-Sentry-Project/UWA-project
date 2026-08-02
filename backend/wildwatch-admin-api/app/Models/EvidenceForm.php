<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceForm extends Model
{
    protected $table = 'evidence_forms';
    protected $primaryKey = 'form_id';

    protected $fillable = ['park_id', 'created_by', 'title', 'description', 'status'];

    public function park()
    {
        return $this->belongsTo(Park::class, 'park_id', 'park_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function fields()
    {
        return $this->hasMany(EvidenceFormField::class, 'form_id', 'form_id')->orderBy('position');
    }

    public function submissions()
    {
        return $this->hasMany(EvidenceFormSubmission::class, 'form_id', 'form_id');
    }
}
