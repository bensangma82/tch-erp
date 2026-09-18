<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'service_order_item_id',
        'service_id',
        'code',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'amount',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(
            Invoice::class
        );
    }

    public function serviceOrderItem()
    {
        return $this->belongsTo(
            ServiceOrderItem::class
        );
    }

    public function service()
    {
        return $this->belongsTo(
            Service::class
        );
    }
}