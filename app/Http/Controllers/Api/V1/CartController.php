<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Box;
use App\Models\Cart;
use App\Models\Item;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\ItemCampaign;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function get_carts(Request $request)
    {
        $user = auth('api')->user();
        
        $validator = Validator::make($request->all(), [
            'guest_id' => $user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $user_id = $user ? $user->id : $request['guest_id'];
        $is_guest = $user ? 0 : 1;
        
        $carts = Cart::where('user_id', $user_id)
            ->where('is_guest', $is_guest)
            ->where('module_id', $request->header('moduleId'))
            ->with('item')
            ->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);

                // Check if item is a Box (handles multiple formats)
                $isBox = in_array($data->item_type, ['Box', 'App\Models\Box', 'AppModelsBox', 'App\ModelsBox']);

                if ($isBox) {
                    $data->item = Helpers::cart_box_data_formatting($data->item);
                } else {
                    $data->item = Helpers::cart_product_data_formatting(
                        $data->item,
                        $data->variation,
                        $data->add_on_ids,
                        $data->add_on_qtys,
                        false,
                        app()->getLocale()
                    );
                }
                return $data;
            });
        return response()->json($carts, 200);
    }

    public function add_to_cart(Request $request)
    {
        // Check if user is authenticated using auth() helper
        $user = auth('api')->user();
        
        $validator = Validator::make($request->all(), [
            'guest_id' => $user ? 'nullable' : 'required',
            'item_id' => 'required|integer',
            'model' => 'required|string|in:Item,ItemCampaign,Box',
            'price' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $user ? $user->id : $request['guest_id'];
        $is_guest = $user ? 0 : 1;
        
        // Determine model class based on request
        $modelMap = [
            'Item' => 'App\Models\Item',
            'ItemCampaign' => 'App\Models\ItemCampaign',
            'Box' => 'App\Models\Box',
        ];
        $model = $modelMap[$request->model];
        
        // Find the item based on model type
        $item = match ($request->model) {
            'Item' => Item::find($request->item_id),
            'ItemCampaign' => ItemCampaign::find($request->item_id),
            'Box' => Box::find($request->item_id),
        };

        if (!$item) {
            return response()->json([
                'errors' => [
                    ['code' => 'item', 'message' => translate('messages.item_not_found')]
                ]
            ], 404);
        }

        // Check if box is available
        if ($request->model === 'Box') {
            if ($item->available_count < $request->quantity) {
                return response()->json([
                    'errors' => [
                        ['code' => 'box_unavailable', 'message' => translate('messages.box_quantity_unavailable')]
                    ]
                ], 403);
            }
        }

        $cart = Cart::where('item_id',$request->item_id)->where('item_type',$model)->where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',$request->header('moduleId'))->first();

        if ($cart && json_decode($cart->variation, true) == $request->variation) {

            return response()->json([
                'errors' => [
                    ['code' => 'cart_item', 'message' => translate('messages.Item_already_exists')]
                ]
            ], 403);
        }

        // Check maximum cart quantity for items (not applicable to boxes)
        if ($request->model !== 'Box' && $item->maximum_cart_quantity && ($request->quantity > $item->maximum_cart_quantity)) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_item_limit', 'message' => translate('messages.maximum_cart_quantity_exceeded')]
                ]
            ], 403);
        }

        $cart = new Cart();
        $cart->user_id = $user_id;
        $cart->module_id = $request->header('moduleId');
        $cart->item_id = $request->item_id;
        $cart->is_guest = $is_guest;
        $cart->add_on_ids = isset($request->add_on_ids)?json_encode($request->add_on_ids):json_encode([]);
        $cart->add_on_qtys = isset($request->add_on_qtys)?json_encode($request->add_on_qtys):json_encode([]);
        $cart->item_type = $request->model;
        $cart->price = $request->price;
        $cart->quantity = $request->quantity;
        $cart->variation = isset($request->variation)?json_encode($request->variation):json_encode([]);
        $cart->save();

        $item->carts()->save($cart);

        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                
                $isBox = in_array($data->item_type, ['Box', 'App\Models\Box', 'AppModelsBox', 'App\ModelsBox']);
                
                if ($isBox) {
                    $data->item = Helpers::cart_box_data_formatting($data->item);
                } else {
                    $data->item = Helpers::cart_product_data_formatting(
                        $data->item,
                        $data->variation,
                        $data->add_on_ids,
                        $data->add_on_qtys,
                        false,
                        app()->getLocale()
                    );
                }
                return $data;
            });
        return response()->json($carts, 200);
    }


    public function update_cart(Request $request)
    {
        $user = auth('api')->user();
        
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required',
            'guest_id' => $user ? 'nullable' : 'required',
            'price' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $user ? $user->id : $request['guest_id'];
        $is_guest = $user ? 0 : 1;
        $cart = Cart::find($request->cart_id);
        
        $item = match ($cart->item_type) {
            'App\Models\Item', 'AppModelsItem', 'Item' => Item::find($cart->item_id),
            'App\Models\ItemCampaign', 'AppModelsItemCampaign', 'ItemCampaign' => ItemCampaign::find($cart->item_id),
            'App\Models\Box', 'AppModelsBox', 'App\ModelsBox', 'Box' => Box::find($cart->item_id),
            default => null,
        };

        if (!$item) {
            return response()->json([
                'errors' => [
                    ['code' => 'item', 'message' => translate('messages.item_not_found')]
                ]
            ], 404);
        }

        $isBox = in_array($cart->item_type, ['Box', 'App\Models\Box', 'AppModelsBox', 'App\ModelsBox']);
        
        if ($isBox) {
            if ($item->available_count < $request->quantity) {
                return response()->json([
                    'errors' => [
                        ['code' => 'box_unavailable', 'message' => translate('messages.box_quantity_unavailable')]
                    ]
                ], 403);
            }
        } else {
            if ($item->maximum_cart_quantity && ($request->quantity > $item->maximum_cart_quantity)) {
                return response()->json([
                    'errors' => [
                        ['code' => 'cart_item_limit', 'message' => translate('messages.maximum_cart_quantity_exceeded')]
                    ]
                ], 403);
            }
        }

        $cart->user_id = $user_id;
        $cart->module_id = $request->header('moduleId');
        $cart->is_guest = $is_guest;
        $cart->add_on_ids = isset($request->add_on_ids) ? json_encode($request->add_on_ids) : $cart->add_on_ids;
        $cart->add_on_qtys = isset($request->add_on_qtys) ? json_encode($request->add_on_qtys) : $cart->add_on_qtys;
        $cart->price = $request->price;
        $cart->quantity = $request->quantity;
        $cart->variation = isset($request->variation) ? json_encode($request->variation) : $cart->variation;
        $cart->save();

        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                
                $isBox = in_array($data->item_type, ['Box', 'App\Models\Box', 'AppModelsBox', 'App\ModelsBox']);
                
                if ($isBox) {
                    $data->item = Helpers::cart_box_data_formatting($data->item);
                } else {
                    $data->item = Helpers::cart_product_data_formatting(
                        $data->item,
                        $data->variation,
                        $data->add_on_ids,
                        $data->add_on_qtys,
                        false,
                        app()->getLocale()
                    );
                }
                return $data;
            });
        return response()->json($carts, 200);
    }

    public function remove_cart_item(Request $request)
    {
        $user = auth('api')->user();
        
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required',
            'guest_id' => $user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $user ? $user->id : $request['guest_id'];
        $is_guest = $user ? 0 : 1;

        $cart = Cart::find($request->cart_id);
        $cart?->delete();

        $carts = Cart::where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',$request->header('moduleId'))->get()
        ->map(function ($data) {
            $data->add_on_ids = json_decode($data->add_on_ids,true);
            $data->add_on_qtys = json_decode($data->add_on_qtys,true);
            $data->variation = json_decode($data->variation,true);
            
            $isBox = in_array($data->item_type, ['Box', 'App\Models\Box', 'AppModelsBox', 'App\ModelsBox']);
            
            if ($isBox) {
                $data->item = Helpers::cart_box_data_formatting($data->item);
            } else {
                $data->item = Helpers::cart_product_data_formatting($data->item, $data->variation,$data->add_on_ids,
                $data->add_on_qtys, false, app()->getLocale());
            }
            return $data;
        });
        return response()->json($carts, 200);
    }

    public function remove_cart(Request $request)
    {
        $user = auth('api')->user();
        
        $validator = Validator::make($request->all(), [
            'guest_id' => $user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $user ? $user->id : $request['guest_id'];
        $is_guest = $user ? 0 : 1;

        $carts = Cart::where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',$request->header('moduleId'))->get();

        foreach($carts as $cart){
            $cart?->delete();
        }


        $carts = Cart::where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',$request->header('moduleId'))->get()
        ->map(function ($data) {
            $data->add_on_ids = json_decode($data->add_on_ids,true);
            $data->add_on_qtys = json_decode($data->add_on_qtys,true);
            $data->variation = json_decode($data->variation,true);
			$data->item = Helpers::cart_product_data_formatting($data->item, $data->variation,$data->add_on_ids,
            $data->add_on_qtys, false, app()->getLocale());
            return $data;
		});
        return response()->json($carts, 200);
    }
}
