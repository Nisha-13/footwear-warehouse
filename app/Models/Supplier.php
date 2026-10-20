<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'code',
        'contact_person',
        'email',
        'phone',
        'city',
        'country',
        'address',
        'tax_number',
        'bank_account',
        'opening_balance',
        'status',
    ];

    protected $casts = [
        'opening_balance' => 'float',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function ledgerEntries(): MorphMany
    {
        return $this->morphMany(LedgerEntry::class, 'party');
    }

    public function getOutstandingAttribute(): float
    {
        return (float) ($this->ledgerEntries()->sum('debit') - $this->ledgerEntries()->sum('credit'));
    }
}
