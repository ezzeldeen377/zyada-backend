# Vendor Mystery Box Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a permitted vendor fully manage mystery boxes for its own store in the browser vendor panel, while preventing cross-store access in both browser and mobile-vendor APIs.

**Architecture:** Add a vendor-specific `BoxController` and two vendor Blade pages that reuse the existing Box model, translation helpers, storage helpers, and admin form fields. Scope every Box lookup to `Helpers::get_store_id()`; keep the store and module server-owned. Apply the same store predicate to the existing vendor API controller’s ID-based endpoints.

**Tech Stack:** Laravel/PHP, Blade, Eloquent, PHPUnit, existing jQuery/Select2 vendor panel assets.

---

## File Structure

- Create: `app/Http/Controllers/Vendor/BoxController.php` — browser-panel CRUD and ownership-scoped lookups.
- Create: `resources/views/vendor-views/box/index.blade.php` — create form plus current-store box list.
- Create: `resources/views/vendor-views/box/edit.blade.php` — edit form for a current-store box.
- Create: `tests/Feature/VendorBoxManagementTest.php` — vendor ownership, mutation, and validation coverage.
- Modify: `routes/vendor.php` — `vendor.box` route group inside the existing authenticated vendor group.
- Modify: `resources/views/layouts/vendor/partials/_sidebar.blade.php` — mystery-box navigation item under Item Management.
- Modify: `app/Http/Controllers/Api/V1/Vendor/BoxController.php` — scope ID-based lookups to the authenticated vendor’s store.

### Task 1: Establish Vendor Box Authorization Tests

**Files:**
- Create: `tests/Feature/VendorBoxManagementTest.php`
- Modify: `routes/vendor.php`

- [ ] **Step 1: Write the failing route and ownership tests**

```php
<?php

namespace Tests\Feature;

use App\Models\Box;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorBoxManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_list_contains_only_current_store_boxes(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $owned = $this->createBox($store, ['name' => 'Owned vendor box']);
        $other = $this->createBox($this->createStore(), ['name' => 'Other vendor box']);

        $response = $this->actingAsVendor($vendor)->get(route('vendor.box.add-new'));

        $response->assertOk()->assertSee($owned->name)->assertDontSee($other->name);
    }

    public function test_vendor_store_action_ignores_a_submitted_store_id(): void
    {
        Storage::fake('public');
        [$vendor, $store, $category] = $this->vendorWithStoreAndCategory();
        $otherStore = $this->createStore();

        $response = $this->actingAsVendor($vendor)->post(route('vendor.box.store'), [
            'lang' => ['default'],
            'name' => ['Vendor box'],
            'description' => ['Surprise items'],
            'store_id' => $otherStore->id,
            'category_id' => $category->id,
            'price' => 25,
            'item_count' => 2,
            'available_count' => 5,
            'discount_amount' => 0,
            'image' => UploadedFile::fake()->image('box.png'),
        ]);

        $response->assertRedirect(route('vendor.box.add-new'));
        $this->assertDatabaseHas('boxes', [
            'name' => 'Vendor box',
            'store_id' => $store->id,
            'module_id' => $store->module_id,
        ]);
    }

    public function test_vendor_cannot_mutate_a_box_from_another_store(): void
    {
        [$vendor] = $this->vendorWithStore();
        $otherBox = $this->createBox($this->createStore(), ['status' => true]);

        $this->actingAsVendor($vendor)
            ->get(route('vendor.box.edit', $otherBox))
            ->assertNotFound();

        $this->actingAsVendor($vendor)
            ->get(route('vendor.box.status', [$otherBox, 0]))
            ->assertNotFound();

        $this->actingAsVendor($vendor)
            ->delete(route('vendor.box.delete', $otherBox))
            ->assertNotFound();

        $this->assertDatabaseHas('boxes', ['id' => $otherBox->id, 'status' => true]);
    }

    public function test_store_without_item_section_cannot_create_or_change_a_box(): void
    {
        [$vendor, $store, $category] = $this->vendorWithStoreAndCategory(['item_section' => 0]);
        $box = $this->createBox($store);

        $this->actingAsVendor($vendor)->post(route('vendor.box.store'), [
            'lang' => ['default'], 'name' => ['Blocked'], 'description' => ['Blocked'],
            'category_id' => $category->id, 'price' => 10, 'item_count' => 1,
            'available_count' => 1, 'discount_amount' => 0,
            'image' => UploadedFile::fake()->image('box.png'),
        ])->assertRedirect();

        $this->actingAsVendor($vendor)
            ->get(route('vendor.box.status', [$box, 0]))
            ->assertRedirect();

        $this->assertDatabaseMissing('boxes', ['name' => 'Blocked']);
        $this->assertDatabaseHas('boxes', ['id' => $box->id, 'status' => true]);
    }
}
```

