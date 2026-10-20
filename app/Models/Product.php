<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'age_group_id',
        'brand_id',
        'style_id',
        'name',
        'sku',
        'barcode',
        'gender',
        'description',
        'image',
        'cost_price',
        'retail_price',
        'status',
    ];

    protected $casts = [
        'cost_price' => 'float',
        'retail_price' => 'float',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function getTotalStockAttribute(): int
    {
        return (int) ProductVariant::where('product_id', $this->id)->sum('stocks.quantity');
    }
}
