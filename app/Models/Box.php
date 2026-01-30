<?php

namespace App\Models;

use App\Scopes\ZoneScope;
use App\Scopes\StoreScope;
use Illuminate\Support\Str;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Box extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'store_id' => 'integer',
        'module_id' => 'integer',
        'price' => 'float',
        'item_count' => 'integer',
        'available_count' => 'integer',
        'status' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['image_full_url'];

    protected $with = ['translations', 'storage'];

    /**
     * Get the full URL for the box image.
     */
    public function getImageFullUrlAttribute()
    {
        $value = $this->image;
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] == 'image') {
                    return Helpers::get_full_url('box', $value, $storage['value']);
                }
            }
        }

        return Helpers::get_full_url('box', $value, 'public');
    }

    /**
     * Get translated name attribute.
     */
    public function getNameAttribute($value)
    {
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'name') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    /**
     * Get translated description attribute.
     */
    public function getDescriptionAttribute($value)
    {
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'description') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    /**
     * Scope for active boxes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1)
            ->where('available_count', '>', 0)
            ->whereHas('store', function ($query) {
                $query->where('status', 1);
            });
    }

    /**
     * Scope for available boxes within date range.
     */
    public function scopeAvailable($query)
    {
        $now = now()->format('Y-m-d');
        return $query->where(function ($q) use ($now) {
            $q->whereNull('start_date')
                ->orWhereDate('start_date', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('end_date')
                ->orWhereDate('end_date', '>=', $now);
        });
    }

    /**
     * Scope for module filter.
     */
    public function scopeModule($query, $module_id)
    {
        return $query->where('module_id', $module_id);
    }

    /**
     * Relationship with store.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relationship with module.
     */
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Relationship with carts (polymorphic).
     */
    public function carts()
    {
        return $this->morphMany(Cart::class, 'item');
    }

    /**
     * Relationship with translations.
     */
    public function translations()
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    /**
     * Relationship with storage.
     */
    public function storage()
    {
        return $this->morphMany(Storage::class, 'data');
    }

    /**
     * Boot method for global scopes.
     */
    protected static function booted()
    {
        if (auth('vendor')->check() || auth('vendor_employee')->check()) {
            static::addGlobalScope(new StoreScope);
        }

        static::addGlobalScope(new ZoneScope);

        static::addGlobalScope('storage', function ($builder) {
            $builder->with('storage');
        });

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                return $query->where('locale', app()->getLocale());
            }]);
        });
    }

    /**
     * Boot method for model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            if ($model->isDirty('image')) {
                $value = Helpers::getDisk();

                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'image',
                ], [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