Add private `vendorWithStore`, `vendorWithStoreAndCategory`, `createStore`, `createBox`, and `actingAsVendor` helpers immediately below the tests. The project does not currently have Box, Store, Vendor, Module, or Category factories, so create only the required records with `Model::unguard()` in these helpers rather than adding application factories solely for this feature. The vendor must be status-enabled and have its `login_remember_token` set in the test session. Create an active module-matching category and authenticate with `actingAs($vendor, 'vendor')`.

- [ ] **Step 2: Run the new tests to verify they fail because vendor box routes do not exist**

Run: `php artisan test tests/Feature/VendorBoxManagementTest.php`

Expected: FAIL with missing `vendor.box.*` route errors.

- [ ] **Step 3: Commit the failing test baseline**

```bash
git add tests/Feature/VendorBoxManagementTest.php
git commit -m "test: cover vendor mystery box ownership"
```

### Task 2: Add Ownership-Scoped Vendor CRUD and Routes

**Files:**
- Create: `app/Http/Controllers/Vendor/BoxController.php`
- Modify: `routes/vendor.php`
- Test: `tests/Feature/VendorBoxManagementTest.php`

- [ ] **Step 1: Add the route group inside the existing authenticated vendor group**

```php
Route::group(['prefix' => 'box', 'as' => 'box.', 'middleware' => ['module:item', 'subscription:item']], function () {
    Route::get('add-new', 'BoxController@index')->name('add-new');
    Route::post('store', 'BoxController@store')->name('store');
    Route::get('edit/{id}', 'BoxController@edit')->name('edit');
    Route::post('update/{id}', 'BoxController@update')->name('update');
    Route::get('status/{id}/{status}', 'BoxController@status')->name('status');
    Route::delete('delete/{id}', 'BoxController@delete')->name('delete');
});
```

- [ ] **Step 2: Implement the controller’s scoped query and write guard**

```php
private function boxesForCurrentStore()
{
    return Box::withoutGlobalScope(StoreScope::class)
        ->where('store_id', Helpers::get_store_id());
}

private function ensureItemSection(): ?RedirectResponse
{
    if (Helpers::get_store_data()->item_section) {
        return null;
    }

    Toastr::warning(translate('messages.permission_denied'));
    return back();
}
```

Import `Box`, `Category`, `Helpers`, `StoreScope`, `Request`, `Toastr`, and `Validator`. Use `boxesForCurrentStore()->findOrFail($id)` for every edit, update, status, and delete action; do not use `Box::find`, route model binding, or submitted store/module IDs.

- [ ] **Step 3: Implement list/create, preserving the admin data shape without a selectable store**

```php
public function index(Request $request)
{
    $key = explode(' ', $request->input('search', ''));
    $store = Helpers::get_store_data();

    $boxes = $this->boxesForCurrentStore()
        ->with('category:id,name')
        ->when($request->filled('search'), function ($query) use ($key) {
            $query->where(function ($query) use ($key) {
                foreach ($key as $value) {
                    $query->orWhere('name', 'like', "%{$value}%");
                }
            });
        })
        ->latest()
        ->paginate(config('default_pagination'))
        ->withQueryString();

    $categories = Category::active()->module($store->module_id)->get(['id', 'name']);

    return view('vendor-views.box.index', compact('boxes', 'categories'));
}
```

Validate `name.0`, `description.0`, `price`, `item_count`, `available_count`, `image`, and a `category_id` that exists in a category under `Helpers::get_store_data()->module_id`. Validate the optional date/time and discount fields as specified in the design. Create the Box with its `store_id` and `module_id` set from `Helpers::get_store_data()`, then use `Helpers::add_or_update_translations` for `name` and `description`.

