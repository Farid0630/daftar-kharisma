<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpWhatsAppGateway implements WhatsAppGateway
{
    public function sendMessage(string $phone, string $message): array
    {
        $provider  = config('wa.http.provider', 'generic');
        $endpoint  = config('wa.http.endpoint');
        $token     = config('wa.http.token');
        $timeout   = (int) config('wa.http.timeout', 15);

        if (!$endpoint || !$token) {
            throw new \RuntimeException('WA_HTTP_ENDPOINT / WA_HTTP_TOKEN belum dikonfigurasi.');
        }

        // Normalisasi nomor: ambil digit saja
        $digits = preg_replace('/\D+/', '', $phone ?? '');
        if ($digits === '') {
            throw new \RuntimeException('Nomor HP kosong setelah normalisasi.');
        }

        // FONNTE: pakai target + message (+ optional countryCode)
        if ($provider === 'fonnte') {
            $countryCode = (string) config('wa.http.country_code', '62');

            // Anda boleh kirim 08xxxx atau 62xxxx; docs contoh pakai 0812... + countryCode 62 :contentReference[oaicite:5]{index=5}
            $payload = [
                'target'      => $digits,
                'message'     => $message,
                'countryCode' => $countryCode, // optional
            ];

            $resp = Http::timeout($timeout)
                ->withHeaders(['Authorization' => $token]) // TANPA "Bearer"
                ->asForm()
                ->post($endpoint, $payload);

            Log::info('[WA][FONNTE] response', [
                'http_status' => $resp->status(),
                'body' => $resp->body(),
            ]);

            // Fonnte bisa saja balas 200 tapi body berisi error → minimal pastikan status HTTP OK
            if (!$resp->successful()) {
                throw new \RuntimeException('Fonnte gagal: ' . $resp->status() . ' ' . $resp->body());
            }

            return $resp->json() ?? ['ok' => true, 'raw' => $resp->body()];
        }

        // GENERIC: fallback
        $authHeader = config('wa.http.auth_header', 'Authorization');
        $authPrefix = config('wa.http.auth_prefix', 'Bearer ');

        $resp = Http::timeout($timeout)
            ->withHeaders([$authHeader => $authPrefix . $token])
            ->asJson()
            ->post($endpoint, ['phone' => $digits, 'message' => $message]);

        if (!$resp->successful()) {
            throw new \RuntimeException('WA HTTP gateway gagal: '.$resp->status().' '.$resp->body());
        }

        return $resp->json() ?? ['ok' => true, 'raw' => $resp->body()];
    }
}