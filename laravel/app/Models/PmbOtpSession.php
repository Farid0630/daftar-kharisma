<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PmbOtpSession extends Model
{
    protected $table = 'pmb_otp_sessions';

    protected $fillable = [
        'session_id', 'phone', 'otp_hash', 'attempts', 'expires_at', 'verified_at', 'meta'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'meta' => 'array',
    ];
}
