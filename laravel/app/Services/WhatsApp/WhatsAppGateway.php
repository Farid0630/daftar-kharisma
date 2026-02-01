<?php
namespace App\Services\WhatsApp;

interface WhatsAppGateway
{
    public function sendMessage(string $phone, string $message): array;
}