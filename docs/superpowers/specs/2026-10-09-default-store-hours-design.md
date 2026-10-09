# Default new stores to 24/7 schedules

## Goal

Every newly created store must receive a schedule covering all seven days from `00:00:00` through `23:59:59` by default.

## Chosen approach

Call the existing `StoreLogic::insert_schedule($storeId)` helper immediately after every successful new-store persistence operation. The helper already creates the required seven daily records with the requested 24-hour values.

Creation paths in scope:

- Admin store configuration (`Admin\\VendorController::store`).
- Vendor web self-registration (`VendorController::store`).
- Vendor API self-registration (`Api\\V1\\Auth\\VendorLoginController::register`).
- New records inserted during both bulk import flows.

For bulk upserts, call the helper only after an actual insert; existing stores being updated retain their schedules.

## Alternatives considered

1. Add explicit helper calls at each known creation path. Chosen because it covers both Eloquent and raw bulk inserts while keeping the side effect visible.
2. Register a Store model `created` event. Rejected because raw database inserts in bulk imports bypass model events.
3. Use a database trigger. Rejected because it adds database-specific behavior and is unnecessary for this application-level default.

## Boundaries

This changes schedule records only. It does not alter `active`, `status`, module configuration, delivery settings, existing stores, store updates, or manually changed schedules. It also removes the module-type condition so every newly created store receives the same schedule default.

## Verification

Add focused tests for the schedule helper and creation-path wiring. Confirm each new store receives seven records with days `0` through `6`, opening `00:00:00`, and closing `23:59:59`; confirm updated/imported-existing stores do not receive a replacement schedule.
