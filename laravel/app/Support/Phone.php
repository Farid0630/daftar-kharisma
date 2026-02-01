<?php

namespace App\Support;

class Phone
{
    public static function normalizeIdToE164(string $input, string $countryCode = '62'): string
    {
        $raw = preg_replace('/\D+/', '', $input) ?? '';
        if ($raw === '') return '';

        // 0xxxxxxxxx -> 62xxxxxxxxx
        if (str_starts_with($raw, '0')) {
            $raw = $countryCode . substr($raw, 1);
        }

        // already 62...
        if (str_starts_with($raw, $countryCode)) {
            return $raw;
        }

        // fallback: prefix country code
        return $countryCode . $raw;
    }
}
