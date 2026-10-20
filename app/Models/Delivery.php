<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use HasFactory;

    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const ALL_STATUSES = [
        self::STATUS_ASSIGNED,
        self::STATUS_PICKED_UP,
        self::STATUS_DISPATCHED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_DELIVERED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    /** Absorbing states: once reached, the delivery is finalized and locked. */
    public const TERMINAL_STATUSES = [
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Forward-only lifecycle. A delivery may move to any later active stage
     * or to a negative outcome, but never backwards, and never out of a
     * terminal state. `failed` is a retryable attempt: the rider may retry
     * (`in_transit` / `delivered`) or give up (`cancelled`).
     */
    public const TRANSITIONS = [
        self::STATUS_ASSIGNED => [
            self::STATUS_PICKED_UP,
            self::STATUS_DISPATCHED,
            self::STATUS_IN_TRANSIT,
            self::STATUS_DELIVERED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ],
        self::STATUS_PICKED_UP => [
            self::STATUS_DISPATCHED,
            self::STATUS_IN_TRANSIT,
            self::STATUS_DELIVERED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ],
        self::STATUS_DISPATCHED => [
            self::STATUS_IN_TRANSIT,
            self::STATUS_DELIVERED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ],
        self::STATUS_IN_TRANSIT => [
            self::STATUS_DELIVERED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ],
        self::STATUS_FAILED => [
            self::STATUS_IN_TRANSIT,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ],
        self::STATUS_DELIVERED => [],
        self::STATUS_CANCELLED => [],
    ];

    /** Structured failure reason codes recorded in `failure_reason`. */
    public const FAILURE_REASONS = [
        'customer_unavailable' => 'Customer not available',
        'address_incorrect' => 'Wrong or incomplete address',
        'customer_refused' => 'Customer refused delivery',
        'damaged_goods' => 'Goods damaged in transit',
        'payment_issue' => 'Payment could not be collected',
        'vehicle_breakdown' => 'Vehicle breakdown or accident',
        'weather_traffic' => 'Weather or traffic conditions',
        'other' => 'Other',
    ];

    protected $fillable = [
        'delivery_no',
        'transfer_id',
        'rider_id',
        'assigned_by',
        'type',
        'status',
        'sequence',
        'drop_name',
        'drop_address',
        'drop_latitude',
        'drop_longitude',
        'transaction_amount',
        'payment_mode',
        'assigned_at',
        'picked_at',
        'dispatched_at',
        'delivered_at',
        'proof_note',
        'failure_reason',
    ];

    protected $casts = [
        'drop_latitude' => 'float',
        'drop_longitude' => 'float',
        'transaction_amount' => 'float',
        'assigned_at' => 'datetime',
        'picked_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DeliveryStatusHistory::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(RiderLocation::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'assigned' => 'secondary',
            'picked_up' => 'info',
            'dispatched' => 'primary',
            'in_transit' => 'primary',
            'delivered' => 'success',
            'failed' => 'danger',
            'cancelled' => 'dark',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /** Valid next statuses the UI should offer for this delivery. */
    public function allowedNextStatuses(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    public function getFailureReasonLabelAttribute(): ?string
    {
        if (! $this->failure_reason) {
            return null;
        }

        return self::FAILURE_REASONS[$this->failure_reason] ?? $this->failure_reason;
    }
}
