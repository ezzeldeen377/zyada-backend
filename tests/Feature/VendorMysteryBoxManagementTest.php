<?php

namespace Tests\Feature;

use App\Models\Box;
use App\Models\Category;
use App\Models\Module;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorMysteryBoxManagementTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        view()->getFinder()->prependLocation(base_path('tests/Fixtures/views'));

        $this->createFixtureSchema();
    }

    public function test_an_authenticated_vendor_sees_only_boxes_from_their_current_store(): void
    {
        [$vendor, $store, $module] = $this->vendorWithStore();
        $otherStore = $this->storeFor($vendor, $module);
        $currentBox = $this->boxFor($store, $module, ['name' => 'Current store box']);
        $otherBox = $this->boxFor($otherStore, $module, ['name' => 'Other store box']);

        $response = $this->asVendor($vendor)->get(route('vendor.box.add-new'));

        $response->assertOk()->assertViewHas('boxes', function ($boxes) use ($currentBox, $otherBox) {
            $boxIds = collect($boxes instanceof \Illuminate\Contracts\Pagination\Paginator ? $boxes->items() : $boxes)
                ->pluck('id');

            return $boxIds->contains($currentBox->id) && ! $boxIds->contains($otherBox->id);
        });
    }

    public function test_store_ignores_a_submitted_store_id_and_uses_the_current_store_module_category_and_default_language_fields(): void
    {
        [$vendor, $store, $module] = $this->vendorWithStore();
        $otherStore = $this->storeFor($vendor, $module);
        $category = $this->categoryFor($module);
        Storage::fake('public');

        $response = $this->asVendor($vendor)->post(route('vendor.box.store'), [
            'store_id' => $otherStore->id,
            'category_id' => $category->id,
            'name' => ['A default-language box', 'صندوق عربي'],
            'description' => ['A default-language description', 'وصف عربي'],
            'lang' => ['default', 'ar'],
            'price' => 20,
            'item_count' => 2,
            'available_count' => 4,
            'image' => UploadedFile::fake()->image('mystery-box.png'),
        ]);

        $response->assertRedirect(route('vendor.box.add-new'));
        $this->assertDatabaseHas('boxes', [
            'store_id' => $store->id,
            'module_id' => $module->id,
            'category_id' => $category->id,
            'name' => 'A default-language box',
            'description' => 'A default-language description',
        ]);
        $this->assertDatabaseMissing('boxes', ['store_id' => $otherStore->id, 'name' => 'A default-language box']);
        $this->assertDatabaseHas('translations', [
            'translationable_type' => Box::class,
            'locale' => 'ar',
            'key' => 'name',
            'value' => 'صندوق عربي',
        ]);
        $this->assertDatabaseHas('translations', [
            'translationable_type' => Box::class,
            'locale' => 'ar',
            'key' => 'description',
            'value' => 'وصف عربي',
        ]);
        $this->assertNotNull(Box::where('name', 'A default-language box')->value('image'));
    }

    public function test_vendor_can_update_a_current_store_box_and_persist_editable_fields(): void
    {
        [$vendor, $store, $module] = $this->vendorWithStore();
        $originalCategory = $this->categoryFor($module);
        $newCategory = $this->categoryFor($module);
        $box = $this->boxFor($store, $module, ['category_id' => $originalCategory->id]);
        Storage::fake('public');

        $response = $this->asVendor($vendor)->post(route('vendor.box.update', $box), [
            'category_id' => $newCategory->id,
            'name' => ['Updated box', 'صندوق محدث'],
            'description' => ['Updated description', 'وصف محدث'],
            'lang' => ['default', 'ar'],
            'price' => 33.5,
            'item_count' => 3,
            'available_count' => 0,
            'discount_type' => 'percent',
            'discount_amount' => 15,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'pickup_time_from' => '09:00',
            'pickup_time_to' => '11:00',
            'image' => UploadedFile::fake()->image('updated-mystery-box.png'),
        ]);

        $response->assertRedirect(route('vendor.box.add-new'));
        $this->assertDatabaseHas('boxes', [
            'id' => $box->id,
            'store_id' => $store->id,
            'module_id' => $module->id,
            'category_id' => $newCategory->id,
            'name' => 'Updated box',
            'description' => 'Updated description',
            'price' => 33.5,
            'item_count' => 3,
            'available_count' => 0,
            'discount_type' => 'percent',
            'discount_amount' => 15,
            'pickup_time_from' => '09:00',
            'pickup_time_to' => '11:00',
        ]);
        $this->assertSame('2026-10-05', $box->fresh()->start_date->toDateString());
        $this->assertSame('2026-10-06', $box->fresh()->end_date->toDateString());
        $this->assertDatabaseHas('translations', [
            'translationable_type' => Box::class,
            'translationable_id' => $box->id,
            'locale' => 'ar',
            'key' => 'name',
            'value' => 'صندوق محدث',
        ]);
    }

    public function test_vendor_cannot_edit_a_box_from_another_store(): void
    {
        [$vendor, $store, $module] = $this->vendorWithStore();
        $foreignBox = $this->boxFor($this->storeFor($vendor, $module), $module, ['name' => 'Protected box']);

        $response = $this->asVendor($vendor)->get(route('vendor.box.edit', $foreignBox));

        $response->assertNotFound();
        $this->assertDatabaseHas('boxes', ['id' => $foreignBox->id, 'name' => 'Protected box']);
    }

    public function test_vendor_cannot_toggle_status_for_a_box_from_another_store(): void
    {
        [$vendor, $store, $module] = $this->vendorWithStore();
        $foreignBox = $this->boxFor($this->storeFor($vendor, $module), $module, ['status' => true]);

        $response = $this->asVendor($vendor)->get(route('vendor.box.status', [$foreignBox, 0]));

        $response->assertNotFound();
        $this->assertDatabaseHas('boxes', ['id' => $foreignBox->id, 'status' => 1]);
    }

    public function test_vendor_cannot_update_a_box_from_another_store(): void
    {
        [$vendor, $store, $module] = $this->vendorWithStore();
        $foreignBox = $this->boxFor($this->storeFor($vendor, $module), $module, ['name' => 'Protected box']);
        $category = $this->categoryFor($module);

        $response = $this->asVendor($vendor)->post(route('vendor.box.update', $foreignBox), [
            'category_id' => $category->id,
            'name' => ['Changed'],
            'description' => ['Changed description'],
            'lang' => ['default'],
            'price' => 50,
            'item_count' => 1,
            'available_count' => 1,
        ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('boxes', ['id' => $foreignBox->id, 'name' => 'Protected box']);
    }

    public function test_vendor_cannot_delete_a_box_from_another_store(): void
    {
        [$vendor, $store, $module] = $this->vendorWithStore();
        $foreignBox = $this->boxFor($this->storeFor($vendor, $module), $module);

        $response = $this->asVendor($vendor)->delete(route('vendor.box.delete', $foreignBox));

        $response->assertNotFound();
        $this->assertDatabaseHas('boxes', ['id' => $foreignBox->id]);
    }

    public function test_item_section_disabled_redirects_and_prevents_box_creation(): void
    {
        [$vendor, $store, $module] = $this->vendorWithStore(['item_section' => false]);
        $category = $this->categoryFor($module);
        Storage::fake('public');

        $response = $this->asVendor($vendor)->post(route('vendor.box.store'), [
            'category_id' => $category->id,
            'name' => ['Blocked box'],
            'description' => ['Must not be created'],
            'lang' => ['default'],
            'price' => 20,
            'item_count' => 2,
            'available_count' => 4,
            'image' => UploadedFile::fake()->image('blocked-box.png'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('boxes', ['name' => 'Blocked box']);
    }

    public function test_item_section_disabled_redirects_and_prevents_status_mutation(): void
    {
        [$vendor, $store, $module] = $this->vendorWithStore(['item_section' => false]);
        $box = $this->boxFor($store, $module, ['status' => true]);

        $response = $this->asVendor($vendor)->get(route('vendor.box.status', [$box, 0]));

        $response->assertRedirect();
        $this->assertDatabaseHas('boxes', ['id' => $box->id, 'status' => 1]);
    }

    public function test_item_section_disabled_redirects_and_prevents_box_update(): void
    {
        [$vendor, $store, $module] = $this->vendorWithStore(['item_section' => false]);
        $category = $this->categoryFor($module);
        $box = $this->boxFor($store, $module, ['name' => 'Unchanged box']);

        $response = $this->asVendor($vendor)->post(route('vendor.box.update', $box), [
            'category_id' => $category->id,
            'name' => ['Changed box'],
            'description' => ['Changed description'],
            'lang' => ['default'],
            'price' => 50,
            'item_count' => 1,
            'available_count' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('boxes', ['id' => $box->id, 'name' => 'Unchanged box']);
    }

    /** @return array{Vendor, Store, Module} */
    private function vendorWithStore(array $storeAttributes = []): array
    {
        $module = $this->module();
        $vendor = $this->vendor();

        return [$vendor, $this->storeFor($vendor, $module, $storeAttributes), $module];
    }

    private function asVendor(Vendor $vendor): static
    {
        config()->set('module.current_module_id', $vendor->stores()->firstOrFail()->module_id);

        return $this->actingAs($vendor, 'vendor')->withSession([
            'login_remember_token' => $vendor->login_remember_token,
        ]);
    }

    private function vendor(): Vendor
    {
        return Vendor::unguarded(fn () => Vendor::create([
            'f_name' => $this->faker->firstName(),
            'l_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->numerify('01#########'),
            'password' => 'not-a-real-password',
            'status' => 1,
            'login_remember_token' => $this->faker->uuid(),
        ]));
    }

    private function module(): Module
    {
        return Module::unguarded(fn () => Module::create([
            'module_name' => $this->faker->unique()->words(2, true),
            'module_type' => 'grocery',
            'status' => 1,
        ]));
    }

    private function storeFor(Vendor $vendor, Module $module, array $attributes = []): Store
    {
        return Store::unguarded(fn () => Store::create(array_merge([
            'name' => $this->faker->company(),
            'phone' => $this->faker->unique()->numerify('01#########'),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'vendor_id' => $vendor->id,
            'module_id' => $module->id,
            'status' => 1,
            'item_section' => true,
        ], $attributes)));
    }

    private function categoryFor(Module $module): Category
    {
        return Category::unguarded(fn () => Category::create([
            'name' => $this->faker->unique()->word(),
            'image' => 'def.png',
            'parent_id' => 0,
            'position' => 0,
            'status' => 1,
            'module_id' => $module->id,
        ]));
    }

    private function boxFor(Store $store, Module $module, array $attributes = []): Box
    {
        return Box::unguarded(fn () => Box::create(array_merge([
            'store_id' => $store->id,
            'module_id' => $module->id,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price' => 20,
            'item_count' => 2,
            'available_count' => 4,
            'status' => true,
        ], $attributes)));
    }

    private function createFixtureSchema(): void
    {
        $schema = Schema::connection('sqlite');

        // ModuleObserver clears database-backed module cache entries on create.
        $schema->create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->integer('expiration');
        });
        $schema->create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('f_name');
            $table->string('l_name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->boolean('status')->default(true);
            $table->string('login_remember_token')->nullable();
            $table->timestamps();
        });
        $schema->create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('module_name');
            $table->string('module_type');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        $schema->create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->text('address');
            $table->foreignId('vendor_id');
            $table->foreignId('module_id');
            $table->boolean('status')->default(true);
            $table->boolean('item_section')->default(true);
            $table->string('slug')->nullable();
            $table->timestamps();
        });
        $schema->create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->default('def.png');
            $table->integer('parent_id')->default(0);
            $table->integer('position')->default(0);
            $table->boolean('status')->default(true);
            $table->foreignId('module_id');
            $table->string('slug')->nullable();
            $table->timestamps();
        });
        $schema->create('boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id');
            $table->foreignId('module_id');
            $table->foreignId('category_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('item_count')->default(1);
            $table->integer('available_count')->default(0);
            $table->boolean('status')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('pickup_time_from')->nullable();
            $table->string('pickup_time_to')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->timestamps();
        });
        $schema->create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('translationable_type');
            $table->unsignedBigInteger('translationable_id');
            $table->string('locale');
            $table->string('key');
            $table->text('value');
        });
        $schema->create('storages', function (Blueprint $table) {
            $table->id();
            $table->string('data_type');
            $table->string('data_id');
            $table->string('key')->nullable();
            $table->string('value');
            $table->timestamps();
        });
        $schema->create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
        });
        $schema->create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('country')->nullable();
            $table->string('currency_code');
            $table->string('currency_symbol');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->timestamps();
        });

        DB::table('business_settings')->insert([
            ['key' => 'language', 'value' => '[]', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'currency', 'value' => 'USD', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'currency_symbol_position', 'value' => 'left', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('currencies')->insert([
            'country' => 'United States',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'exchange_rate' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
