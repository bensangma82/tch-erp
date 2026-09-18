<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'receipt_no',
        'invoice_id',
        'patient_id',
        'encounter_id',
        'payment_date',
        'amount',
        'payment_mode',
        'transaction_reference',
        'remarks',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter()
    {
        return $this->belongsTo(Encounter::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(
            User::class,
            'received_by'
        );
    }
}