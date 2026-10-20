<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_WAREHOUSE_MANAGER = 'warehouse_manager';
    public const ROLE_SHOPKEEPER = 'shopkeeper';
    public const ROLE_RIDER = 'rider';
    public const ROLE_ACCOUNTANT = 'accountant';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_WAREHOUSE_MANAGER,
        self::ROLE_SHOPKEEPER,
        self::ROLE_RIDER,
        self::ROLE_ACCOUNTANT,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'designation',
        'warehouse_id',
        'shop_id',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function managedWarehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'manager_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'rider_id');
    }

    public function assignedDeliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'assigned_by');
    }

    public function riderLocations(): HasMany
    {
        return $this->hasMany(RiderLocation::class, 'rider_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'rider_id');
    }

    public function createdTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'created_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isWarehouseManager(): bool
    {
        return $this->role === self::ROLE_WAREHOUSE_MANAGER;
    }

    public function isShopkeeper(): bool
    {
        return $this->role === self::ROLE_SHOPKEEPER;
    }

    public function isAccountant(): bool
    {
        return $this->role === self::ROLE_ACCOUNTANT;
    }

    public function isRider(): bool
    {
        return $this->role === self::ROLE_RIDER;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function getRoleLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->role));
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name));

        return strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr(end($parts) ?: '', 0, 1));
    }
}
