# Hide Delivery Fees from Rendered Order UI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hide delivery-fee presentation in all order-detail, receipt/invoice, and transactional-email Blade views without changing saved delivery fees or order calculations.

**Architecture:** Apply labelled Blade comments only around markup that outputs a delivery fee/charge. Keep every variable assignment and total calculation active so data persistence, APIs, payment totals, and accounting remain unchanged. A source-level PHPUnit regression test records each view that must carry the suppression marker.

**Tech Stack:** Laravel, Blade templates, PHPUnit.

---

## File map

- `tests/Unit/DeliveryFeePresentationTest.php` — verifies all in-scope templates retain a display-suppression marker.
- `resources/views/admin-views/order/order-view.blade.php` — hides admin standard-order summary card and total-row fee output.
- `resources/views/admin-views/order/parcel-order-view.blade.php` — hides admin parcel-order fee summary card.
- `resources/views/vendor-views/order/order-view.blade.php` — hides vendor order-detail fee row.
- `resources/views/admin-views/order/partials/_invoice.blade.php` — hides both print-invoice delivery-fee representations.
- `resources/views/order-invoice.blade.php` — hides standalone printable invoice parcel-price and delivery-fee rows.
- `resources/views/admin-views/pos/invoice.blade.php` — hides both POS receipt delivery-fee representations.
- `resources/views/email-templates/new-email-format-3.blade.php` and `new-email-format-9.blade.php` — hide customer email parcel-price and delivery-fee rows.
- `resources/views/admin-views/business-settings/email-format-setting/templates/email-format-3.blade.php` and `email-format-9.blade.php` — hide matching email-template preview rows.

### Task 1: Add a red regression test for the presentation boundary

**Files:**
- Create: `tests/Unit/DeliveryFeePresentationTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run the test and confirm it fails before markup changes**

Run: `./vendor/bin/phpunit tests/Unit/DeliveryFeePresentationTest.php`

Expected: FAIL because no template contains `Delivery fee UI intentionally hidden` yet.

- [ ] **Step 3: Commit the red test only if the team’s commit policy accepts red commits**

Do not commit a failing test in this repository by default; continue directly to the minimal view-only implementation.

### Task 2: Hide order details and invoice delivery-fee presentation

**Files:**
- Modify: `resources/views/admin-views/order/order-view.blade.php:459-464,987-997`
- Modify: `resources/views/admin-views/order/parcel-order-view.blade.php:283-289`
- Modify: `resources/views/vendor-views/order/order-view.blade.php:632-638`
- Modify: `resources/views/admin-views/order/partials/_invoice.blade.php:122-128,273-279`
- Modify: `resources/views/order-invoice.blade.php:323-330,520-529`
- Modify: `resources/views/admin-views/pos/invoice.blade.php:93-99,226-231`

- [ ] **Step 1: Wrap only the display blocks in labelled Blade comments**

Use the same reversible wrapper at each listed delivery-fee row or summary card:

```blade
{{-- Delivery fee UI intentionally hidden; preserve delivery_charge data and totals for future use. --}}
{{--
<tr>
    <td>{{ translate('messages.delivery_charge') }}</td>
    <td>{{ \App\CentralLogics\Helpers::format_currency($order->delivery_charge) }}</td>
</tr>
--}}
```

For `dt`/`dd` summary markup, place the existing adjacent `<dt>` and `<dd>` inside the same wrapper. Do not comment out `@php($del_c = ...)`, variables used by totals, refund logic, or `$order->delivery_charge` arithmetic.

- [ ] **Step 2: Re-run the focused test**

Run: `./vendor/bin/phpunit tests/Unit/DeliveryFeePresentationTest.php`

Expected: Still FAIL because email views are not marked yet; the failure identifies only those unmarked email templates.

### Task 3: Hide transactional email and email-preview delivery-fee presentation

**Files:**
- Modify: `resources/views/email-templates/new-email-format-3.blade.php:334-341,517-526`
- Modify: `resources/views/email-templates/new-email-format-9.blade.php:272-279,416-422`
- Modify: `resources/views/admin-views/business-settings/email-format-setting/templates/email-format-3.blade.php:104-110`
- Modify: `resources/views/admin-views/business-settings/email-format-setting/templates/email-format-9.blade.php:103-109`

- [ ] **Step 1: Wrap the email-only fee output in the same labelled Blade comments**

For formats 3 and 9, hide both the parcel line that formats `$order->delivery_charge` and the delivery-charge total row. Retain `$total_shipping_cost = $order->delivery_charge;` and all order-total calculations. In preview templates, hide only the preview table row containing `translate('Delivery_Charge')`.

- [ ] **Step 2: Run the focused regression test**

Run: `./vendor/bin/phpunit tests/Unit/DeliveryFeePresentationTest.php`

Expected: PASS with 1 test and 10 assertions groups (one per targeted template).

- [ ] **Step 3: Run syntax and repository checks**

Run:

```bash
php -l tests/Unit/DeliveryFeePresentationTest.php
./vendor/bin/phpunit tests/Unit/DeliveryFeePresentationTest.php
git diff --check
git diff -- resources/views tests/Unit/DeliveryFeePresentationTest.php
```

Expected: PHP syntax reports no errors, PHPUnit passes, whitespace check is clean, and the diff contains only Blade comments plus the regression test.

- [ ] **Step 4: Commit the finished implementation**

```bash
git add tests/Unit/DeliveryFeePresentationTest.php \
  resources/views/admin-views/order/order-view.blade.php \
  resources/views/admin-views/order/parcel-order-view.blade.php \
  resources/views/vendor-views/order/order-view.blade.php \
  resources/views/admin-views/order/partials/_invoice.blade.php \
  resources/views/order-invoice.blade.php \
  resources/views/admin-views/pos/invoice.blade.php \
  resources/views/email-templates/new-email-format-3.blade.php \
  resources/views/email-templates/new-email-format-9.blade.php \
  resources/views/admin-views/business-settings/email-format-setting/templates/email-format-3.blade.php \
  resources/views/admin-views/business-settings/email-format-setting/templates/email-format-9.blade.php
git commit -m "feat: hide delivery fees from order presentations"
```
