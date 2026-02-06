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
     * List all boxes for the vendor's store.
     */
    public function list(Request $request)
    {
        $vendor = $request['vendor'];
        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 1);

        $boxes = Box::withoutGlobalScope(StoreScope::class)
            ->withoutGlobalScope('translate')
            ->with('translations', 'storage')
            ->where('store_id', $vendor->stores[0]->id)
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);

        $data = [
            'total_size' => $boxes->total(),
            'limit' => $limit,
            'offset' => $offset,
            'boxes' => $boxes->items(),
        ];

        return response()->json($data, 200);
    }

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
            'name.0' => 'required',
            'name.*' => 'max:191',
            'description.*' => 'max:1000',
            'price' => 'required|numeric|min:0',
            'item_count' => 'required|integer|min:1',
            'available_count' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ], [
            'name.0.required' => translate('messages.item_name_required'),
            'description.*.max' => translate('messages.description_length_warning'),
            'price.required' => translate('messages.price_required'),
            'item_count.required' => translate('messages.item_count_required'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $vendor = $request['vendor'];

        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = Helpers::upload('box/', 'png', $request->file('image'));
        }

        $box = new Box();
        $box->store_id = $vendor->stores[0]->id;
        $box->module_id = $vendor->stores[0]->module_id;
        $box->name = $request->name[array_search('default', $request->lang)];
        $box->description = $request->description[array_search('default', $request->lang)];
        $box->price = $request->price;
        $box->item_count = $request->item_count;
        $box->available_count = $request->available_count;
        $box->image = $imageName;
        $box->start_date = $request->start_date;
        $box->end_date = $request->end_date;
        $box->status = true;
        $box->save();

        Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'App\Models\Box', data_id: $box->id, data_value: $box->name);
        Helpers::add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: 'App\Models\Box', data_id: $box->id, data_value: $box->description);

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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'item_count' => 'required|integer|min:1',
            'available_count' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'translations' => 'array',
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

        if ($request->hasFile('image')) {
            $box->image = Helpers::update('box/', $box->image, 'png', $request->file('image'));
        }

        $box->name = $request->name;
        $box->description = $request->description;
        $box->price = $request->price;
        $box->item_count = $request->item_count;
        $box->available_count = $request->available_count;
        $box->start_date = $request->start_date;
        $box->end_date = $request->end_date;
        $box->save();

        // Handle translations
        $translations = $request->translations ?? [];
        foreach ($translations as $item) {
            Translation::updateOrInsert(
                [
                    'translationable_type' => 'App\Models\Box',
                    'translationable_id' => $box->id,
                    'locale' => $item['locale'],
                    'key' => $item['key'],
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
     * Get box details.
     */
    public function get_box($id, Request $request)
    {
        $box = Box::withoutGlobalScope(StoreScope::class)
            ->withoutGlobalScope('translate')
            ->with('translations', 'storage')
            ->find($id);

        if (!$box) {
            return response()->json([
                'errors' => [
                    ['code' => 'box', 'message' => translate('messages.box_not_found')]
                ]
            ], 404);
        }

        return response()->json($box, 200);
    }
}
