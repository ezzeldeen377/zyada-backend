<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Services\TelrPaymentService;
use App\Traits\Processor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TelrPaymentController extends Controller
{
    use Processor;

    private TelrPaymentService $telr;

    public function __construct()
    {
        $config = $this->payment_config('telr', 'payment_config');
        $mode = $config?->mode ?: 'test';
        $values = $config ? json_decode($config->{$mode.'_values'} ?? '{}', true) : [];
        $this->telr = new TelrPaymentService(array_merge($values ?: [], ['mode' => $mode]));
    }

    public function pay(Request $request)
    {
        $validator = Validator::make($request->all(), ['payment_id' => 'required|uuid']);
        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $payment = PaymentRequest::whereKey($request->string('payment_id'))->where('is_paid', 0)->first();
        if (! $payment) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $urls = [
            'authorised' => route('telr.authorised', ['payment_id' => $payment->id]),
            'declined' => route('telr.declined', ['payment_id' => $payment->id]),
            'cancelled' => route('telr.cancelled', ['payment_id' => $payment->id]),
        ];

        try {
            $payer = json_decode($payment->payer_information ?: '{}');
            $response = $this->telr->create($payment, $urls, $payer);
            $reference = data_get($response, 'order.ref');
            $url = data_get($response, 'order.url');
            if (! $reference || ! $url) {
                throw new \RuntimeException('Telr did not return a payment reference or URL.');
            }

            $payment->update(['transaction_id' => $reference]);

            return redirect()->away($url);
        } catch (\Throwable $exception) {
            Log::error('Telr payment session creation failed.', ['payment_id' => $payment->id, 'error' => $exception->getMessage()]);

            return $this->payment_response($payment, 'fail');
        }
    }

    public function authorised(Request $request): RedirectResponse
    {
        return $this->handleReturn($request, 'success');
    }

    public function declined(Request $request): RedirectResponse
    {
        return $this->handleReturn($request, 'fail');
    }

    public function cancelled(Request $request): RedirectResponse
    {
        return $this->handleReturn($request, 'cancel');
    }

    public function webhook(Request $request): JsonResponse
    {
        $data = $request->all();
        try {
            $signatureValid = $this->telr->verifyWebhook($data);
        } catch (\Throwable) {
            $signatureValid = false;
        }
        if (! $signatureValid) {
            return response()->json(['message' => 'Invalid Telr signature.'], 400);
        }

        $payment = PaymentRequest::whereKey($data['tran_cartid'] ?? null)->first();
        if (! $payment || $payment->is_paid) {
            return response()->json(['received' => true]);
        }

        if (! $this->matchesPayment($payment, $data['tran_amount'] ?? null, $data['tran_currency'] ?? null)) {
            return response()->json(['message' => 'Payment data mismatch.'], 400);
        }

        $authorised = $this->telr->isAuthorisedTransaction($data);
        Log::info('Telr webhook transaction received.', [
            'payment_id' => $payment->id,
            'transaction_reference' => $data['tran_ref'] ?? null,
            'status' => $data['tran_status'] ?? null,
            'authorised' => $authorised,
        ]);

        if ($authorised) {
            $this->settle($payment, $data['tran_ref'] ?? $payment->transaction_id);
        }

        return response()->json(['received' => true]);
    }

    private function handleReturn(Request $request, string $result): RedirectResponse
    {
        $reference = $request->input('OrderRef') ?: $request->input('order_ref');
        $payment = PaymentRequest::whereKey($request->input('payment_id'))->first();
        if (! $payment && $reference) {
            $payment = PaymentRequest::where('transaction_id', $reference)->first();
        }
        if (! $payment) {
            abort(404);
        }

        // A verified Telr webhook can settle the payment before the browser returns.
        // Never turn an already-paid request into a failure on the return path.
        if ($payment->is_paid) {
            return $this->payment_response($payment, 'success');
        }

        if (!self::shouldVerifyTelrReturn($result)) {
            return $this->payment_response($payment, 'cancel');
        }

        $reference = $reference ?: $payment->transaction_id;
        try {
            $response = $this->telr->check((string) $reference);
            $order = (array) data_get($response, 'order', []);
            Log::warning('Telr return decision', [
                'payment_id' => $payment->id,
                'attribute_id' => $payment->attribute_id,
                'result' => $result,
                'reference' => $reference,
                'telr_status' => data_get($response, 'order.status.code'),
                'telr_amount' => data_get($response, 'order.amount'),
                'local_amount' => $payment->payment_amount,
                'telr_currency' => data_get($response, 'order.currency'),
                'local_currency' => $payment->currency_code,
                'is_paid_before_check' => $payment->is_paid,
            ]);
            if (! $this->telr->isPaid($response) || ! $this->matchesPayment($payment, $order['amount'] ?? null, $order['currency'] ?? null)) {
                return $this->payment_response($payment, 'fail');
            }

            $this->settle($payment, data_get($order, 'transaction.ref', $reference));

            return $this->payment_response($payment->fresh(), 'success');
        } catch (\Throwable $exception) {
            Log::error('Telr payment verification failed.', ['payment_id' => $payment->id, 'error' => $exception->getMessage()]);

            return $this->payment_response($payment, 'fail');
        }
    }

    private function settle(PaymentRequest $payment, string $transaction): void
    {
        $updated = PaymentRequest::whereKey($payment->id)->where('is_paid', 0)->update([
            'payment_method' => 'telr',
            'is_paid' => 1,
            'transaction_id' => $transaction,
        ]);
        if (! $updated) {
            return;
        }

        $payment = $payment->fresh();
        if ($payment && function_exists($payment->success_hook)) {
            call_user_func($payment->success_hook, $payment);
        }
    }

    public static function shouldVerifyTelrReturn(string $result): bool
    {
        return in_array($result, ['success', 'fail'], true);
    }

    private function matchesPayment(PaymentRequest $payment, mixed $amount, mixed $currency): bool
    {
        return $amount !== null
            && number_format((float) $amount, 2, '.', '') === number_format((float) $payment->payment_amount, 2, '.', '')
            && strtoupper((string) $currency) === strtoupper((string) $payment->currency_code);
    }
}
