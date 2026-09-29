<?php

namespace Tests\Unit;

use App\Models\PaymentRequest;
use App\Services\TelrPaymentService;
use PHPUnit\Framework\TestCase;

class TelrPaymentServiceTest extends TestCase
{
    private function service(): TelrPaymentService
    {
        return new TelrPaymentService([
            'store_id' => '1234',
            'authkey' => 'secret-key',
            'webhook_secret' => 'webhook-secret',
            'mode' => 'test',
            'panels' => 'card,applepay',
        ]);
    }

    public function test_create_payload_contains_telr_hosted_page_fields(): void
    {
        $payment = new PaymentRequest();
        $payment->id = 'payment-123';
        $payment->payment_amount = '10.50';
        $payment->currency_code = 'AED';

        $payload = $this->service()->createPayload(
            $payment,
            [
                'authorised' => 'https://example.test/telr/authorised',
                'declined' => 'https://example.test/telr/declined',
                'cancelled' => 'https://example.test/telr/cancelled',
            ],
            ['email' => 'customer@example.test', 'name' => 'Test Customer', 'phone' => '0500000000']
        );

        $this->assertSame('create', $payload['method']);
        $this->assertSame(1234, $payload['store']);
        $this->assertSame('secret-key', $payload['authkey']);
        $this->assertSame(1, $payload['order']['test']);
        $this->assertSame('10.50', $payload['order']['amount']);
        $this->assertSame('AED', $payload['order']['currency']);
        $this->assertSame('payment-123', $payload['order']['cartid']);
        $this->assertSame('card,applepay', $payload['panels']);
        $this->assertSame('customer@example.test', $payload['customer']['email']);
        $this->assertSame('https://example.test/telr/authorised', $payload['return']['authorised']);
    }

    public function test_check_payload_and_paid_status_use_telr_order_reference(): void
    {
        $this->assertSame([
            'method' => 'check',
            'store' => 1234,
            'authkey' => 'secret-key',
            'order' => ['ref' => 'telr-order-ref'],
        ], $this->service()->checkPayload('telr-order-ref'));

        $this->assertTrue($this->service()->isPaid(['order' => ['status' => ['code' => 3]]]));
        $this->assertFalse($this->service()->isPaid(['order' => ['status' => ['code' => -3]]]));
    }

    public function test_webhook_signature_is_verified_using_telr_documented_field_order(): void
    {
        $webhook = [
            'tran_store' => '1234',
            'tran_type' => 'sale',
            'tran_class' => 'ecom',
            'tran_test' => '1',
            'tran_ref' => 'txn-ref',
            'tran_prevref' => 'txn-ref',
            'tran_firstref' => 'txn-ref',
            'tran_order' => 'telr-order-ref',
            'tran_currency' => 'AED',
            'tran_amount' => '10.50',
            'tran_cartid' => 'payment-123',
            'tran_desc' => 'Payment payment-123',
            'tran_status' => 'A',
            'tran_authcode' => 'AUTH',
            'tran_authmessage' => 'Authorised',
        ];
        $webhook['tran_check'] = TelrPaymentService::signWebhook($webhook, 'webhook-secret');

        $this->assertTrue($this->service()->verifyWebhook($webhook));
        $webhook['tran_amount'] = '99.00';
        $this->assertFalse($this->service()->verifyWebhook($webhook));
    }
}
