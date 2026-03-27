<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Box;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\CentralLogics\ProductLogic;
use App\CentralLogics\StoreLogic;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BoxController extends Controller
{
    /**
     * Get all available boxes.
     */
    public function index(Request $request, $store_id = null)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:1',
            'store_id' => 'nullable|integer',
            'all_boxes' => 'nullable|in:true,false',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 403);
        }

        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 1);
        $store_id = $store_id ?? $request->store_id;
        $all_boxes = $request->query('all_boxes') == 'true';

        $boxes = Box::active()
            ->available()
            ->module($request->header('moduleId'))
            ->when($store_id, function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->when(!$store_id && !$all_boxes, function ($query) {
                return $query->groupBy('store_id');
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
     * Get box details by ID — includes reviews and rating averages.
     */
    public function show($id)
    {
        $box = Box::active()
            ->available()
            ->withAvg('reviews', 'quality_rating')
            ->withAvg('reviews', 'value_rating')
            ->withAvg('reviews', 'packaging_rating')
            ->withAvg('reviews', 'service_rating')
            ->withAvg('reviews', 'usability_rating')
            ->withCount('reviews')
            ->with([
                'store:id,name,logo,address,latitude,longitude',
                'reviews' => function ($query) {
                    $query->active()->with('customer:id,f_name,l_name,image')->latest()->limit(5);
                }
            ])
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

    /**
     * Get paginated reviews for a box.
     */
    public function get_reviews(Request $request, $box_id)
    {
        $box = Box::find($box_id);
        if (!$box) {
            return response()->json([
                'errors' => [['code' => 'box', 'message' => translate('messages.box_not_found')]]
            ], 404);
        }

        if (isset($request['limit']) && $request['limit'] != null && isset($request['offset']) && $request['offset'] != null) {
            $reviews = Review::with(['customer'])
                ->where('box_id', $box_id)
                ->active()
                ->paginate($request['limit'], ['*'], 'page', $request['offset']);
            $total = $reviews->total();
        } else {
            $reviews = Review::with(['customer'])
                ->where('box_id', $box_id)
                ->active()
                ->get();
            $total = $reviews->count();
        }

        $storage = [];
        foreach ($reviews as $temp) {
            $temp['attachment'] = json_decode($temp['attachment']);
            $temp['box_name'] = $box->name;
            array_push($storage, $temp);
        }

        return response()->json([
            'total_size' => $total,
            'limit' => $request['limit'] ?? null,
            'offset' => $request['offset'] ?? null,
            'reviews' => $storage,
        ], 200);
    }

    /**
     * Get overall rating for a box.
     */
    public function get_rating($box_id)
    {
        try {
            $box = Box::findOrFail($box_id);
            $overallRating = ProductLogic::get_overall_rating($box->reviews);
            return response()->json(floatval($overallRating[0]), 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 403);
        }
    }

    /**
     * Submit a review for a box.
     */
    public function submit_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'box_id'           => 'required|integer',
            'order_id'         => 'required|integer',
            'quality_rating'   => 'required|numeric|min:1|max:5',
            'value_rating'     => 'required|numeric|min:1|max:5',
            'packaging_rating' => 'required|numeric|min:1|max:5',
            'service_rating'   => 'required|numeric|min:1|max:5',
            'usability_rating' => 'required|numeric|min:1|max:5',
        ]);

        $order = Order::find($request->order_id);
        if (!isset($order)) {
            $validator->errors()->add('order_id', translate('messages.order_data_not_found'));
        }

        $box = Box::find($request->box_id);
        if (!isset($box)) {
            $validator->errors()->add('box_id', translate('messages.box_not_found'));
        }

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // Check for duplicate review (same box + user + order)
        $existing = Review::where([
            'box_id'   => $request->box_id,
            'user_id'  => $request->user()->id,
            'order_id' => $request->order_id,
        ])->first();

        if (isset($existing)) {
            return response()->json([
                'errors' => [
                    ['code' => 'review', 'message' => translate('messages.already_submitted')]
                ]
            ], 403);
        }

        // Handle attachment uploads
        $image_array = [];
        if (!empty($request->file('attachment'))) {
            foreach ($request->file('attachment') as $image) {
                if ($image != null) {
                    if (!Storage::disk('public')->exists('review')) {
                        Storage::disk('public')->makeDirectory('review');
                    }
                    array_push($image_array, Storage::disk('public')->put('review', $image));
                }
            }
        }

        // Mark order as reviewed
        $order?->OrderReference?->update(['is_reviewed' => 1]);

        // Compute average rating
        $avg_rating = ($request->quality_rating + $request->value_rating + $request->packaging_rating + $request->service_rating + $request->usability_rating) / 5;

        $review = new Review;
        $review->user_id          = $request->user()->id;
        $review->box_id           = $request->box_id;
        $review->item_id          = null; // Explicitly null — not an item review
        $review->order_id         = $request->order_id;
        $review->module_id        = $order->module_id;
        $review->store_id         = $box->store_id;
        $review->comment          = $request->comment ?? null;
        $review->rating           = round($avg_rating);
        $review->quality_rating   = $request->quality_rating;
        $review->value_rating     = $request->value_rating;
        $review->packaging_rating = $request->packaging_rating;
        $review->service_rating   = $request->service_rating;
        $review->usability_rating = $request->usability_rating;
        $review->attachment       = json_encode($image_array);
        $review->save();

        // Update store rating
        if ($box->store) {
            $store_rating = StoreLogic::update_store_rating($box->store->rating, (int) $review->rating);
            $box->store->rating = $store_rating;
            $box->store->save();
        }

        // Update box rating
        $box->rating = ProductLogic::update_rating($box->rating, (int) $review->rating);
        $box->avg_rating = ProductLogic::get_avg_rating(json_decode($box->rating, true));
        $box->save();
        $box->increment('rating_count');

        return response()->json(['message' => translate('messages.review_submited_successfully')], 200);
    }
}
