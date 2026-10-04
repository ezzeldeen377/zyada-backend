<?php

namespace App\Services;

use App\Mail\PlaceOrder;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class OrderPaymentReceipt
{
    public static function recipientFor(Order $order): ?string
    {
        if ((int) $order->is_guest === 1) {
            $address = json_decode($order->delivery_address, true);
            $email = $address['contact_person_email'] ?? null;
        } else {
            $email = $order->customer?->email;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public static function mailableFor(Order $order): PlaceOrder
    {
        return new PlaceOrder($order->id, paymentConfirmation: true);
    }

    public static function send(Order $order): void
    {
        if (! config('mail.status')) {
            return;
        }

        $email = self::recipientFor($order);

        if ($email) {
            Mail::to($email)->send(self::mailableFor($order));
        }
    }
}
