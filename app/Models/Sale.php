<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no',
        'shop_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'sale_date',
        'item_count',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'balance',
        'payment_mode',
        'payment_status',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'subtotal' => 'float',
        'discount' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'paid_amount' => 'float',
        'balance' => 'float',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
