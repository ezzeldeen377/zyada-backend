# Default New Stores to 24/7 Schedules Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ensure every application-created store receives the existing seven-day 24/7 schedule immediately after creation.

**Architecture:** Reuse `StoreLogic::insert_schedule`, whose defaults create days `0`–`6` from `00:00:00` to `23:59:59`. Make invocation unconditional immediately after each Eloquent store save, and add it after inserts in the bulk upsert flow; no update path, activation flag, or schedule-edit path changes.

**Tech Stack:** Laravel, PHP, PHPUnit.

---

## File map

- `tests/Unit/DefaultStoreScheduleTest.php` — source-level regression coverage for every new-store persistence path.
- `app/Http/Controllers/Admin/VendorController.php` — admin store creation and both bulk import insert paths.
- `app/Http/Controllers/VendorController.php` — vendor web registration creation path.
- `app/Http/Controllers/Api/V1/Auth/VendorLoginController.php` — vendor API registration creation path.

### Task 1: Add a red regression test for creation-path schedule wiring

**Files:**
- Create: `tests/Unit/DefaultStoreScheduleTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DefaultStoreScheduleTest extends TestCase
{
    /** @test */
    public function every_new_store_persistence_path_creates_the_default_schedule(): void
    {
        $root = dirname(__DIR__, 2).'/';

        foreach ([
            'app/Http/Controllers/Admin/VendorController.php' => 5,
            'app/Http/Controllers/VendorController.php' => 1,
            'app/Http/Controllers/Api/V1/Auth/VendorLoginController.php' => 1,
        ] as $path => $expectedCalls) {
            $contents = file_get_contents($root.$path);

            $this->assertSame(
                $expectedCalls,
                substr_count($contents, 'StoreLogic::insert_schedule('),
                "{$path} must initialize schedules for every new store path."
            );
        }

        foreach ([
            'app/Http/Controllers/Admin/VendorController.php',
            'app/Http/Controllers/VendorController.php',
            'app/Http/Controllers/Api/V1/Auth/VendorLoginController.php',
        ] as $path) {
            $this->assertStringNotContainsString("['always_open']", file_get_contents($root.$path));
        }
    }

    /** @test */
    public function the_default_schedule_covers_all_days_for_24_hours(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/app/CentralLogics/StoreLogic.php');

        $this->assertStringContainsString('array $days=[0,1,2,3,4,5,6]', $contents);
        $this->assertStringContainsString("String \$opening_time='00:00:00'", $contents);
        $this->assertStringContainsString("String \$closing_time='23:59:59'", $contents);
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run: `./vendor/bin/phpunit tests/Unit/DefaultStoreScheduleTest.php`

Expected: FAIL because `Admin\\VendorController` initially contains four schedule calls (including the manual schedule endpoint) while the test requires the fifth call for newly inserted bulk-upsert rows.

### Task 2: Initialize the default schedule for every new store

**Files:**
- Modify: `app/Http/Controllers/Admin/VendorController.php:148-153,1648-1653`
- Modify: `app/Http/Controllers/VendorController.php:189-194`
- Modify: `app/Http/Controllers/Api/V1/Auth/VendorLoginController.php:220-223`

- [ ] **Step 1: Replace module-gated calls with unconditional post-save calls**

In each Eloquent creation method, replace:

```php
if(config('module.'.$store->module->module_type)['always_open'])
{
    StoreLogic::insert_schedule($store->id);
}
```

with:

```php
StoreLogic::insert_schedule($store->id);
```

Keep the call immediately after `$store->save()`. Do not modify `active`, `status`, or existing scheduling endpoints.

- [ ] **Step 2: Add schedule creation only to the bulk-import insert branch**

In the second bulk-import flow, append the helper directly after `insertGetId` and its storage updates:

```php
$insertedId = DB::table('stores')->insertGetId($store);
Helpers::updateStorageTable(get_class(new Store), $insertedId, $store['logo']);
Helpers::updateStorageTable(get_class(new Store), $insertedId, $store['cover_photo']);
StoreLogic::insert_schedule($insertedId);
```

Do not add this call to the `update($store)` branch, preserving the schedules of existing stores.

- [ ] **Step 3: Run the focused test and verify it passes**

Run: `./vendor/bin/phpunit tests/Unit/DefaultStoreScheduleTest.php`

Expected: PASS with two tests, confirming four creation-path calls and the 24/7 helper defaults.

- [ ] **Step 4: Run syntax and focused regression checks**

Run:

```bash
php -l app/Http/Controllers/Admin/VendorController.php
php -l app/Http/Controllers/VendorController.php
php -l app/Http/Controllers/Api/V1/Auth/VendorLoginController.php
./vendor/bin/phpunit tests/Unit/DefaultStoreScheduleTest.php
git diff --check
```

Expected: PHP reports no syntax errors, the focused test passes, and the whitespace check is clean.

- [ ] **Step 5: Commit the implementation**

```bash
git add tests/Unit/DefaultStoreScheduleTest.php \
  app/Http/Controllers/Admin/VendorController.php \
  app/Http/Controllers/VendorController.php \
  app/Http/Controllers/Api/V1/Auth/VendorLoginController.php
git commit -m "feat: default new stores to 24-7 schedules"
```
