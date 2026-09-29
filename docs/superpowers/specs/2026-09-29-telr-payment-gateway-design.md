# Telr Payment Gateway Design

## Goal

Add Telr as an administrator-configured digital payment gateway for customer checkout, customer wallet top-ups, and vendor subscription payments.

## Chosen integration

Use Telr's Hosted Payment Page API. The application creates a payment session through `https://secure.telr.com/gateway/order.json`, redirects the shopper to the returned payment URL, and verifies the Telr order reference server-to-server before recording a payment. This avoids handling card data in the application.

## Configuration

The Admin **Business Settings → Payment Method** page will expose Telr with separate test and live credentials:

- Store ID
- Authentication Key
- Payment panels (optional comma-separated Telr panel values, such as `card,applepay`)
- Gateway enabled/disabled status

The existing environment selection determines whether `test_values` or `live_values` are used. Credentials remain in `addon_settings`, consistent with the existing gateways; they are never sent to clients or written to logs.

## Payment flow

1. The shared gateway selector includes `telr` when Digital Payment and Telr are enabled.
2. The Telr controller receives the normal payment context used by existing gateways: order, wallet add-fund transaction, or subscription transaction.
3. It requests a hosted-payment session using the current amount, currency, unique cart ID, description, customer data where available, and three application return URLs.
4. It redirects the customer to Telr's hosted URL.
5. Each return endpoint verifies the session by calling Telr with `method: check` and the Telr order reference. Only Telr status `3` (Paid) creates or finalizes the local payment.
6. A transaction-advice webhook independently validates Telr's SHA-1 `tran_check` signature and reconciles an authorised (`A`) or held (`H`) payment. Duplicate requests are safe: existing local payment/transaction state is checked before it is credited or completed again.

## Error handling

Session-creation failures show the existing payment-failed experience without changing local payment state. Declined and cancelled returns show their corresponding pages. Invalid callback parameters, invalid signatures, and mismatched amount/currency/cart references are rejected and logged without exposing secrets.

## Routes and compatibility

Telr uses its own named routes under the existing public payment routes. The generic payment trait maps the `telr` gateway to the session route, preserving the current URLs and behavior for every other gateway. The gateway appears in all existing built-in gateway allowlists and configuration API responses.

## Verification

Automated tests will cover configuration validation, session payload construction, return verification, failure/cancel flows, webhook signature validation, and idempotent wallet/subscription/order settlement. A manual Telr sandbox checklist will confirm the configured Store ID and Authentication Key, redirect, return URL, and webhook reachability.
