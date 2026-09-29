<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\PaymentRequest;
use Illuminate\Http\Request;
use App\Models\BusinessSetting;
use App\Library\Payer;
use App\Traits\Payment;
use App\Library\Receiver;
use App\Library\Payment as PaymentInfo;

class PaymentController extends Controller
{
    public function __construct(){
        if (is_dir('App\Traits') && trait_exists('App\Traits\Payment')) {
            $this->extendWithPaymentGatewayTrait();
        }
    }

    private function extendWithPaymentGatewayTrait()
    {
        $extendedControllerClass = $this->generateExtendedControllerClass();
        eval($extendedControllerClass);
    }

    private function generateExtendedControllerClass()
    {
        $baseControllerClass = get_class($this);
        $traitClassName = 'App\Traits\Payment';

        $extendedControllerClass = "
            class ExtendedController extends $baseControllerClass {
                use $traitClassName;
            }
        ";

        return $extendedControllerClass;
    }
    public function payment(Request $request)
    {
        if ($request->has('callback')) {
            Order::where(['id' => $request->order_id])->update(['callback' => $request['callback']]);
        }

        session()->put('customer_id', $request['customer_id']);
        session()->put('payment_platform', $request['payment_platform']);
        session()->put('order_id', $request->order_id);

        $order = Order::where(['id' => $request->order_id, 'user_id' => $request['customer_id']])->first();
        if(!$order){
            return response()->json(['errors' => ['code' => 'order-payment', 'message' => 'Data not found']], 403);
        }
        if($order->is_guest){
            $customer_details = json_decode($order['delivery_address'],true);
        }else{
            $customer = User::find($request['customer_id']);
        }

        //guest user check
        if ($order->is_guest) {
            $address = json_decode($order['delivery_address'],true);
            $customer = collect([
                'first_name' => $address['contact_person_name'],
                'last_name' => '',
                'phone' => $address['contact_person_number'],
                'email' => $address['contact_person_email'],
            ]);

        } else {
            $customer = User::find($request['customer_id']);
            $customer = collect([
                'first_name' => $customer['f_name'],
                'last_name' => $customer['l_name'],
                'phone' => $customer['phone'],
                'email' => $customer['email'],
            ]);
        }


        if (session()->has('payment_method') == false) {
            session()->put('payment_method', 'ssl_commerz_payment');
        }

        $order_amount = $order->order_amount - $order->partially_paid_amount;

        if (!isset($customer)) {
            return response()->json(['errors' => ['message' => 'Customer not found']], 403);
        }

        if (!isset($order_amount)) {
            return response()->json(['errors' => ['message' => 'Amount not found']], 403);
        }

        if (!$request->has('payment_method')) {
            return response()->json(['errors' => ['message' => 'Payment not found']], 403);
        }

        $payer = new Payer($customer['first_name'].' '.$customer['last_name'], $customer['email'], $customer['phone'], '');

        $currency=BusinessSetting::where(['key'=>'currency'])->first()->value;

        $store_logo= BusinessSetting::where(['key' => 'logo'])->first();
        $additional_data = [
            'business_name' => BusinessSetting::where(['key'=>'business_name'])->first()?->value,
            'business_logo' => \App\CentralLogics\Helpers::get_full_url('business',$store_logo?->value,$store_logo?->storage[0]?->value ?? 'public' )
        ];

        $payment_info = new PaymentInfo(
            success_hook: 'order_place',
            failure_hook: 'order_failed',
            currency_code: $currency,
            payment_method: $request->payment_method,
            payment_platform: $request['payment_platform'],
            payer_id: $request['customer_id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $order_amount,
            external_redirect_link: $request->has('callback')?$request['callback']:session('callback'),
            attribute: 'order',
            attribute_id: $order->id
        );

        $receiver_info = new Receiver('receiver_name','example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return redirect($redirect_link);

    }

    public function success()
    {
        $order = Order::where(['id' => session('order_id'), 'user_id'=>session('customer_id')])->first();
        if (isset($order) && $order->callback != null) {
            return redirect($order->callback . '&status=success');
        }
        return response()->json(['message' => 'Payment succeeded'], 200);
    }

    public function fail(Request $request)
    {
        $order = Order::where(['id' => session('order_id'), 'user_id'=>session('customer_id')])->first();

        // Telr can confirm the payment through its webhook after the browser
        // has already reached the declined/failed return URL. Reconcile that
        // delayed confirmation before reporting a failure to the client.
        $tokenData = self::paymentTokenData($request->input('token'));
        if (($tokenData['payment_method'] ?? null) === 'telr' && !empty($tokenData['attribute_id'])) {
            $payment = PaymentRequest::where('attribute_id', (int) $tokenData['attribute_id'])
                ->where('payment_method', 'telr')
                ->where('is_paid', 1)
                ->latest('created_at')
                ->first();

            if ($payment) {
                if (isset($order) && $order->callback != null) {
                    return redirect($order->callback . '&status=success');
                }

                return response()->json(['message' => 'Payment succeeded'], 200);
            }
        }

        if (isset($order) && $order->callback != null) {
            return redirect($order->callback . '&status=fail');
        }
        return response()->json(['message' => 'Payment failed'], 403);
    }

    public static function paymentTokenData(?string $token): array
    {
        if (!$token) {
            return [];
        }

        $decoded = base64_decode($token, true);
        if (!is_string($decoded)) {
            return [];
        }

        parse_str(str_replace('&&', '&', $decoded), $data);

        return is_array($data) ? $data : [];
    }
    public function cancel(Request $request)
    {
        $order = Order::where(['id' => session('order_id'), 'user_id'=>session('customer_id')])->first();
        if (isset($order) && $order->callback != null) {
            return redirect($order->callback . '&status=fail');
        }
        return response()->json(['message' => 'Payment failed'], 403);
    }

}
