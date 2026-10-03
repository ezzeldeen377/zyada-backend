<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Category;
use App\Scopes\StoreScope;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BoxController extends Controller
{
    public function index(Request $request)
    {
        $store = Helpers::get_store_data();
        $key = explode(' ', $request->input('search', ''));

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

        $categories = $this->categoriesForStore($store);

        return view('vendor-views.box.index', compact('boxes', 'categories'));
    }

    public function store(Request $request)
    {
        if ($redirect = $this->ensureItemSection()) {
            return $redirect;
        }

        $store = Helpers::get_store_data();
        $this->validateBox($request, $store->module_id, true);

        $box = new Box();
        $this->fillBox($box, $request);
        $box->store_id = $store->id;
        $box->module_id = $store->module_id;
        $box->image = Helpers::upload('box/', 'png', $request->file('image'));
        $box->status = true;
        $box->save();

        $this->syncTranslations($request, $box);

        Toastr::success(translate('messages.box_added_successfully'));
        return redirect()->route('vendor.box.add-new');
    }

    public function edit($id)
    {
        $box = $this->boxesForCurrentStore()
            ->withoutGlobalScope('translate')
            ->with('translations')
            ->findOrFail($id);
        $categories = $this->categoriesForStore(Helpers::get_store_data());

        return view('vendor-views.box.edit', compact('box', 'categories'));
    }

    public function update(Request $request, $id)
    {
        if ($redirect = $this->ensureItemSection()) {
            return $redirect;
        }

        $store = Helpers::get_store_data();
        $this->validateBox($request, $store->module_id);
        $box = $this->boxesForCurrentStore()->findOrFail($id);

        $this->fillBox($box, $request);
        if ($request->hasFile('image')) {
            $box->image = Helpers::update('box/', $box->image, 'png', $request->file('image'));
        }
        $box->save();

        $this->syncTranslations($request, $box);

        Toastr::success(translate('messages.box_updated_successfully'));
        return redirect()->route('vendor.box.add-new');
    }

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

    private function boxesForCurrentStore()
    {
        return Box::withoutGlobalScope(StoreScope::class)
            ->where('store_id', Helpers::get_store_id());
    }

    private function categoriesForStore($store)
    {
        return Category::active()
            ->module($store->module_id)
            ->get(['id', 'name']);
    }

    private function ensureItemSection()
    {
        if (Helpers::get_store_data()->item_section) {
            return null;
        }

        Toastr::warning(translate('messages.permission_denied'));
        return back();
    }

    private function validateBox(Request $request, int $moduleId, bool $imageRequired = false): void
    {
        $request->validate([
            'name' => 'required|array',
            'name.0' => 'required|max:191',
            'description' => 'required|array',
            'description.0' => 'required',
            'lang' => 'required|array',
            'price' => 'required|numeric|min:0',
            'item_count' => 'required|integer|min:1',
            'available_count' => 'required|integer|min:0',
            'image' => ($imageRequired ? 'required|' : 'nullable|') . 'image|max:2048',
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('module_id', $moduleId),
            ],
            'discount_type' => 'nullable|in:amount,percent',
            'discount_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'pickup_time_from' => 'nullable|date_format:H:i',
            'pickup_time_to' => 'nullable|date_format:H:i',
        ]);
    }

    private function fillBox(Box $box, Request $request): void
    {
        $defaultIndex = array_search('default', $request->input('lang', []), true);
        $defaultIndex = $defaultIndex === false ? 0 : $defaultIndex;

        $box->name = $request->name[$defaultIndex] ?? $request->name[0];
        $box->description = $request->description[$defaultIndex] ?? $request->description[0];
        $box->price = $request->price;
        $box->item_count = $request->item_count;
        $box->available_count = $request->available_count;
        $box->category_id = $request->category_id;
        $box->discount_type = $request->discount_type ?: null;
        $box->discount_amount = $request->discount_amount ?? 0;
        $box->start_date = $request->start_date;
        $box->end_date = $request->end_date;
        $box->pickup_time_from = $request->pickup_time_from;
        $box->pickup_time_to = $request->pickup_time_to;
    }

    private function syncTranslations(Request $request, Box $box): void
    {
        Helpers::add_or_update_translations($request, 'name', 'name', 'Box', $box->id, $box->name);
        Helpers::add_or_update_translations($request, 'description', 'description', 'Box', $box->id, $box->description);
    }
}
