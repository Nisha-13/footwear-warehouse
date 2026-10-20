<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'shopkeeper_id',
        'name',
        'business_name',
        'code',
        'owner_name',
        'phone',
        'city',
        'address',
        'latitude',
        'longitude',
        'credit_limit',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'credit_limit' => 'float',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shopkeeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shopkeeper_id');
    }

    public function stocks(): MorphMany
    {
        return $this->morphMany(Stock::class, 'stockable');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_shop_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->business_name ?: $this->name;
    }

    public function getStockUnitsAttribute(): int
    {
        return (int) $this->stocks()->sum('quantity');
    }
}
