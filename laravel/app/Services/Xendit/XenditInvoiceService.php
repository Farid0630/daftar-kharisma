<?php

namespace App\Services\Xendit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class XenditInvoiceService
{
    private function baseUrl(): string
    {
        return rtrim(config('xendit.api_base'), '/');
    }

    private function secretKey(): string
    {
        $key = (string) config('xendit.secret_key');
        if (!$key) {
            throw new \RuntimeException('XENDIT_SECRET_KEY belum di-set.');
        }
        return $key;
    }

    /**
     * Xendit pakai Basic Auth: username = secret key, password kosong.
     */
    private function client()
    {
        return Http::withBasicAuth($this->secretKey(), '')
            ->acceptJson()
            ->timeout(20);
    }

    public function createInvoice(array $payload): array
    {
        $url = $this->baseUrl() . '/v2/invoices';

        $resp = $this->client()->post($url, $payload);

        if (!$resp->successful()) {
            // lempar body mentah agar Anda bisa lihat pesan 400 dari Xendit
            throw new \RuntimeException($resp->body());
        }

        return $resp->json();
    }

    public function getInvoice(string $invoiceId): array
    {
        $url = $this->baseUrl() . '/v2/invoices/' . urlencode($invoiceId);

        $resp = $this->client()->get($url);

        if (!$resp->successful()) {
            throw new \RuntimeException($resp->body());
        }

        return $resp->json();
    }

    public function makeExternalId(string $prefix = 'PMB-MANDIRI-'): string
    {
        return $prefix . Str::upper(Str::random(10));
    }
}
