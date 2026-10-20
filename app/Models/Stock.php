<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'stockable_type',
        'stockable_id',
        'quantity',
        'reserved_quantity',
        'reorder_level',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getAvailableAttribute(): int
    {
        return (int) $this->quantity - (int) $this->reserved_quantity;
    }
}
