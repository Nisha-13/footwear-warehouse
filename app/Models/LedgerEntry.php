<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_no',
        'party_type',
        'party_id',
        'entry_type',
        'reference_type',
        'reference_id',
        'item_count',
        'debit',
        'credit',
        'balance',
        'payment_status',
        'payment_method',
        'entry_date',
        'due_date',
        'description',
        'user_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'due_date' => 'date',
        'debit' => 'float',
        'credit' => 'float',
        'balance' => 'float',
    ];

    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
