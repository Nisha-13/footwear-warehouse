<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no',
        'supplier_id',
        'warehouse_id',
        'user_id',
        'purchase_date',
        'item_count',
        'subtotal',
        'tax',
        'discount',
        'shipping',
        'total',
        'paid_amount',
        'balance',
        'payment_status',
        'payment_method',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'float',
        'tax' => 'float',
        'discount' => 'float',
        'shipping' => 'float',
        'total' => 'float',
        'paid_amount' => 'float',
        'balance' => 'float',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function ledgerEntries()
    {
        return $this->morphMany(LedgerEntry::class, 'reference');
    }
}
