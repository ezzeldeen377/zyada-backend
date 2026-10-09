# Hide delivery fees from rendered order UI

## Goal

Temporarily suppress delivery-fee presentation throughout the order experience while retaining the underlying `delivery_charge` data, calculations, API responses, payment totals, and accounting behavior. The implementation must be easy to reverse when delivery fees are needed again.

## Chosen approach

Wrap the existing Blade markup that renders delivery fees in clearly labelled Blade comments. This preserves the original markup in place and avoids altering order totals or any server-side business logic.

Alternative approaches considered:

1. Set delivery charges to zero in the backend. Rejected because it changes payments, accounting, and persisted order data.
2. Introduce a global feature flag. Rejected for now because it adds settings and logic beyond the temporary display-only requirement.
3. Comment the presentational rows in their Blade templates. Chosen because it has no effect on data or computations and is trivially reversible.

## Presentation scope

Hide all visible delivery-fee/charge rows or summary cards in order-facing presentations:

- Admin standard and parcel order detail pages.
- Vendor order detail page.
- Admin order print invoice partial and standalone order invoice.
- Admin POS invoice/receipt views.
- Customer transactional email templates and their matching admin email-format previews (formats 3 and 9).

For parcel orders, suppress the delivery-charge amount when it is displayed as the parcel line price as well as in totals, because it is the order's delivery fee.

The change does not hide delivery-fee configuration, operational reports, financial/commission reports, delivery-person earnings, or internal admin settings. Those are not order-detail or customer-email presentations and remain needed for operations.

## Behavior and data flow

`orders.delivery_charge` and related `original_delivery_charge` values continue to be stored and used unchanged. Existing order-total calculations—including totals displayed after a fee line is hidden—remain unchanged. This is intentionally display-only: the total remains the recorded/payable order total, avoiding a mismatch with payment records.

No controller, model, migration, API resource, notification dispatch, or calculation code changes.

## Error handling

There are no new runtime branches or data transformations. Blade comments remove only static presentation blocks, so existing rendering behavior and error paths are retained.

## Verification

Add focused source-level regression tests that assert the target order and email templates contain the labelled delivery-fee suppression comments, while controller/calculation files remain unchanged. Run the focused PHPUnit test and syntax/lint checks appropriate to the modified Blade/PHP files.

Manually verify representative standard, parcel, admin POS, vendor, printable-invoice, and email-preview output: no delivery-fee label or amount is shown; the stored order total still matches its existing value.
