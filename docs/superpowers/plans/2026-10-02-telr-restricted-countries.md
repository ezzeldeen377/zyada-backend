# Telr Restricted Countries Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prevent orders and Telr payment creation for Telr-restricted countries using ISO-3166 alpha-2 country codes.

**Architecture:** Add one backend compliance service containing the restricted-country list and normalization rules. Store `country_code` in the order delivery-address JSON, reject restricted countries during order creation, and repeat the check immediately before Telr session creation. The Flutter client must hide the same codes and send `country_code`; backend checks remain authoritative.

**Tech Stack:** Laravel/PHP, Eloquent, PHPUnit.

---

### Task 1: Restricted-country service

**Files:**
- Create: `app/Services/TelrComplianceService.php`
- Create: `tests/Unit/TelrComplianceServiceTest.php`

- [ ] **Step 1: Write failing tests** for the ten ISO codes (`IR`, `CU`, `KP`, `SD`, `SS`, `UA`, `SY`, `RU`, `MM`, `YE`), case normalization, whitespace, and non-restricted codes.
- [ ] **Step 2: Run** `./vendor/bin/phpunit tests/Unit/TelrComplianceServiceTest.php` and verify failure because the service does not exist.
- [ ] **Step 3: Implement** `TelrComplianceService::isRestricted(?string $countryCode): bool` and `normalize(?string $countryCode): ?string`.
- [ ] **Step 4: Run** the focused test and verify it passes.

### Task 2: Capture and reject restricted countries at order creation

**Files:**
- Modify: `app/Traits/PlaceNewOrder.php`
- Create: `tests/Unit/PlaceNewOrderComplianceTest.php`

- [ ] **Step 1: Add a failing test** proving a restricted `country_code` is rejected and an allowed code is accepted.
- [ ] **Step 2: Run** the focused test and verify failure.
- [ ] **Step 3: Add** `country_code` to the order request validation for delivery/parcel orders, reject restricted codes with a stable `country_restricted` error, and persist the normalized code in `delivery_address`.
- [ ] **Step 4: Run** focused and full PHPUnit tests.

### Task 3: Block Telr payment creation server-side

**Files:**
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `app/Http/Controllers/TelrPaymentController.php`
- Create: `tests/Unit/TelrPaymentCountryGuardTest.php`

- [ ] **Step 1: Add failing tests** proving Telr payment creation is rejected for restricted order addresses, including direct calls to the Telr payment endpoint.
- [ ] **Step 2: Run** the focused test and verify failure.
- [ ] **Step 3: Add guards** before generating a Telr payment link and before creating a Telr hosted session; never call Telr for a restricted order.
- [ ] **Step 4: Run** `./vendor/bin/phpunit`, `php -l` on changed files, and `git diff --check`.

### Task 4: Flutter integration handoff

**Files:**
- Flutter repository (not present in this workspace)

- [ ] Remove the ten codes from the country selector.
- [ ] Send `country_code` as ISO alpha-2 in order creation requests.
- [ ] Display the backend `country_restricted` error and do not open payment UI.

