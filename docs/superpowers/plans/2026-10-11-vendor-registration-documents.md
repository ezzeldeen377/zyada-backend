# Vendor registration documents Implementation Plan

> **For agentic workers:** Execute this plan task-by-task in the current session. Automated tests are omitted because the user did not request them.

**Goal:** Capture four optional vendor documents during registration, save and retrieve them using the existing store TIN pattern, and remove estimated delivery time from registration.

**Architecture:** Add nullable document path columns to `stores`, upload files using existing `Helpers` methods, and add full-URL accessors to `Store`. Update web self-registration, vendor API registration, and admin create/edit/detail screens; preserve TIN and use `30-40 min` as the new-store operational delivery-time default.

**Tech Stack:** Laravel 12, Eloquent, Blade, Laravel validation, existing `Helpers::upload` and storage URL conventions.

---

## Files to change

- Create a migration adding four nullable document path columns to `stores`.
- Create `app/CentralLogics/StoreRegistrationDocuments.php` to centralize document validation and upload persistence across registration paths.
- Modify `app/Models/Store.php` to fill the new columns, add admin-view URL accessors, and hide document paths from automatic JSON serialization.
- Modify `app/Http/Controllers/VendorController.php`, `app/Http/Controllers/Api/V1/Auth/VendorLoginController.php`, and `app/Http/Controllers/Admin/VendorController.php` to validate and persist optional uploads on create; admin update also accepts replacement uploads.
- Modify `resources/views/vendor-views/auth/general-info.blade.php` to remove ETA controls and add optional document uploads.
- Remove the obsolete ETA controls from `resources/views/vendor-views/auth/register.blade.php` so no vendor registration template still asks for the estimate.
- Modify `resources/views/admin-views/vendor/index.blade.php` to remove ETA controls and add optional document uploads.
- Modify `resources/views/admin-views/vendor/edit.blade.php` to show existing documents and allow optional replacement uploads.
- Modify `resources/views/admin-views/vendor/view/index.blade.php` to display TIN and document files with the existing preview/download pattern.
- Create `resources/views/admin-views/vendor/partials/_registration_documents_form.blade.php` for the shared optional upload inputs and current-document links.
- Modify inline validation in `resources/views/vendor-views/auth/general-info.blade.php` to remove ETA checks and enforce the optional document size limit.

## Execution tasks

### Task 1: Add store document persistence

- [x] Create a forward-only migration with nullable string columns `trade_license`, `emirates_id`, `passport_copy`, and `iban_letter` on `stores`; rollback drops only these four columns.
- [x] Add the four fields to `Store::$fillable` and `$hidden`; do not append private document URLs to store JSON.
- [x] Add four full URL accessors that use `Helpers::get_full_url('store', $value, $storageType)` and the matching storage key, following `getTinCertificateImageFullUrlAttribute`.
- [x] Inspect the migration/model diff for naming consistency with the request field names.

### Task 2: Update web self-registration

- [x] Remove estimated delivery time inputs from both vendor registration Blade templates and remove required client-side checks from `general-info.blade.php`.
- [x] Add optional Trade License, Emirates ID, Passport Copy, and IBAN Letter inputs with `accept` values matching the spec; show 2 MB guidance and do not mark them required.
- [x] In `VendorController::store`, validate optional uploads as PDF/images or images by document type, max 2048 KB; remove delivery-time rules and set new store `delivery_time` to `30-40 min`.
- [x] Save only supplied documents under `store/`, preserving TIN input and TIN certificate behavior.
- [x] Inspect Blade form encoding and client validation to ensure document files are submitted and omitted fields pass.

### Task 3: Update vendor app registration API

- [x] In `VendorLoginController::register`, add matching optional file validation and remove the three ETA request requirements.
- [x] Persist supplied documents to the new store fields; set `delivery_time` to `30-40 min`.
- [x] Keep the existing TIN request fields and store persistence intact.
- [x] Inspect response/error paths to ensure invalid optional uploads use the endpoint's existing validation response format.

### Task 4: Update admin create and edit

- [x] Remove estimated delivery time UI from `resources/views/admin-views/vendor/index.blade.php` and remove ETA-required client-side checks from its inline script.
- [x] Add the four optional document inputs to admin creation, with document-specific accepted formats and 2 MB guidance.
- [x] In `Admin\VendorController::store`, validate and save optional documents to the store; remove ETA validation and set `30-40 min`.
- [x] Add optional document replacement inputs to `resources/views/admin-views/vendor/edit.blade.php` and update `Admin\VendorController::update` to preserve existing paths when no replacement is submitted.
- [x] Keep admin edit's operational delivery-time controls and validation unchanged.

### Task 5: Retrieve and preview documents in admin

- [x] Extend `resources/views/admin-views/vendor/view/index.blade.php` so the document card appears when any new document exists; keep the existing TIN card intact.
- [x] Render each supplied file with the existing PDF/image thumbnail and download interaction; omit empty documents.
- [x] Confirm store JSON omits document paths and URLs while the admin detail view resolves the new full URL accessors.

### Task 6: Review changed paths

- [x] Review the migration, model, three creation handlers, admin update path, and Blade forms against the spec.
- [x] Run PHP syntax checks on modified PHP files; do not run or add automated tests unless requested.
- [x] Review `git diff --check` and `git status --short`, preserving unrelated user changes.
