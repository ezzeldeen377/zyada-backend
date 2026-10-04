<?php

namespace Tests\Unit;

use App\Mail\PlaceOrder;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderPaymentReceipt;
use PHPUnit\Framework\TestCase;

class OrderPaymentReceiptTest extends TestCase
{
    public function test_signed_in_customer_receipt_uses_the_account_email(): void
    {
        $order = new Order();
        $order->is_guest = 0;
        $order->id = 42;
        $customer = new User();
        $customer->email = 'customer@example.com';
        $order->setRelation('customer', $customer);

        $this->assertSame('customer@example.com', OrderPaymentReceipt::recipientFor($order));
    }

    public function test_guest_customer_receipt_uses_the_checkout_email(): void
    {
        $order = new Order();
        $order->is_guest = 1;
        $order->delivery_address = json_encode(['contact_person_email' => 'guest@example.com']);
        $order->id = 42;

        $this->assertSame('guest@example.com', OrderPaymentReceipt::recipientFor($order));
    }

    public function test_payment_receipt_is_a_payment_confirmation_email(): void
    {
        $order = new Order();
        $order->id = 42;

        $receipt = OrderPaymentReceipt::mailableFor($order);

        $this->assertInstanceOf(PlaceOrder::class, $receipt);
        $this->assertSame('Payment confirmation & receipt', $receipt->subjectLine());
        $this->assertSame(
            'Your payment has been confirmed. This email is your payment receipt and is sent immediately after payment is received, no later than 24 hours after receipt.',
            $receipt->confirmationMessage()
        );
    }
}
