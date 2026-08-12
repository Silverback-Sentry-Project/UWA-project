<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceFormField extends Model
{
    protected $table = 'evidence_form_fields';
    protected $primaryKey = 'field_id';

    protected $fillable = ['form_id', 'label', 'field_type', 'options', 'is_required', 'position'];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    public function form()
    {
        return $this->belongsTo(EvidenceForm::class, 'form_id', 'form_id');
    }

    public function answers()
    {
        return $this->hasMany(EvidenceFormSubmissionAnswer::class, 'field_id', 'field_id');
    }
}
