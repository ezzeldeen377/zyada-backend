<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\Library\Payer;
use App\Library\Receiver;
use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Prepares Telr native Mobile SDK checkout data at order placement.
 * Any failure returns null so the caller omits token_url/order_url and
 * the app falls back to the hosted /payment-mobile WebView flow.
 */
class TelrMobileCheckout
{
    private const DEFAULT_COUNTRY = 'AE';

    public function prepare(Order $order, Request $request): ?array
    {
        try {
            $service = TelrPaymentService::fromAddonSettings();
            if (! $service) {
                return null;
            }

            $amount = (float) $order->order_amount - (float) $order->partially_paid_amount;
            if ($amount <= 0) {
                return null;
            }

            $customer = $this->customerData($order);
            if (! $customer['email']) {
                return null;
            }

            $currency = strtoupper((string) BusinessSetting::where('key', 'currency')->value('value'));

            $storeLogo = BusinessSetting::where('key', 'logo')->first();
            $payment = new PaymentRequest();
            $payment->payment_amount = $amount;
            $payment->success_hook = 'order_place';
            $payment->failure_hook = 'order_failed';
            $payment->payer_id = $order->user_id;
            $payment->receiver_id = '100';
            $payment->currency_code = $currency;
            $payment->payment_method = 'telr';
            $payment->additional_data = json_encode([
                'business_name' => BusinessSetting::where('key', 'business_name')->value('value'),
                'business_logo' => Helpers::get_full_url('business', $storeLogo?->value, $storeLogo?->storage[0]?->value ?? 'public'),
            ]);
            $payment->payer_information = json_encode(
                (new Payer($customer['name'], $customer['email'], $customer['phone'], ''))->information()
            );
            $payment->receiver_information = json_encode((new Receiver('receiver_name', 'example.png'))->information());
            $payment->external_redirect_link = null;
            $payment->attribute = 'order';
            $payment->attribute_id = $order->id;
            $payment->payment_platform = 'app';
            $payment->save();

            $billing = [
                'email' => $customer['email'],
                'name' => $this->billingName($customer['name']),
                'address' => ['country' => $this->countryCode($request)],
            ];
            $address = json_decode((string) $order->delivery_address, true);
            if (! empty($address['address'])) {
                $billing['address']['line1'] = substr((string) $address['address'], 0, 110);
            }
            if ($customer['phone']) {
                $billing['phone'] = substr($customer['phone'], 0, 25);
            }

            $response = $service->createMobileOrder($payment, $billing, route('telr.webhook'));

            $links = TelrPaymentService::extractMobileLinks($response);
            if (! $links) {
                return null;
            }

            $ref = data_get($response, 'ref');
            if (is_string($ref) && $ref !== '') {
                $payment->update(['transaction_id' => $ref]);
            }

            return $links;
        } catch (\Throwable $exception) {
            Log::error('Telr mobile order creation failed.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function customerData(Order $order): array
    {
        if ($order->is_guest) {
            $address = json_decode((string) $order->delivery_address, true) ?: [];

            return [
                'name' => (string) ($address['contact_person_name'] ?? ''),
                'email' => (string) ($address['contact_person_email'] ?? ''),
                'phone' => (string) ($address['contact_person_number'] ?? ''),
            ];
        }

        $user = User::find($order->user_id);

        return [
            'name' => $user ? trim($user->f_name.' '.$user->l_name) : '',
            'email' => (string) ($user?->email ?? ''),
            'phone' => (string) ($user?->phone ?? ''),
        ];
    }

    private function billingName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [
            'firstName' => substr((string) ($parts[0] ?? ''), 0, 128),
            'lastName' => substr((string) ($parts[1] ?? ''), 0, 128),
        ];
    }

    private function countryCode(Request $request): string
    {
        $code = strtoupper(trim((string) $request->country_code));

        return preg_match('/^[A-Z]{2}$/', $code) ? $code : self::DEFAULT_COUNTRY;
    }
}
