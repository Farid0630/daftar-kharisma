<?php

namespace App\Http\Controllers\Api\Pmb;

use App\Http\Controllers\Controller;
use App\Models\PmbPayment;
use Illuminate\Http\Request;

class PmbXenditWebhookController extends Controller
{
    /**
     * POST /api/pmb/payments/xendit/webhook
     * Verifikasi header x-callback-token.
     */
    public function handle(Request $request)
    {
        $tokenHeader = (string) $request->header('x-callback-token');
        $expected = (string) config('xendit.callback_token');

        if (!$expected || $tokenHeader !== $expected) {
            return response()->json(['message' => 'Invalid callback token.'], 403);
        }

        $payload = $request->all();

        // invoice payload biasanya punya: id, external_id, status, paid_at, expiry_date, invoice_url
        $externalId = $payload['external_id'] ?? null;
        $invoiceId = $payload['id'] ?? null;
        $status = strtoupper($payload['status'] ?? 'PENDING');

        $payment = null;

        if ($externalId) {
            $payment = PmbPayment::where('external_id', $externalId)->first();
        }
        if (!$payment && $invoiceId) {
            $payment = PmbPayment::where('invoice_id', $invoiceId)->first();
        }

        if (!$payment) {
            return response()->json(['message' => 'Payment record not found.'], 404);
        }

        $payment->status = $status;
        $payment->invoice_url = $payload['invoice_url'] ?? $payment->invoice_url;
        $payment->expiry_date = $payload['expiry_date'] ?? $payment->expiry_date;

        if (!empty($payload['paid_at'])) {
            $payment->paid_at = $payload['paid_at'];
        } elseif (in_array($status, ['PAID', 'SETTLED'], true) && !$payment->paid_at) {
            $payment->paid_at = now();
        }

        $payment->webhook_payload = $payload;
        $payment->save();

        return response()->json(['message' => 'OK']);
    }
}
