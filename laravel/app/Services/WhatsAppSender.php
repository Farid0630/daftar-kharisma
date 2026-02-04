<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppSender
{
    public function send(string $phone, string $message): void
    {
        $driver = config('services.wa.driver', 'log');

        if ($driver === 'log') {
            Log::info('[WA OTP][LOG DRIVER] Send message', [
                'phone' => $phone,
                'message' => $message,
            ]);
            return;
        }

        if ($driver === 'http') {
            $endpoint = config('services.wa.endpoint');
            $token = config('services.wa.token');

            if (!$endpoint || !$token) {
                throw new \RuntimeException('WA HTTP endpoint/token belum di-set di .env');
            }

            // Payload ini GENERIK. Sesuaikan dengan gateway WA Anda (Fonnte/Wablas/dll).
            Http::withToken($token)
                ->timeout(15)
                ->post($endpoint, [
                    'phone'   => $phone,
                    'message' => $message,
                ])
                ->throw();

            return;
        }

        throw new \InvalidArgumentException("WA_DRIVER tidak dikenali: {$driver}");
    }
}
