<?php

namespace App\Support;

class GeoService
{
    /** Straight-line distance is typically ~74% of real road distance in urban areas. */
    public const ROAD_FACTOR = 1.35;

    /** Fallback city delivery speed (km/h) when no live speed data is available. */
    public const DEFAULT_SPEED_KMH = 25.0;

    /** Pings older than this are considered stale and produce no live ETA. */
    public const STALE_AFTER_SECONDS = 300;

    /** Sanity clamp for device-reported speeds (km/h). */
    public const MIN_SPEED_KMH = 5.0;
    public const MAX_SPEED_KMH = 90.0;

    /**
     * Estimate the remaining travel time from a rider's last known position
     * to a drop point.
     *
     * @return array{
     *     has_position: bool,
     *     straight_line_km: float|null,
     *     distance_km: float|null,
     *     speed_kmh: float|null,
     *     eta_minutes: int|null,
     *     stale: bool,
     *     last_seen: ?string
     * }
     */
    public static function estimateEta(
        ?float $riderLat,
        ?float $riderLng,
        ?float $dropLat,
        ?float $dropLng,
        ?float $speedKmh = null,
        ?\DateTimeInterface $lastSeen = null,
    ): array {
        $empty = [
            'has_position' => false,
            'straight_line_km' => null,
            'distance_km' => null,
            'speed_kmh' => null,
            'eta_minutes' => null,
            'stale' => false,
            'last_seen' => null,
        ];

        if ($riderLat === null || $riderLng === null || $dropLat === null || $dropLng === null) {
            return $empty;
        }

        $straight = self::distance($riderLat, $riderLng, $dropLat, $dropLng);
        $road = round($straight * self::ROAD_FACTOR, 2);

        $speed = is_numeric($speedKmh) && $speedKmh > 0
            ? min(max((float) $speedKmh, self::MIN_SPEED_KMH), self::MAX_SPEED_KMH)
            : self::DEFAULT_SPEED_KMH;

        $stale = $lastSeen !== null && $lastSeen < now()->subSeconds(self::STALE_AFTER_SECONDS);

        $eta = (int) ceil(($road / $speed) * 60);
        $eta = min(max($eta, 1), 600);

        return [
            'has_position' => true,
            'straight_line_km' => $straight,
            'distance_km' => $road,
            'speed_kmh' => round($speed, 1),
            'eta_minutes' => $stale ? null : $eta,
            'stale' => $stale,
            'last_seen' => $lastSeen?->toIso8601String(),
        ];
    }

    /**
     * Distance between two coordinates in kilometres (Haversine formula).
     */
    public static function distance(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return 0.0;
        }

        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }

    /**
     * Nearest-neighbour route optimisation.
     * Orders stops starting from the origin by closest proximity.
     *
     * @param  array<int, array{id:int, latitude:?float, longitude:?float}>  $stops
     * @return array<int, array{id:int, latitude:?float, longitude:?float, distance:float}>
     */
    public static function optimiseRoute(array $stops, ?float $originLat, ?float $originLng): array
    {
        $remaining = array_values($stops);
        $ordered = [];

        $curLat = $originLat;
        $curLng = $originLng;

        while ($remaining) {
            $bestIndex = 0;
            $bestDistance = PHP_FLOAT_MAX;

            foreach ($remaining as $index => $stop) {
                $d = self::distance($curLat, $curLng, $stop['latitude'] ?? null, $stop['longitude'] ?? null);
                if (($stop['latitude'] ?? null) === null || ($stop['longitude'] ?? null) === null) {
                    $d = PHP_FLOAT_MAX - 1;
                }
                if ($d < $bestDistance) {
                    $bestDistance = $d;
                    $bestIndex = $index;
                }
            }

            $chosen = $remaining[$bestIndex];
            $chosen['distance'] = $bestDistance === PHP_FLOAT_MAX - 1 ? 0.0 : $bestDistance;
            $ordered[] = $chosen;

            $curLat = $chosen['latitude'] ?? $curLat;
            $curLng = $chosen['longitude'] ?? $curLng;

            array_splice($remaining, $bestIndex, 1);
        }

        return $ordered;
    }
}
