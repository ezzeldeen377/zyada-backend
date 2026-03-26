<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Translation;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Scopes\StoreScope;
use Illuminate\Support\Facades\Validator;

class BoxController extends Controller
{

    /**
     * Store a new box.
     */
    public function store(Request $request)
    {
        if (!$request->vendor->stores[0]->item_section) {
            return response()->json([
                'errors' => [
                    ['code' => 'unauthorized', 'message' => translate('messages.permission_denied')]
                ]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'translations' => 'required',
            'price' => 'required|numeric|min:0',
            'item_count' => 'required|integer|min:1',
            'available_count' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'pickup_time_from' => 'nullable|date_format:H:i',
            'pickup_time_to' => 'nullable|date_format:H:i',
        ], [
            'translations.required' => translate('messages.translations_required'),
            'price.required' => translate('messages.price_required'),
            'item_count.required' => translate('messages.item_count_required'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $data = json_decode($request->translations, true);
        if (count($data) < 1) {
            return response()->json([
                'errors' => [
                    ['code' => 'translations', 'message' => translate('messages.Name and description are required')]
                ]
            ], 403);
        }

        $vendor = $request['vendor'];

        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = Helpers::upload('box/', 'png', $request->file('image'));
        }

        // Extract name and description for the main model (usually from the first translation entry)
        $name = '';
        $description = '';
        foreach ($data as $tm) {
            if ($tm['locale'] == 'default' || $tm['locale'] == 'en') {
                if ($tm['key'] == 'name') {
                    $name = $tm['value'];
                }
                if ($tm['key'] == 'description') {
                    $description = $tm['value'];
                }
            }
        }

        // If no default/en found, pick the first ones available
        if (empty($name)) {
            foreach ($data as $tm) {
                if ($tm['key'] == 'name') {
                    $name = $tm['value'];
                    break;
                }
            }
        }
        if (empty($description)) {
            foreach ($data as $tm) {
                if ($tm['key'] == 'description') {
                    $description = $tm['value'];
                    break;
                }
            }
        }

        $box = new Box();
        $box->store_id = $vendor->stores[0]->id;
        $box->module_id = $vendor->stores[0]->module_id;
        $box->name = $name;
        $box->description = $description;
        $box->price = $request->price;
        $box->item_count = $request->item_count;
        $box->available_count = $request->available_count;
        $box->image = $imageName;
        $box->start_date = $request->start_date;
        $box->end_date = $request->end_date;
        $box->pickup_time_from = $request->pickup_time_from;
        $box->pickup_time_to = $request->pickup_time_to;
        $box->status = true;
        $box->save();

        foreach ($data as $key => $i) {
            $data[$key]['translationable_type'] = 'App\Models\Box';
            $data[$key]['translationable_id'] = $box->id;
        }
        Translation::insert($data);

        return response()->json([
            'message' => translate('messages.box_created_successfully'),
            'box' => $box,
        ], 200);
    }


    /**
     * Update an existing box.
     */
    public function update(Request $request)
    {
        if (!$request->vendor->stores[0]->item_section) {
            return response()->json([
                'errors' => [
                    ['code' => 'unauthorized', 'message' => translate('messages.permission_denied')]
                ]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'translations' => 'required',
            'price' => 'required|numeric|min:0',
            'item_count' => 'required|integer|min:1',
            'available_count' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'pickup_time_from' => 'nullable|date_format:H:i',
            'pickup_time_to' => 'nullable|date_format:H:i',
        ], [
            'translations.required' => translate('messages.translations_required'),
            'price.required' => translate('messages.price_required'),
            'item_count.required' => translate('messages.item_count_required'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $box = Box::withoutGlobalScope(StoreScope::class)
            ->withoutGlobalScope('translate')
            ->find($request->id);

        if (!$box) {
            return response()->json([
                'errors' => [
                    ['code' => 'box', 'message' => translate('messages.box_not_found')]
                ]
            ], 404);
        }

        $data = json_decode($request->translations, true);
        if (count($data) < 1) {
            return response()->json([
                'errors' => [
                    ['code' => 'translations', 'message' => translate('messages.Name and description are required')]
                ]
            ], 403);
        }

        if ($request->hasFile('image')) {
            $box->image = Helpers::update('box/', $box->image, 'png', $request->file('image'));
        }

        // Extract name and description for the main model (usually from the first translation entry)
        $name = '';
        $description = '';
        foreach ($data as $tm) {
            if ($tm['locale'] == 'default' || $tm['locale'] == 'en') {
                if ($tm['key'] == 'name') {
                    $name = $tm['value'];
                }
                if ($tm['key'] == 'description') {
                    $description = $tm['value'];
                }
            }
        }

        // If no default/en found, pick the first ones available
        if (empty($name)) {
            foreach ($data as $tm) {
                if ($tm['key'] == 'name') {
                    $name = $tm['value'];
                    break;
                }
            }
        }
        if (empty($description)) {
            foreach ($data as $tm) {
                if ($tm['key'] == 'description') {
                    $description = $tm['value'];
                    break;
                }
            }
        }

        $box->name = $name;
        $box->description = $description;
        $box->price = $request->price;
        $box->item_count = $request->item_count;
        $box->available_count = $request->available_count;
        $box->start_date = $request->start_date;
        $box->end_date = $request->end_date;
        $box->pickup_time_from = $request->pickup_time_from;
        $box->pickup_time_to = $request->pickup_time_to;
        $box->save();

        foreach ($data as $key => $item) {
            Translation::updateOrInsert(
                [
                    'translationable_type' => 'App\Models\Box',
                    'translationable_id' => $box->id,
                    'locale' => $item['locale'],
                    'key' => $item['key']
                ],
                ['value' => $item['value']]
            );
        }

        return response()->json([
            'message' => translate('messages.box_updated_successfully'),
            'box' => $box,
        ], 200);
    }


    /**
     * Delete a box.
     */
    public function delete(Request $request)
    {
        if (!$request->vendor->stores[0]->item_section) {
            return response()->json([
                'errors' => [
                    ['code' => 'unauthorized', 'message' => translate('messages.permission_denied')]
                ]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $box = Box::withoutGlobalScope(StoreScope::class)
            ->withoutGlobalScope('translate')
            ->find($request->id);

        if (!$box) {
            return response()->json([
                'errors' => [
                    ['code' => 'box', 'message' => translate('messages.box_not_found')]
                ]
            ], 404);
        }

        $box->translations()->delete();
        $box->storage()->delete();
        $box->delete();

        return response()->json([
            'message' => translate('messages.box_deleted_successfully')
        ], 200);
    }

    /**
     * Toggle box status.
     */
    public function status(Request $request)
    {
        if (!$request->vendor->stores[0]->item_section) {
            return response()->json([
                'errors' => [
                    ['code' => 'unauthorized', 'message' => translate('messages.permission_denied')]
                ]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $box = Box::withoutGlobalScope(StoreScope::class)->find($request->id);

        if (!$box) {
            return response()->json([
                'errors' => [
                    ['code' => 'box', 'message' => translate('messages.box_not_found')]
                ]
            ], 404);
        }

        $box->status = $request->status;
        $box->save();

        return response()->json([
            'message' => translate('messages.box_status_updated')
        ], 200);
    }

    /**
     * List all boxes for the vendor's store.
     */
    public function list(Request $request)
    {
        $vendor = $request['vendor'];
        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 1);

        $boxes = Box::withoutGlobalScope(StoreScope::class)
            ->with('storage', 'store', 'module')
            ->where('store_id', $vendor->stores[0]->id)
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);

        $data = [
            'total_size' => $boxes->total(),
            'limit' => $limit,
            'offset' => $offset,
            'boxes' => Helpers::box_data_formatting($boxes->items(), true, true, app()->getLocale()),
        ];

        return response()->json($data, 200);
    }

    /**
     * Get box details.
     */
    public function get_box($id, Request $request)
    {
        $box = Box::withoutGlobalScope(StoreScope::class)
            ->withoutGlobalScope('translate')
            ->with(['translations' => function($query) {
                return $query; // Get all translations for editing
            }, 'storage', 'store', 'module'])
            ->find($id);

        if (!$box) {
            return response()->json([
                'errors' => [
                    ['code' => 'box', 'message' => translate('messages.box_not_found')]
                ]
            ], 404);
        }

        return response()->json(Helpers::box_data_formatting($box, false, true, app()->getLocale()), 200);
    }

}
