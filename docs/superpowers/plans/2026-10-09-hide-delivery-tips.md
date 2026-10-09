# Hide Delivery-Man Tips from Rendered Views Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Suppress displayed delivery-man tip rows everywhere they are rendered while retaining `dm_tips` data and all totals.

**Architecture:** Add labelled Blade comments around the existing tip label-and-amount markup only. Do not alter assignments, arithmetic, controller behavior, data persistence, or delivery-person earning calculations.

**Tech Stack:** Laravel Blade, PHPUnit.

---

## File map

- `tests/Unit/DeliveryTipPresentationTest.php` — verifies every rendered order/invoice/email tip row has a suppression marker.
- `resources/views/admin-views/order/order-view.blade.php` — admin order-detail tip row.
- `resources/views/admin-views/order/parcel-order-view.blade.php` — admin parcel-order tip row.
- `resources/views/vendor-views/order/order-view.blade.php` — vendor order-detail tip row.
- `resources/views/admin-views/order/partials/_invoice.blade.php` — printable admin invoice tip row.
- `resources/views/order-invoice.blade.php` — standalone printable invoice tip row.
- `resources/views/admin-views/pos/invoice.blade.php` — both POS receipt tip rows.
- `resources/views/email-templates/new-email-format-3.blade.php` — transactional-email tip row.

### Task 1: Add a red regression test

**Files:**
- Create: `tests/Unit/DeliveryTipPresentationTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run it and confirm the expected red state**

Run: `./vendor/bin/phpunit tests/Unit/DeliveryTipPresentationTest.php`

Expected: FAIL because no target template contains the suppression marker.

### Task 2: Comment only rendered delivery-tip blocks

**Files:**
- Modify: `resources/views/admin-views/order/order-view.blade.php:999-1001`
- Modify: `resources/views/admin-views/order/parcel-order-view.blade.php:310-315`
- Modify: `resources/views/vendor-views/order/order-view.blade.php:629-631`
- Modify: `resources/views/admin-views/order/partials/_invoice.blade.php:280-284`
- Modify: `resources/views/order-invoice.blade.php:532-540`
- Modify: `resources/views/admin-views/pos/invoice.blade.php:221-225,232-236`
- Modify: `resources/views/email-templates/new-email-format-3.blade.php:529-536`

- [ ] **Step 1: Wrap each existing visible tip row in a labelled Blade comment**

```blade
{{-- Delivery man tips UI intentionally hidden; preserve dm_tips data and totals for future use.
<dt class="col-6">{{ translate('messages.delivery_man_tips') }}</dt>
<dd class="col-6">
    + {{ \App\CentralLogics\Helpers::format_currency($order->dm_tips) }}
</dd>
--}}
```

Keep all `$order->dm_tips` arithmetic outside the comments. Do not alter delivery-person earnings, report calculations, API responses, or business settings.

- [ ] **Step 2: Run focused verification**

Run: `./vendor/bin/phpunit tests/Unit/DeliveryTipPresentationTest.php`

Expected: PASS with one test and seven template assertions.

- [ ] **Step 3: Compile and check the changed views**

Run:

```bash
php artisan view:clear
php artisan view:cache
git diff --check
```

Expected: Blade compilation succeeds and `git diff --check` has no whitespace errors.

- [ ] **Step 4: Commit**

```bash
git add tests/Unit/DeliveryTipPresentationTest.php resources/views
git commit -m "feat: hide delivery man tips from rendered views"
```
