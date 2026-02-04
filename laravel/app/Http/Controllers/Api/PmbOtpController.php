<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\WhatsApp\WhatsAppFactory;

class PmbOtpController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'nomor_hp' => ['required', 'string', 'min:8', 'max:20'],
        ]);

        $digits = preg_replace('/\D+/', '', $data['nomor_hp']);
        if (Str::startsWith($digits, '0')) $digits = '62' . substr($digits, 1);
        if (!Str::startsWith($digits, '62')) $digits = '62' . $digits;

        $ttl = (int) env('OTP_TTL_SECONDS', 300);
        $otp = (string) random_int(100000, 999999);

        Cache::put("pmb:otp:{$digits}", [
            'otp' => $otp,
            'attempts' => 0,
            'verified' => false,
        ], $ttl);

        $message = "Kode OTP PMB Anda: {$otp}\nBerlaku {$ttl} detik. Jangan bagikan kode ini.";

        $wa = WhatsAppFactory::make();
        $wa->sendMessage($digits, $message);

        return response()->json([
            'message' => 'OTP berhasil dikirim ke WhatsApp.',
            'ttl_seconds' => $ttl,
        ]);
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'nomor_hp' => ['required', 'string', 'min:8', 'max:20'],
            'kode_otp' => ['required', 'string', 'min:4', 'max:6'],
        ]);

        $digits = preg_replace('/\D+/', '', $data['nomor_hp']);
        if (Str::startsWith($digits, '0')) $digits = '62' . substr($digits, 1);
        if (!Str::startsWith($digits, '62')) $digits = '62' . $digits;

        $row = Cache::get("pmb:otp:{$digits}");
        if (!$row) {
            throw ValidationException::withMessages([
                'kode_otp' => 'OTP tidak ditemukan / sudah kedaluwarsa. Silakan kirim ulang.',
            ]);
        }

        $maxAttempts = (int) env('OTP_MAX_ATTEMPTS', 5);
        $attempts = (int) ($row['attempts'] ?? 0);

        if ($attempts >= $maxAttempts) {
            Cache::forget("pmb:otp:{$digits}");
            throw ValidationException::withMessages([
                'kode_otp' => 'Percobaan OTP melebihi batas. Silakan kirim ulang OTP.',
            ]);
        }

        if (trim($data['kode_otp']) !== (string) ($row['otp'] ?? '')) {
            $row['attempts'] = $attempts + 1;
            Cache::put("pmb:otp:{$digits}", $row, (int) env('OTP_TTL_SECONDS', 300));

            throw ValidationException::withMessages([
                'kode_otp' => 'Kode OTP salah.',
            ]);
        }

        $row['verified'] = true;
        Cache::put("pmb:otp:{$digits}", $row, (int) env('OTP_TTL_SECONDS', 300));

        return response()->json([
            'message' => 'OTP berhasil diverifikasi.',
            'otp_terverifikasi' => true,
        ]);
    }
}