- [ ] **Step 4: Implement edit/update/status/delete with store-scoped data and existing helpers**

```php
public function status($id, $status)
{
    if ($redirect = $this->ensureItemSection()) {
        return $redirect;
    }

    $box = $this->boxesForCurrentStore()->findOrFail($id);
    $box->status = (bool) $status;
    $box->save();

    Toastr::success(translate('messages.status_updated'));
    return back();
}

public function delete($id)
{
    if ($redirect = $this->ensureItemSection()) {
        return $redirect;
    }

    $box = $this->boxesForCurrentStore()->findOrFail($id);
    if ($box->image) {
        Helpers::check_and_delete('box/', $box->image);
    }
    $box->translations()->delete();
    $box->delete();

    Toastr::success(translate('messages.box_deleted_successfully'));
    return back();
}
```

`edit` loads the same current-store categories and returns `vendor-views.box.edit`. `update` starts from the same write guard and scoped lookup, repeats create validation except image is nullable and available count permits zero, updates every editable field, updates the image with `Helpers::update`, writes translations, and redirects to `vendor.box.add-new` with the existing translated success toast.

- [ ] **Step 5: Run the ownership tests and fix only controller/route failures**

Run: `php artisan test tests/Feature/VendorBoxManagementTest.php`

Expected: the tests exercise real `vendor.box.*` routes; remaining failures should only be missing views or fixture assumptions.

- [ ] **Step 6: Commit controller and route work**

```bash
git add app/Http/Controllers/Vendor/BoxController.php routes/vendor.php
git commit -m "feat: add vendor mystery box management"
```

### Task 3: Build Vendor-Panel Create/List and Edit Pages

**Files:**
- Create: `resources/views/vendor-views/box/index.blade.php`
- Create: `resources/views/vendor-views/box/edit.blade.php`
- Test: `tests/Feature/VendorBoxManagementTest.php`

- [ ] **Step 1: Create the list/create page from the established admin form pattern**

Use `layouts.vendor.app`, `route('vendor.box.store')`, and the configured language collection from `getWebConfig('language')`. Reuse the exact field names `lang[]`, `name[]`, `description[]`, `available_count`, `item_count`, `price`, `category_id`, `discount_type`, `discount_amount`, `image`, `start_date`, `end_date`, `pickup_time_from`, and `pickup_time_to`.

Do not render the admin form’s `store_id` select or its store column. Render a table with image, name, discounted price, available count, item count, status toggle targeting `vendor.box.status`, edit targeting `vendor.box.edit`, delete targeting `vendor.box.delete`, pagination, and the existing `form-alert` deletion convention. Keep the current vendor layout’s Select2, language tab, image-preview, toastr, and redirect-url conventions; do not add a second JS framework.

- [ ] **Step 2: Create the edit page using the same field names and translated values**

Use `layouts.vendor.app` and `route('vendor.box.update', $box->id)`. Build the default-language fields from `$box->getRawOriginal('name')` and `$box->getRawOriginal('description')`; map all other translations by locale as the admin edit page does. Preselect the current category, discount fields, dates, times, and show `$box->image_full_url`. Keep the image optional and omit store/module fields entirely.

- [ ] **Step 3: Run static and route checks**

Run:

```bash
php -l app/Http/Controllers/Vendor/BoxController.php
php artisan route:list --name=vendor.box
```

Expected: PHP reports no syntax errors and the output lists exactly six vendor box routes, all under `vendor-panel/box`.

- [ ] **Step 4: Run the feature tests and commit the views**

Run: `php artisan test tests/Feature/VendorBoxManagementTest.php`

Expected: PASS.

```bash
git add resources/views/vendor-views/box/index.blade.php resources/views/vendor-views/box/edit.blade.php tests/Feature/VendorBoxManagementTest.php
git commit -m "feat: add vendor mystery box views"
```

### Task 4: Expose the Feature in Vendor Navigation

**Files:**
- Modify: `resources/views/layouts/vendor/partials/_sidebar.blade.php`
- Test: `tests/Feature/VendorBoxManagementTest.php`

- [ ] **Step 1: Add a mystery-box item under Item Management**

Place it after the Item menu and before AddOns, using the existing item permission and store capability checks:

