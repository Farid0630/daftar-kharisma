<?php

namespace App\Services\WhatsApp;

class WhatsAppFactory
{
    public static function make(): WhatsAppGateway
    {
        return match (config('wa.driver', 'log')) {
            'http' => new HttpWhatsAppGateway(),
            default => new LogWhatsAppGateway(),
        };
    }
}
