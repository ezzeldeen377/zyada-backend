<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Store;
use App\Models\Translation;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;
use Rap2hpoutre\FastExcel\FastExcel;

class BoxController extends Controller
{
    public function index(Request $request)
    {
        $boxes = Box::latest()->paginate(config('default_pagination'));
        return view('admin-views.box.index', compact('boxes'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name.0' => 'required',
            'store_id' => 'required',
            'price' => 'required|numeric|min:0',
            'available_count' => 'required|numeric|min:1',
            'item_count' => 'required|numeric|min:1',
            'image' => 'required',
        ], [
            'name.0.required' => translate('messages.Name is required!'),
            'store_id.required' => translate('messages.Please select a store!'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $store = Store::find($request->store_id);

        $box = new Box();
        
        // Extract default or first translation item
        $default_index = array_search('default', $request->lang);
        $name = $default_index !== false ? $request->name[$default_index] : ($request->name[0] ?? '');
        $description = $default_index !== false ? $request->description[$default_index] : ($request->description[0] ?? '');

        $box->name = $name;
        $box->description = $description;
        $box->price = $request->price;
        $box->available_count = $request->available_count;
        $box->item_count = $request->item_count;
        $box->store_id = $request->store_id;
        $box->module_id = $store->module_id;
        $box->start_date = $request->start_date;
        $box->end_date = $request->end_date;
        $box->pickup_time_from = $request->pickup_time_from;
        $box->pickup_time_to = $request->pickup_time_to;
        $box->image = Helpers::upload('box/', 'png', $request->file('image'));
        $box->status = 1;
        $box->save();

        Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'App\Models\Box', data_id: $box->id, data_value: $box->name);
        Helpers::add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: 'App\Models\Box', data_id: $box->id, data_value: $box->description);

        return response()->json(['success' => translate('messages.box_added_successfully')]);
    }

    public function edit($id)
    {
        $box = Box::withoutGlobalScope('translate')->findOrFail($id);
        return view('admin-views.box.edit', compact('box'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name.0' => 'required',
            'store_id' => 'required',
            'price' => 'required|numeric|min:0',
            'available_count' => 'required|numeric|min:0',
            'item_count' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $box = Box::findOrFail($id);
        $store = Store::find($request->store_id);

        // Extract default or first translation item
        $default_index = array_search('default', $request->lang);
        $name = $default_index !== false ? $request->name[$default_index] : ($request->name[0] ?? '');
        $description = $default_index !== false ? $request->description[$default_index] : ($request->description[0] ?? '');

        $box->name = $name;
        $box->description = $description;
        $box->price = $request->price;
        $box->available_count = $request->available_count;
        $box->item_count = $request->item_count;
        $box->store_id = $request->store_id;
        $box->module_id = $store->module_id;
        $box->start_date = $request->start_date;
        $box->end_date = $request->end_date;
        $box->pickup_time_from = $request->pickup_time_from;
        $box->pickup_time_to = $request->pickup_time_to;
        $box->image = $request->has('image') ? Helpers::update('box/', $box->image, 'png', $request->file('image')) : $box->image;
        $box->save();

        Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'App\Models\Box', data_id: $box->id, data_value: $box->name);
        Helpers::add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: 'App\Models\Box', data_id: $box->id, data_value: $box->description);

        return response()->json(['success' => translate('messages.box_updated_successfully')]);
    }

    public function status(Request $request)
    {
        $box = Box::findOrFail($request->id);
        $box->status = $request->status;
        $box->save();
        Toastr::success(translate('messages.status_updated'));
        return back();
    }

    public function delete(Request $request)
    {
        $box = Box::findOrFail($request->id);
        if ($box->image) {
            Helpers::check_and_delete('box/', $box->image);
        }
        $box->translations()->delete();
        $box->delete();
        Toastr::success(translate('messages.box_deleted_successfully'));
        return back();
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $boxes = Box::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('name', 'like', "%{$value}%");
            }
        })->limit(50)->get();

        return response()->json([
            'view' => view('admin-views.box.partials._table', compact('boxes'))->render(),
            'count' => $boxes->count()
        ]);
    }
}
