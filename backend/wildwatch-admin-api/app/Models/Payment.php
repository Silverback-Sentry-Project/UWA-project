<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    public $timestamps = false;

    protected $fillable = ['claim_id', 'amount_paid', 'payment_method', 'transaction_reference', 'payment_date'];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    public function claim()
    {
        return $this->belongsTo(CompensationClaim::class, 'claim_id', 'claim_id');
    }
}
