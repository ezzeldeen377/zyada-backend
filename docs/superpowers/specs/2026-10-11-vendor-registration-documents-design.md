# Vendor registration documents and delivery time

## Goal

Collect optional vendor identity and banking documents at vendor registration, keep the existing Business TIN capture, and stop asking vendors to estimate delivery time during registration.

## Current project paths

- Web self-registration: `VendorController::store` and the vendor registration Blade views.
- Vendor app self-registration: `Api\V1\Auth\VendorLoginController::register`.
- Admin store creation: `Admin\VendorController::store` and the admin vendor creation view.
- Customer-app store creation is handled elsewhere and is out of scope.
- TIN and its certificate currently belong to `stores`; new documents will follow that same persistence and retrieval pattern.

## Approaches considered

1. **Nullable document columns on `stores` (chosen).** Add one nullable path per document and expose them beside the existing TIN details in the admin store view. This follows the explicitly requested current TIN save-and-retrieve behavior and the existing store upload conventions.
2. **Nullable document columns on `vendors`.** This aligns with owner identity, but would require a separate display path from the existing TIN/store details and would diverge from the requested persistence and retrieval pattern.
3. **A separate vendor documents table.** This accommodates an evolving set of document types and multiple versions, but adds relations and UI/API complexity that the current request does not need.

## Design

Add nullable string fields to `stores` for `trade_license`, `emirates_id`, `passport_copy`, and `iban_letter`. Store uploaded file paths using the project's existing upload/storage helpers and expose full URLs to admin views using model accessors patterned after `tin_certificate_image_full_url`. Keep document paths and URLs out of automatic store JSON serialization so consumer APIs do not expose identity or bank records. All four inputs are optional in web and API validation; accept PDFs or images for the trade license and IBAN letter, and images only for Emirates ID and Passport Copy. Limit each upload to 2 MB, consistent with current registration document uploads.

Expose the same optional multipart fields through the existing vendor app registration API. The customer app is not changed because its store-registration flow is outside this repository. Add the four optional upload inputs to the landing-page self-registration and admin store-creation forms. Keep Business TIN, expiry date, and certificate fields and their existing `stores` persistence unchanged.

Remove minimum/maximum delivery time and delivery-time type from registration UI and validation in all three in-repository create paths. Since order processing still reads `stores.delivery_time`, assign the existing import fallback `30-40 min` when a new store is registered without a vendor-provided estimate. Leave delivery-time editing outside registration unchanged.

## Data flow and validation

Each entry point validates and uploads only files that were supplied, then saves the resulting paths on the newly created store. Missing files remain `NULL`; they do not block registration. Upload validation is enforced server-side, with matching `accept` hints and optional client-side size checks in the web forms. Admin store detail retrieval displays whichever TIN/document records are present using the existing file preview pattern. The existing TIN input continues to save to the store record as before.

## Boundaries

- No customer-app endpoint or client changes.
- No requirement changes to Business TIN.
- No change to existing-store data, store delivery-time editing, or delivery-time display outside registration.
- No document review workflow or separate vendor profile viewer in this change.

## Verification

Review the migration and each create path for optional field persistence, accepted formats, and absence of delivery-time requirements. Inspect the landing and admin forms to confirm document inputs are optional and ETA controls are absent. Confirm new stores receive `30-40 min` and existing TIN capture remains intact. Do not add or run automated tests unless requested.
