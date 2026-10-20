<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'manager_id',
        'name',
        'code',
        'type',
        'owner_name',
        'city',
        'address',
        'phone',
        'latitude',
        'longitude',
        'capacity',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'parent_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function stocks(): MorphMany
    {
        return $this->morphMany(Stock::class, 'stockable');
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_warehouse_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_warehouse_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function isMain(): bool
    {
        return $this->type === 'main';
    }

    public function isRegional(): bool
    {
        return $this->type === 'regional';
    }

    public function getStockUnitsAttribute(): int
    {
        return (int) $this->stocks()->sum('quantity');
    }
}
