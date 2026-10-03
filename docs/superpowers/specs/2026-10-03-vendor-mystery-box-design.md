# Vendor Mystery Box Management Design

## Goal

Allow an authenticated vendor to create, view, edit, enable or disable, and delete mystery boxes for only the vendor's current store through the browser vendor panel.

## Current State

`Box` already persists the required data: store and module ownership, multilingual name and description, price, item and available counts, image, date window, pickup-time window, category, discount, status, translations, and image storage metadata. The admin panel already exposes a complete form. The vendor mobile API has matching operations, but the browser vendor panel has no routes, controller, pages, or navigation entry.

## Scope

### In scope

- A vendor-panel list-and-create page.
- A vendor-panel edit page.
- Create, update, status-toggle, and delete actions.
- Search and pagination in the vendor's own box list.
- Multilingual name and description fields using the existing translation helpers.
- Image upload/update using the existing `box/` storage convention.
- Server-side store ownership and `item_section` authorization checks.
- An explicit ownership fix for the existing vendor mobile API mutations and details lookup, so a vendor cannot access another store's box by ID.

### Out of scope

- Database migrations or new Box fields.
- Changes to customer/mobile box discovery, ordering, pricing, or reviews.
- A new employee-permission key, module flag, or subscription feature. The panel will follow the existing `item` permission and `item_section` policy.

## Recommended Architecture

Create `App\\Http\\Controllers\\Vendor\\BoxController` and a `vendor.box` route group in `routes/vendor.php`. Its controller owns browser-panel behavior; it does not call the API controller. It will reuse `Box`, `Category`, `Helpers`, `Toastr`, translations, and the same form field names used by the admin panel.

Use a small query helper in the controller, conceptually `boxesForCurrentStore()`, that filters `store_id` to `Helpers::get_store_id()`. Every read and mutation starts with this query. This is the authority boundary: a submitted box ID never overrides the current-store filter.

The existing `Api\\V1\\Vendor\\BoxController` will receive the same ownership restriction for update, delete, status, and details, retaining its existing response shape and validation conventions.

## Routes and Navigation

Under the existing vendor web middleware group, add a `box` route group:

- `GET /vendor-panel/box/add-new` — list boxes and show the create form.
- `POST /vendor-panel/box/store` — create a box for the current store.
- `GET /vendor-panel/box/edit/{id}` — render edit form for a current-store box.
- `POST /vendor-panel/box/update/{id}` — update a current-store box.
- `GET /vendor-panel/box/status/{id}/{status}` — toggle a current-store box's status.
- `DELETE /vendor-panel/box/delete/{id}` — delete a current-store box and its image/translations.

Add a “Mystery box” navigation item to the vendor sidebar's Item Management section. It uses the existing `item` employee permission and is displayed only when the current store has `item_section` enabled. Its active state matches `vendor-panel/box*`.

## Data and Form Flow

The vendor create page mirrors the existing admin mystery-box form except it has no store selector:

- Name and description in default plus configured languages.
- Available count, item count, and price.
- Store-module active categories only.
- Discount type and amount.
- Image with the existing preview component.
- Optional start/end dates and pickup start/end times.
- A paginated, searchable table of only the current store's boxes with status, edit, and delete actions.

On create, the controller assigns `store_id` from the current authenticated store and `module_id` from that store; neither is accepted from the browser. On edit/update, the same fields remain immutable. The current store's module is also used to populate categories.

Validation matches the current admin capability while making constraints explicit: default name and description, positive `item_count`, non-negative `available_count`, numeric price and discount amount, valid optional dates with end date no earlier than start date, `H:i` pickup times, a category belonging to the current module, and an image on create (optional on update).

## Authorization and Failure Behavior

- If the store's `item_section` is disabled, all write actions return to the vendor with the existing translated permission warning.
- A box outside the current store is treated as unavailable to the vendor: edit/update/status/delete return the standard not-found behavior instead of exposing cross-store data.
- Delete removes the image through `Helpers::check_and_delete('box/', ...)`, deletes translations, then deletes the model. Storage metadata continues to be handled by existing model relationships.
- Validation failures follow existing vendor panel behavior and preserve submitted data through Laravel's normal redirect/validation response.

## Tests and Verification

Add focused feature tests around the controller/query boundary:

- An authenticated vendor sees only boxes for the current store.
- Create assigns the current store and module even if another store ID is submitted.
- A vendor cannot edit, update, change status, delete, or retrieve a box belonging to another store.
- Valid create and update persist all box fields and translations.
- `item_section = false` blocks mutations.
- The sidebar view exposes the link only under the selected existing conditions.

Run the relevant feature tests, PHP syntax checks for changed PHP files, and `php artisan route:list --name=vendor.box` to confirm the route names and middleware registration.

## Acceptance Criteria

- A permitted vendor can fully manage mystery boxes from `/vendor-panel` without choosing a store.
- No vendor-panel or vendor-API endpoint can read or mutate another store's mystery box by changing an ID.
- Admin and customer/mobile behavior remains unchanged.
- Existing translations, image URLs/storage, category filtering, and date/discount fields are preserved.
