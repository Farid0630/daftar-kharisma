<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PmbPayment extends Model
{
    protected $table = 'pmb_payments';

    protected $fillable = [
        'external_id',
        'invoice_id',
        'invoice_url',
        'amount',
        'method',
        'currency',
        'status',
        'payer_email',
        'phone',
        'paid_at',
        'expiry_date',
        'invoice_payload',
        'webhook_payload',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'expiry_date' => 'datetime',
        'invoice_payload' => 'array',
        'webhook_payload' => 'array',
    ];
}
