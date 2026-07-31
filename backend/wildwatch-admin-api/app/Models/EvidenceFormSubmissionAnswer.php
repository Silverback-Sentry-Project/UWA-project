<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceFormSubmissionAnswer extends Model
{
    protected $table = 'evidence_form_submission_answers';
    protected $primaryKey = 'answer_id';

    protected $fillable = ['submission_id', 'field_id', 'value', 'image_path'];

    public function submission()
    {
        return $this->belongsTo(EvidenceFormSubmission::class, 'submission_id', 'submission_id');
    }

    public function field()
    {
        return $this->belongsTo(EvidenceFormField::class, 'field_id', 'field_id');
    }
}
