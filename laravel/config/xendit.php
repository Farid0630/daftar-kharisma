<?php

return [
    'api_base' => env('XENDIT_API_BASE', 'https://api.xendit.co'),
    'secret_key' => env('XENDIT_SECRET_KEY'),
    'callback_token' => env('XENDIT_CALLBACK_TOKEN'),

    // nominal sumber kebenaran (backend)
    'invoice_amount' => (int) env('XENDIT_INVOICE_AMOUNT', 100000),

    // expiry invoice (menit)
    'invoice_expiry_minutes' => (int) env('XENDIT_INVOICE_EXPIRY_MINUTES', 1440),

    // optional redirect url (boleh null)
    'success_redirect_url' => env('XENDIT_SUCCESS_REDIRECT_URL', null),
    'failure_redirect_url' => env('XENDIT_FAILURE_REDIRECT_URL', null),
];
