<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PmbOtpCode extends Model
{
    protected $table = 'pmb_otp_codes';

    protected $fillable = [
        'phone', 'code_hash', 'expires_at', 'verified_at', 'attempts',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
    ];
}
