# Telr Payment Gateway Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Telr Hosted Payment Page payments to customer orders, wallet top-ups, and vendor subscriptions, with complete admin configuration and verified callbacks.

**Architecture:** Add a small Telr service for request construction, HTTP calls, status interpretation, and webhook signatures. A controller adapts that service to the existing `PaymentRequest` success/failure hooks. Extend the existing gateway allowlists and generic payment route map so every existing flow can select `telr`.

**Tech Stack:** Laravel/PHP, PHPUnit, Laravel HTTP client, existing `addon_settings` gateway configuration.

---

### Task 1: Add failing unit tests for Telr request and verification behavior

**Files:**
- Create: `tests/Unit/TelrPaymentServiceTest.php`

- [ ] **Step 1: Write the failing tests**

Cover these exact behaviors: a create payload includes `method`, numeric store, auth key, test flag, amount, currency, unique cart ID, description, three return URLs, optional panels, and customer email; a check payload uses `method: check` and the returned Telr order reference; status code `3` is paid while `1`, `-1`, `-2`, and `-3` are not; the webhook signature is SHA-1 over Telr’s documented colon-separated transaction field order and rejects a changed field.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `./vendor/bin/phpunit tests/Unit/TelrPaymentServiceTest.php`

Expected: FAIL because `App\Services\TelrPaymentService` does not exist.

### Task 2: Implement the Telr service

**Files:**
- Create: `app/Services/TelrPaymentService.php`
- Test: `tests/Unit/TelrPaymentServiceTest.php`

- [ ] **Step 1: Implement minimal service behavior**

Expose `createPayload(PaymentRequest, array $returnUrls, object|array|null $payer)`, `checkPayload(string $reference)`, `isPaid(array $response)`, `isAuthorisedTransaction(array $webhook)`, and `verifyWebhook(array $webhook)`. Use `Http::acceptJson()->post('https://secure.telr.com/gateway/order.json', ...)` for create/check. Select `live_values` or `test_values` using the row’s `mode`; use Telr’s `test` value of `0` for live and `1` for test. Throw a controlled exception when credentials or Telr’s `order.ref`/`order.url` are missing.

- [ ] **Step 2: Run the focused tests**

Run: `./vendor/bin/phpunit tests/Unit/TelrPaymentServiceTest.php`

Expected: PASS.

### Task 3: Add the Telr payment controller and routes

**Files:**
- Create: `app/Http/Controllers/TelrPaymentController.php`
- Modify: `routes/web.php`
- Modify: `app/Traits/Payment.php`

- [ ] **Step 1: Write the controller**

Load the `telr` `PaymentRequest`, redirect to Telr’s `order.url`, store the Telr reference in `PaymentRequest.transaction_id` without marking it paid, and use the three return actions to call Telr `check`. On paid status, update `payment_method=telr`, `is_paid=1`, `transaction_id` to the Telr transaction/order reference, and call the existing success hook exactly once. On failed/cancelled/invalid/mismatched responses, call the existing failure hook and return `payment-fail`/`payment-cancel`.

- [ ] **Step 2: Add routes**

Register `payment/telr/pay`, `payment/telr/authorised`, `payment/telr/declined`, `payment/telr/cancelled`, and `payment/telr/webhook`; remove CSRF middleware from gateway callbacks/webhook. Add `'telr' => 'payment/telr/pay'` to `Payment::generate_link`.

- [ ] **Step 3: Run route and syntax checks**

Run: `php artisan route:list --path=payment/telr` and `php -l app/Http/Controllers/TelrPaymentController.php`.

Expected: all five Telr routes are listed and syntax check reports no errors.

### Task 4: Make Telr selectable and configurable in Admin

**Files:**
- Modify: `app/Http/Controllers/Admin/BusinessSettingsController.php`
- Modify: `resources/lang/en/messages.php`
- Modify: `app/Http/Controllers/Api/V1/ConfigController.php`
- Modify: `app/CentralLogics/Helpers.php`
- Modify: `app/Http/Controllers/Vendor/WalletController.php`
- Modify: `app/Traits/PaymentGatewayTrait.php`
- Modify: `app/Library/Constant.php`

- [ ] **Step 1: Extend all gateway allowlists**

Add `telr` to payment-index loading, request validation, active-gateway filtering, API default-gateway filtering, wallet gateway filtering, and the gateway constants/currency map. Include AED, EGP, SAR, USD, and EUR in the supported currency map.

- [ ] **Step 2: Add Admin fields and validation**

Add Telr validation requiring `store_id` numeric, `authkey`, and `panels` when enabled. Seed the generic admin form’s Telr values through the existing `addon_settings` mechanism with a gateway title of `Telr`; the existing dynamic form will then render the fields and save separate live/test JSON values.

- [ ] **Step 3: Add translation text**

Add the `telr` label to the English messages catalog.

- [ ] **Step 4: Test admin configuration**

Run the existing PHPUnit suite plus a request test that posts disabled Telr settings successfully, rejects enabled Telr settings without Store ID/auth key, and accepts valid enabled settings.

### Task 5: Make Telr available to initialization/update paths

**Files:**
- Modify: `app/Http/Controllers/UpdateController.php`
- Modify: `database/partial/addon_settings.sql`

- [ ] **Step 1: Add Telr default row/migration update support**

Ensure fresh installs and the existing update/import path create an inactive `telr` payment-config row with `live_values` and `test_values` containing `gateway`, `mode`, `status`, `store_id`, `authkey`, and `panels` keys.

- [ ] **Step 2: Verify database initialization**

Run the project’s database/update test or `php artisan migrate:fresh --seed` in the test environment and confirm the Telr row exists without enabling it.

### Task 6: Full verification and handoff

**Files:**
- All files above

- [ ] **Step 1: Run focused tests and static checks**

Run: `./vendor/bin/phpunit tests/Unit/TelrPaymentServiceTest.php`, `./vendor/bin/phpunit`, `git diff --check`, and `php artisan route:list --path=payment/telr`.

- [ ] **Step 2: Review the final diff**

Confirm no credentials, tokens, or raw payment data are committed; confirm duplicate callbacks cannot call success hooks twice; confirm the Admin form has Telr’s Store ID, Authentication Key, panels, mode, status, title, and logo fields.

- [ ] **Step 3: Commit the implementation**

Run: `git add app routes resources database tests docs && git commit -m "feat: add Telr payment gateway"`
