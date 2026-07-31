<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceFormSubmission extends Model
{
    protected $table = 'evidence_form_submissions';
    protected $primaryKey = 'submission_id';

    protected $fillable = [
        'form_id', 'park_id', 'submitted_by_name', 'submitted_by_contact',
        'status', 'verified_by', 'verified_at', 'forwarded_at', 'verification_notes',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'forwarded_at' => 'datetime',
    ];

    public function form()
    {
        return $this->belongsTo(EvidenceForm::class, 'form_id', 'form_id');
    }

    public function park()
    {
        return $this->belongsTo(Park::class, 'park_id', 'park_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }

    public function answers()
    {
        return $this->hasMany(EvidenceFormSubmissionAnswer::class, 'submission_id', 'submission_id');
    }
}
