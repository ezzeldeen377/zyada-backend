<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DeliveryFeePresentationTest extends TestCase
{
    /** @test */
    public function every_order_presentation_marks_delivery_fee_output_as_hidden(): void
    {
        $expectedMarkers = [
            'resources/views/admin-views/order/order-view.blade.php' => 2,
            'resources/views/admin-views/order/parcel-order-view.blade.php' => 1,
            'resources/views/vendor-views/order/order-view.blade.php' => 1,
            'resources/views/admin-views/order/partials/_invoice.blade.php' => 2,
            'resources/views/order-invoice.blade.php' => 2,
            'resources/views/admin-views/pos/invoice.blade.php' => 2,
            'resources/views/email-templates/new-email-format-3.blade.php' => 2,
            'resources/views/email-templates/new-email-format-9.blade.php' => 2,
            'resources/views/admin-views/business-settings/email-format-setting/templates/email-format-3.blade.php' => 1,
            'resources/views/admin-views/business-settings/email-format-setting/templates/email-format-9.blade.php' => 1,
        ];

        foreach ($expectedMarkers as $relativePath => $expectedCount) {
            $contents = file_get_contents(dirname(__DIR__, 2).'/'.$relativePath);

            $this->assertSame(
                $expectedCount,
                substr_count($contents, 'Delivery fee UI intentionally hidden'),
                "{$relativePath} must keep each delivery-fee display block hidden."
            );
        }
    }
}
