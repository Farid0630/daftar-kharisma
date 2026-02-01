<?php

namespace App\Http\Controllers\Api\Pmb;

use App\Http\Controllers\Controller;
use App\Models\PmbPayment;
use App\Services\Xendit\XenditInvoiceService;
use App\Support\Phone;
use Illuminate\Http\Request;

class PmbXenditPaymentController extends Controller
{
    public function __construct(
        private readonly XenditInvoiceService $xendit
    ) {}

    /**
     * POST /api/pmb/payments/xendit/invoice
     * Body minimal:
     * - method: bank|ewallet (metadata internal)
     * - name
     * - phone
     * - email (optional) atau payer_email (optional)
     */
    public function createInvoice(Request $request)
    {
        $data = $request->validate([
            'jalur' => ['nullable', 'string', 'max:50'],
            'method' => ['required', 'in:bank,ewallet'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'payer_email' => ['nullable', 'email', 'max:255'],
        ]);

        $payerEmail = $data['payer_email'] ?? $data['email'] ?? null;
        $phoneE164 = Phone::normalizeIdToE164($data['phone'], env('WA_COUNTRY_CODE', '62'));

        $externalId = $this->xendit->makeExternalId('PMB-MANDIRI-');
        $amount = (int) config('xendit.invoice_amount', 100000);

        // Payload Xendit: sengaja TIDAK mengunci payment_methods
        // agar tidak kena UNAVAILABLE_PAYMENT_METHOD_ERROR kalau channel belum aktif.
        $payload = [
            'external_id' => $externalId,
            'amount' => $amount,
            'currency' => 'IDR',
            'description' => 'PMB Jalur Mandiri - Biaya Formulir',

            // optional
            'payer_email' => $payerEmail,
            'customer' => [
                'given_names' => $data['name'],
                'email' => $payerEmail,
                'mobile_number' => $phoneE164,
            ],
        ];

        // optional redirect
        if (config('xendit.success_redirect_url')) {
            $payload['success_redirect_url'] = config('xendit.success_redirect_url');
        }
        if (config('xendit.failure_redirect_url')) {
            $payload['failure_redirect_url'] = config('xendit.failure_redirect_url');
        }

        // optional expiry
        $expiryMinutes = (int) config('xendit.invoice_expiry_minutes', 1440);
        if ($expiryMinutes > 0) {
            $payload['invoice_duration'] = $expiryMinutes * 60; // detik (dipakai sebagian akun)
        }

        try {
            $resp = $this->xendit->createInvoice($payload);
        } catch (\Throwable $e) {
            // kirimkan message mentah dari Xendit agar Anda bisa debug 400
            return response()->json([
                'message' => $this->extractXenditError($e->getMessage()),
            ], 400);
        }

        $payment = PmbPayment::create([
            'external_id' => $externalId,
            'invoice_id' => $resp['id'] ?? null,
            'invoice_url' => $resp['invoice_url'] ?? null,
            'amount' => $amount,
            'method' => $data['method'],
            'currency' => $resp['currency'] ?? 'IDR',
            'status' => strtoupper($resp['status'] ?? 'PENDING'),
            'payer_email' => $payerEmail,
            'phone' => $phoneE164,
            'expiry_date' => isset($resp['expiry_date']) ? $resp['expiry_date'] : null,
            'invoice_payload' => $resp,
        ]);

        return response()->json([
            'message' => 'Invoice berhasil dibuat. Silakan lakukan pembayaran.',
            'external_id' => $payment->external_id,
            'invoice_id' => $payment->invoice_id,
            'invoice_url' => $payment->invoice_url,
            'status' => $payment->status,
            'expiry_date' => optional($payment->expiry_date)->toIso8601String(),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
        ]);
    }

    /**
     * GET /api/pmb/payments/xendit/{external_id}
     * Refresh status ke Xendit (kalau invoice_id ada), lalu kembalikan record DB.
     */
    public function show(string $external_id)
    {
        $payment = PmbPayment::where('external_id', $external_id)->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment tidak ditemukan.'], 404);
        }

        // refresh dari Xendit supaya di lokal pun bisa update tanpa webhook
        if ($payment->invoice_id) {
            try {
                $inv = $this->xendit->getInvoice($payment->invoice_id);

                $payment->status = strtoupper($inv['status'] ?? $payment->status);
                $payment->invoice_url = $inv['invoice_url'] ?? $payment->invoice_url;
                $payment->expiry_date = $inv['expiry_date'] ?? $payment->expiry_date;

                // paid_at kadang ada di response tertentu
                if (!empty($inv['paid_at'])) {
                    $payment->paid_at = $inv['paid_at'];
                }

                $payment->invoice_payload = $inv;
                $payment->save();
            } catch (\Throwable $e) {
                // jika gagal refresh, tetap kembalikan data DB terakhir
            }
        }

        return response()->json([
            'external_id' => $payment->external_id,
            'invoice_id' => $payment->invoice_id,
            'invoice_url' => $payment->invoice_url,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'paid_at' => optional($payment->paid_at)->toIso8601String(),
            'expiry_date' => optional($payment->expiry_date)->toIso8601String(),
        ]);
    }

    /**
     * POST /api/pmb/payments/xendit/status
     * Body: external_id
     */
    public function status(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['required', 'string'],
        ]);

        return $this->show($data['external_id']);
    }

    private function extractXenditError(string $raw): string
    {
        // kadang error dari Xendit berupa JSON string
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            // format umum: { "message": "...", "error_code": "..." }
            if (!empty($decoded['message'])) return (string) $decoded['message'];
            if (!empty($decoded['error']['message'])) return (string) $decoded['error']['message'];
        }
        return $raw ?: 'Invalid input data. Please check your request';
    }
}
