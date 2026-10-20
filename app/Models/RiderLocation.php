<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class RiderLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'rider_id',
        'delivery_id',
        'latitude',
        'longitude',
        'speed',
        'label',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'speed' => 'float',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Latest ping for each of the given riders, keyed by rider_id.
     * Resolves with two queries regardless of how many riders there are.
     */
    public static function latestPerRider(array $riderIds): Collection
    {
        $riderIds = array_values(array_unique(array_filter($riderIds)));

        if ($riderIds === []) {
            return new Collection();
        }

        $latestIds = static::query()
            ->whereIn('rider_id', $riderIds)
            ->groupBy('rider_id')
            ->selectRaw('MAX(id) as max_id')
            ->pluck('max_id');

        if ($latestIds->isEmpty()) {
            return new Collection();
        }

        return static::whereIn('id', $latestIds)->get()
            ->keyBy(fn (self $ping) => (int) $ping->rider_id);
    }

    /**
     * Average reported speed (km/h) over recent pings for each rider,
     * keyed by rider_id. Single aggregate query for the whole fleet.
     *
     * @return Collection<int, float>
     */
    public static function recentSpeedsByRider(array $riderIds, int $minutes = 15): Collection
    {
        $riderIds = array_values(array_unique(array_filter($riderIds)));

        if ($riderIds === []) {
            return new Collection();
        }

        return static::query()
            ->whereIn('rider_id', $riderIds)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->whereNotNull('speed')
            ->where('speed', '>', 0)
            ->groupBy('rider_id')
            ->selectRaw('rider_id, AVG(speed) as avg_speed')
            ->pluck('avg_speed', 'rider_id')
            ->mapWithKeys(fn ($speed, $riderId) => [(int) $riderId => round((float) $speed, 1)]);
    }

    /**
     * Average speed (km/h) reported by a rider's device over recent pings,
     * or null when there is not enough fresh data to be meaningful.
     */
    public static function recentSpeedKmhFor(int $riderId, int $minutes = 15): ?float
    {
        return self::recentSpeedsByRider([$riderId], $minutes)->get($riderId);
    }
}
