<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingOrderCheckout extends Model
{
    protected $fillable = [
        'user_id',
        'is_guest',
        'module_id',
        'store_id',
        'payment_method',
        'payment_gateway',
        'order_amount',
        'digital_payment_fee',
        'partially_paid_amount',
        'callback',
        'payload',
        'status',
        'order_id',
        'expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'is_guest' => 'boolean',
        'module_id' => 'integer',
        'store_id' => 'integer',
        'order_amount' => 'float',
        'digital_payment_fee' => 'float',
        'partially_paid_amount' => 'float',
        'payload' => 'array',
        'order_id' => 'integer',
        'expires_at' => 'datetime',
    ];
}
