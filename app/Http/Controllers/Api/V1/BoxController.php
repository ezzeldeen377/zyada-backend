<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Box;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BoxController extends Controller
{
    /**
     * Get all available boxes.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:1',
            'store_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 403);
        }

        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 1);

        $boxes = Box::active()
            ->available()
            ->module($request->header('moduleId'))
            ->when($request->store_id, function ($query) use ($request) {
                return $query->where('store_id', $request->store_id);
            })
            ->with('store:id,name,logo,address')
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
     * Get box details by ID.
     */
    public function show($id)
    {
        $box = Box::active()
            ->available()
            ->with('store:id,name,logo,address,latitude,longitude')
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
