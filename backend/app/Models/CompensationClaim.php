<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompensationClaim extends Model
{
    protected $table = 'compensation_claims';
    protected $primaryKey = 'claim_id';
    public $timestamps = false;

    protected $fillable = [
        'incident_id', 'claimant_id', 'estimated_amount', 'claim_status',
        'reviewed_by', 'approved_by', 'reviewed_at', 'approved_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'estimated_amount' => 'decimal:2',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }

    public function claimant()
    {
        return $this->belongsTo(User::class, 'claimant_id', 'user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function documents()
    {
        return $this->hasMany(ClaimDocument::class, 'claim_id', 'claim_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'claim_id', 'claim_id');
    }
}
