<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $casts = [
        'user_id' => 'integer',
        'module_id' => 'integer',
        'item_id' => 'integer',
        'is_guest' => 'boolean',
        'price' => 'float',
        'quantity' => 'integer',
        'add_on_ids' => 'array',
        'add_on_qtys' => 'array',
        'variation' => 'array',
    ];

    protected $fillable = [
        'user_id',
        'module_id',
        'item_id',
        'is_guest',
        'add_on_ids',
        'add_on_qtys',
        'item_type',
        'price',
        'quantity',
        'variation',
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Define morph map to handle various Box type formats
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'Box' => 'App\Models\Box',
            'AppModelsBox' => 'App\Models\Box',
            'App\ModelsBox' => 'App\Models\Box',
            'Item' => 'App\Models\Item',
            'AppModelsItem' => 'App\Models\Item',
            'ItemCampaign' => 'App\Models\ItemCampaign',
            'AppModelsItemCampaign' => 'App\Models\ItemCampaign',
        ]);
    }

    public function item()
    {
        return $this->morphTo();
    }
}
