<?php

namespace App\Services;

use App\CentralLogics\CustomerLogic;
use App\CentralLogics\Helpers;
use App\CentralLogics\OrderLogic;
use App\CentralLogics\ProductLogic;
use App\Mail\OrderVerificationMail;
use App\Models\Box;
use App\Models\Cart;
use App\Models\CashBackHistory;
use App\Models\Item;
use App\Models\ItemCampaign;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PendingOrderCheckout;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PendingOrderService
{
    public function createOrderFromPending(
        PendingOrderCheckout $pendingCheckout,
        string $paymentMethod,
        ?string $transactionRef = null
    ): ?Order {
        $order = DB::transaction(function () use ($pendingCheckout, $paymentMethod, $transactionRef) {
            /** @var PendingOrderCheckout|null $pending */
            $pending = PendingOrderCheckout::whereKey($pendingCheckout->id)->lockForUpdate()->first();
            if (! $pending) {
                return null;
            }

            if ($pending->status === 'completed' && $pending->order_id) {
                return Order::find($pending->order_id);
            }

            $payload = $pending->payload ?? [];
            $orderAttributes = $payload['order_attributes'] ?? [];
            $orderDetails = $payload['order_details'] ?? [];
            $orderTaxIds = $payload['order_tax_ids'] ?? [];
            $cartIds = $payload['cart_ids'] ?? [];
            $isBuyNow = (bool) ($payload['is_buy_now'] ?? false);
            $isPartialPayment = (bool) ($payload['partial_payment'] ?? false);
            $pAmount = (float) ($payload['p_amount'] ?? 0);
            $unpaidAmount = (float) ($payload['unpaid_amount'] ?? 0);
            $orderType = $orderAttributes['order_type'] ?? 'delivery';

            $order = new Order();
            foreach ($orderAttributes as $key => $value) {
                $order->{$key} = $value;
            }

            $order->order_status = 'confirmed';
            $order->payment_status = 'paid';
            $order->confirmed = now();
            if ($order->payment_method !== 'partial_payment') {
                $order->payment_method = $paymentMethod;
            }
            if ($transactionRef) {
                $order->transaction_reference = $transactionRef;
            }
            $order->save();

            if ($orderType !== 'parcel' && ! empty($orderDetails)) {
                foreach ($orderDetails as $index => $detail) {
                    $orderDetails[$index]['order_id'] = $order->id;
                }
                OrderDetail::insert($orderDetails);

                foreach ($orderDetails as $detail) {
                    $qty = (int) ($detail['quantity'] ?? 1);
                    if (! empty($detail['box_id'])) {
                        Box::where('id', $detail['box_id'])->update([
                            'available_count' => DB::raw('GREATEST(0, available_count - ' . $qty . ')'),
                        ]);
                    } elseif (! empty($detail['item_id'])) {
                        $item = Item::find($detail['item_id']);
                        if ($item) {
                            $variant = '';
                            $variations = is_string($detail['variation'] ?? null)
                                ? json_decode($detail['variation'], true)
                                : ($detail['variation'] ?? []);
                            if (is_array($variations) && isset($variations[0]['type'])) {
                                $variant = $variations[0]['type'];
                            }
                            ProductLogic::update_stock($item, $qty, $variant)->save();
                            ProductLogic::update_flash_stock($item, $qty)?->save();
                        }
                    } elseif (! empty($detail['item_campaign_id'])) {
                        $campaign = ItemCampaign::find($detail['item_campaign_id']);
                        if ($campaign) {
                            $variant = '';
                            $variations = is_string($detail['variation'] ?? null)
                                ? json_decode($detail['variation'], true)
                                : ($detail['variation'] ?? []);
                            if (is_array($variations) && isset($variations[0]['type'])) {
                                $variant = $variations[0]['type'];
                            }
                            ProductLogic::update_stock($campaign, $qty, $variant)->save();
                        }
                    }
                }

                if ($order->store_id) {
                    $store = Store::find($order->store_id);
                    if ($store) {
                        $store->increment('total_order');
                        if ($store->is_valid_subscription == 1 && $store->store_sub && $store->store_sub->max_order != 'unlimited' && $store->store_sub->max_order > 0) {
                            $store->store_sub->decrement('max_order', 1);
                        }
                    }
                }
            }

            if (! empty($orderTaxIds)) {
                \Modules\TaxModule\Services\CalculateTaxService::updateOrderTaxData(
                    orderId: $order->id,
                    orderTaxIds: $orderTaxIds,
                );
            }

            if (! $isBuyNow && ! empty($cartIds)) {
                Cart::whereIn('id', $cartIds)->delete();
            }

            if (! $order->is_guest && $order->user_id) {
                $customer = User::find($order->user_id);
                if ($customer) {
                    $customer->zone_id = $order->zone_id;
                    $customer->save();
                }

                if ($isPartialPayment && $pAmount > 0) {
                    $order->partially_paid_amount = $pAmount;
                    $order->save();
                    CustomerLogic::create_wallet_transaction($order->user_id, $pAmount, 'partial_payment', $order->id);
                    OrderLogic::create_order_payment(order_id: $order->id, amount: $pAmount, payment_status: 'paid', payment_method: 'wallet');
                    OrderLogic::create_order_payment(order_id: $order->id, amount: $unpaidAmount, payment_status: 'paid', payment_method: $paymentMethod);
                }

                $this->createCashBackHistory(max(0, (float) $order->order_amount - (float) ($order->digital_payment_fee ?? 0)), $order->user_id, $order->id);
            }

            $pending->status = 'completed';
            $pending->order_id = $order->id;
            $pending->save();

            return $order;
        });

        if (! $order) {
            return null;
        }

        try {
            Helpers::send_order_notification($order);
            OrderPaymentReceipt::send($order);
            $address = json_decode((string) $order->delivery_address, true);

            if (Helpers::getNotificationStatusData('customer', 'customer_delivery_verification', 'mail_status')
                && Helpers::get_mail_status('order_verification_mail_status_user') == 1
                && config('mail.status')
            ) {
                if (config('order_delivery_verification') == 1 && $order->is_guest == 0 && $order->customer) {
                    Mail::to($order->customer->email)->send(new OrderVerificationMail($order->otp, $order->customer->f_name));
                }

                if ($order->is_guest == 1 && isset($address['contact_person_email'])) {
                    Mail::to($address['contact_person_email'])->send(new OrderVerificationMail($order->otp, $order?->customer?->f_name));
                }
            }
        } catch (\Throwable $e) {
            info($e->getMessage());
        }

        return $order;
    }

    private function createCashBackHistory(float $orderAmount, int $userId, int $orderId): void
    {
        $cashBack = Helpers::getCalculatedCashBackAmount(amount: $orderAmount, customer_id: $userId);
        if (data_get($cashBack, 'calculated_amount') > 0) {
            $cashBackHistory = new CashBackHistory();
            $cashBackHistory->user_id = $userId;
            $cashBackHistory->order_id = $orderId;
            $cashBackHistory->calculated_amount = data_get($cashBack, 'calculated_amount');
            $cashBackHistory->cashback_amount = data_get($cashBack, 'cashback_amount');
            $cashBackHistory->cash_back_id = data_get($cashBack, 'id');
            $cashBackHistory->cashback_type = data_get($cashBack, 'cashback_type');
            $cashBackHistory->min_purchase = data_get($cashBack, 'min_purchase');
            $cashBackHistory->max_discount = data_get($cashBack, 'max_discount');
            $cashBackHistory->save();

            $cashBackHistory?->order()->update([
                'cash_back_id' => $cashBackHistory->id,
            ]);
        }
    }
}
