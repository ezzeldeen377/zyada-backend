<?php

namespace Tests\Unit;

use App\Http\Controllers\PaymentController;
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
}
