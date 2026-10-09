<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DeliveryTipPresentationTest extends TestCase
{
    /** @test */
    public function every_rendered_delivery_tip_row_is_marked_as_hidden(): void
    {
        $expectedMarkers = [
            'resources/views/admin-views/order/order-view.blade.php' => 1,
            'resources/views/admin-views/order/parcel-order-view.blade.php' => 1,
            'resources/views/vendor-views/order/order-view.blade.php' => 1,
            'resources/views/admin-views/order/partials/_invoice.blade.php' => 1,
            'resources/views/order-invoice.blade.php' => 1,
            'resources/views/admin-views/pos/invoice.blade.php' => 2,
            'resources/views/email-templates/new-email-format-3.blade.php' => 1,
            'resources/views/deliveryman-report-invoice.blade.php' => 3,
            'resources/views/admin-views/delivery-man/view/transaction.blade.php' => 2,
            'resources/views/file-exports/deliveryman-earning.blade.php' => 2,
        ];

        foreach ($expectedMarkers as $path => $expectedCount) {
            $contents = file_get_contents(dirname(__DIR__, 2).'/'.$path);

            $this->assertSame(
                $expectedCount,
                substr_count($contents, 'Delivery man tips UI intentionally hidden'),
                "{$path} must keep rendered delivery tips hidden."
            );
        }
    }
}