```blade
@if (\App\CentralLogics\Helpers::employee_module_permission_check('item') && $store_data->item_section)
    <li class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/box*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.box.add-new') }}"
            title="{{ translate('messages.mystery_box') }}">
            <i class="tio-gift nav-icon"></i>
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                {{ translate('messages.mystery_box') }}
            </span>
        </a>
    </li>
@endif
```

- [ ] **Step 2: Extend the test to assert the link is rendered for an enabled item-section store and omitted for a disabled store**

```php
$this->actingAsVendor($vendor)
    ->get(route('vendor.dashboard'))
    ->assertSee(route('vendor.box.add-new'));
```

For the disabled-store fixture, assert `assertDontSee(route('vendor.box.add-new'))`.

- [ ] **Step 3: Run the feature test and commit**

Run: `php artisan test tests/Feature/VendorBoxManagementTest.php`

Expected: PASS.

```bash
git add resources/views/layouts/vendor/partials/_sidebar.blade.php tests/Feature/VendorBoxManagementTest.php
git commit -m "feat: add vendor mystery box navigation"
```

### Task 5: Close Vendor Mobile API Cross-Store Access

**Files:**
- Modify: `app/Http/Controllers/Api/V1/Vendor/BoxController.php`
- Test: `tests/Feature/VendorBoxManagementTest.php`

- [ ] **Step 1: Write failing API ownership assertions**

Add a test that authenticates a vendor token for Store A, calls vendor API `details/{box}` for a Box in Store B, and expects 404. Repeat for `update`, `delete`, and `status`; each must leave Store B’s record unchanged.

- [ ] **Step 2: Replace every global ID lookup with a vendor-store-scoped lookup**

At the beginning of each of `update`, `delete`, `status`, and `get_box`, obtain the current store ID from the authenticated request vendor, then replace the global lookup with:

```php
$box = Box::withoutGlobalScope(StoreScope::class)
    ->withoutGlobalScope('translate')
    ->where('store_id', $request->vendor->stores[0]->id)
    ->find($request->id);
```

For `get_box($id, Request $request)`, use `$id` in `find($id)`. Preserve the controller’s existing 404 response payload; do not change successful API data formatting or validation status codes.

- [ ] **Step 3: Run API ownership tests and syntax check**

Run:

```bash
php artisan test tests/Feature/VendorBoxManagementTest.php
php -l app/Http/Controllers/Api/V1/Vendor/BoxController.php
```

Expected: PASS and no PHP syntax errors.

- [ ] **Step 4: Commit the API authorization fix**

```bash
git add app/Http/Controllers/Api/V1/Vendor/BoxController.php tests/Feature/VendorBoxManagementTest.php
git commit -m "fix: scope vendor box API to current store"
```

### Task 6: Final Regression Verification

**Files:**
- Verify: `app/Http/Controllers/Vendor/BoxController.php`
- Verify: `app/Http/Controllers/Api/V1/Vendor/BoxController.php`
- Verify: `routes/vendor.php`
- Verify: `resources/views/vendor-views/box/index.blade.php`
- Verify: `resources/views/vendor-views/box/edit.blade.php`
- Verify: `resources/views/layouts/vendor/partials/_sidebar.blade.php`

- [ ] **Step 1: Run the focused suite and PHP lint**

Run:

```bash
php artisan test tests/Feature/VendorBoxManagementTest.php
php -l app/Http/Controllers/Vendor/BoxController.php
php -l app/Http/Controllers/Api/V1/Vendor/BoxController.php
php artisan route:list --name=vendor.box
```

Expected: every test passes, both controllers have no syntax errors, and all six browser routes resolve.

- [ ] **Step 2: Perform a manual authorization smoke test**

With two vendor stores, sign in as Store A and verify: the list excludes Store B boxes; Store B’s edit URL returns 404; Store B’s status/delete URLs return 404; the vendor API responds 404 for Store B box details and mutations; and the mystery-box sidebar entry disappears when Store A’s `item_section` is disabled.

- [ ] **Step 3: Inspect the final diff and commit any verification-only corrections**

Run:

```bash
git diff --check HEAD~5..HEAD
git status --short
```

Expected: no whitespace errors and no unintended files. If verification requires a correction, add only the corrected feature files and commit with `fix: complete vendor mystery box verification`.
