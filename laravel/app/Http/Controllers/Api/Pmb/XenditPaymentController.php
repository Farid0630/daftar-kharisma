<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PmbPayment;
use App\Services\Payments\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class XenditPaymentController extends Controller
{
    public function createInvoice(Request $request, XenditService $xendit)
    {
        $data = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1'],
            'payer_email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:255'],

            // redirect optional
            'success_redirect_url' => ['nullable', 'url'],
            'failure_redirect_url' => ['nullable', 'url'],
        ]);

        $amount = $data['amount'] ?? (int) config('xendit.default_amount');
        $externalId = 'PMB-' . Carbon::now()->format('YmdHis') . '-' . Str::upper(Str::random(6));

        // Minimal payload create invoice :contentReference[oaicite:5]{index=5}
        $payload = [
            'external_id' => $externalId,
            'amount' => $amount,
            'currency' => config('xendit.currency', 'IDR'),
            'payer_email' => $data['payer_email'] ?? null,
            'description' => $data['description'] ?? 'Pembayaran Formulir PMB',
        ];

        if (!empty($data['success_redirect_url'])) $payload['success_redirect_url'] = $data['success_redirect_url'];
        if (!empty($data['failure_redirect_url'])) $payload['failure_redirect_url'] = $data['failure_redirect_url'];

        $invoice = $xendit->createInvoice($payload);

        // Simpan agar bisa diverifikasi di backend (jangan percaya status dari frontend)
        $payment = PmbPayment::create([
            'external_id' => $externalId,
            'invoice_id' => $invoice['id'] ?? null,
            'invoice_url' => $invoice['invoice_url'] ?? null,
            'amount' => $amount,
            'currency' => $payload['currency'],
            'payer_email' => $data['payer_email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'status' => 'pending',
            'invoice_payload' => $invoice,
        ]);

        return response()->json([
            'message' => 'Invoice berhasil dibuat.',
            'data' => [
                'external_id' => $payment->external_id,
                'invoice_id' => $payment->invoice_id,
                'invoice_url' => $payment->invoice_url,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ],
        ]);
    }

    // Webhook endpoint (dipanggil oleh Xendit)
    public function webhookInvoice(Request $request, XenditService $xendit)
    {
        // Validasi callback token: header x-callback-token :contentReference[oaicite:6]{index=6}
        $token = $request->header('x-callback-token');
        if (!$xendit->verifyCallbackToken($token)) {
            return response()->json(['message' => 'Invalid callback token'], 401);
        }

        $payload = $request->all();

        // Untuk invoice, Xendit biasanya mengirim external_id & status (PAID/PENDING/EXPIRED)
        $externalId = $payload['external_id'] ?? null;
        $statusRaw = strtoupper((string) ($payload['status'] ?? ''));

        if (!$externalId) {
            return response()->json(['message' => 'external_id missing'], 422);
        }

        $payment = PmbPayment::where('external_id', $externalId)->first();
        if (!$payment) {
            // tetap 200 agar Xendit tidak retry tanpa henti, tapi log internal sebaiknya dibuat
            return response()->json(['message' => 'payment not found'], 200);
        }

        $mapped = match ($statusRaw) {
            'PAID', 'SETTLED' => 'paid',
            'EXPIRED' => 'expired',
            default => 'pending',
        };

        $payment->status = $mapped;

        if ($mapped === 'paid') {
            $paidAt = $payload['paid_at'] ?? null;
            $payment->paid_at = $paidAt ? Carbon::parse($paidAt) : Carbon::now();
        }

        $payment->webhook_payload = $payload;
        $payment->save();

        return response()->json(['message' => 'ok']);
    }

    // Optional: polling endpoint untuk frontend cek status
    public function checkStatus(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['required', 'string'],
        ]);

        $payment = PmbPayment::where('external_id', $data['external_id'])->firstOrFail();

        return response()->json([
            'data' => [
                'external_id' => $payment->external_id,
                'status' => $payment->status,
                'paid_at' => optional($payment->paid_at)->toISOString(),
            ],
        ]);
    }
}
