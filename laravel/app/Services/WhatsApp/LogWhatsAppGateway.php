<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

class LogWhatsAppGateway implements WhatsAppGateway
{
    public function sendMessage(string $phone, string $message): array
    {
        Log::info('[WA][LOG] Send message', ['phone' => $phone, 'message' => $message]);

        // kembalikan respons dummy agar controller bisa tetap konsisten
        return ['ok' => true, 'driver' => 'log'];
    }
}