<?php

namespace Tests\Unit;

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TelrPaymentController;
use App\Models\Order;
use PHPUnit\Framework\TestCase;

class PaymentControllerTest extends TestCase
{
    public function test_payment_token_data_decodes_telr_return_token(): void
    {
        $token = base64_encode('payment_method=telr&&attribute_id=100019&&transaction_reference=030126798359');

        $this->assertSame([
            'payment_method' => 'telr',
            'attribute_id' => '100019',
            'transaction_reference' => '030126798359',
        ], PaymentController::paymentTokenData($token));
    }

    public function test_payment_token_data_ignores_non_string_tokens(): void
    {
        $this->assertSame([], PaymentController::paymentTokenData(['malformed']));
    }

    public function test_telr_token_must_match_the_session_order(): void
    {
        $order = new Order();
        $order->id = 100019;

        $this->assertTrue(PaymentController::telrTokenMatchesOrder(['attribute_id' => '100019'], $order));
        $this->assertTrue(PaymentController::telrTokenMatchesOrder(['attribute_id' => '100019'], $order, null));
        $this->assertFalse(PaymentController::telrTokenMatchesOrder(['attribute_id' => '100019'], $order, '100020'));
        $this->assertFalse(PaymentController::telrTokenMatchesOrder(['attribute_id' => '100020'], $order));
        $this->assertFalse(PaymentController::telrTokenMatchesOrder(['attribute_id' => ['100019']], $order));
        $this->assertFalse(PaymentController::telrTokenMatchesOrder(['attribute_id' => '100019'], null));
    }

    public function test_signed_telr_token_requires_a_valid_signature(): void
    {
        $data = [
            'payment_method' => 'telr',
            'attribute_id' => '100019',
            'transaction_reference' => '030126798359',
        ];
        $payload = 'payment_method=telr&&attribute_id=100019&&transaction_reference=030126798359';
        $data['token_signature'] = hash_hmac('sha256', $payload, 'test-secret');

        $this->assertTrue(PaymentController::hasValidTelrTokenSignature($data, 'test-secret'));
        $data['attribute_id'] = '100020';
        $this->assertFalse(PaymentController::hasValidTelrTokenSignature($data, 'test-secret'));
    }

    public function test_telr_declined_returns_are_verified_but_cancellations_are_not(): void
    {
        $this->assertTrue(TelrPaymentController::shouldVerifyTelrReturn('fail'));
        $this->assertFalse(TelrPaymentController::shouldVerifyTelrReturn('cancel'));
    }
}
