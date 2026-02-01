<?php

return [
    'driver' => env('WA_DRIVER', 'log'),

    'http' => [
        'provider'     => env('WA_HTTP_PROVIDER', 'generic'),
        'endpoint'     => env('WA_HTTP_ENDPOINT'),
        'token'        => env('WA_HTTP_TOKEN'),
        'auth_header'  => env('WA_HTTP_AUTH_HEADER', 'Authorization'),
        'auth_prefix'  => env('WA_HTTP_AUTH_PREFIX', 'Bearer '),
        'timeout'      => env('WA_HTTP_TIMEOUT', 15),
        'country_code' => env('WA_COUNTRY_CODE', '62'),
    ],
];  