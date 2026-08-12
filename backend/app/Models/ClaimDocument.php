<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimDocument extends Model
{
    protected $table = 'claim_documents';
    protected $primaryKey = 'document_id';
    public $timestamps = false;

    protected $fillable = ['claim_id', 'document_type', 'file_path'];

    protected $casts = ['uploaded_at' => 'datetime'];

    public function claim()
    {
        return $this->belongsTo(CompensationClaim::class, 'claim_id', 'claim_id');
    }
}
