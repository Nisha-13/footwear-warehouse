<?php

namespace Tests\Unit;

use App\Support\GeoService;
use PHPUnit\Framework\TestCase;

class GeoServiceTest extends TestCase
{
    public function test_returns_empty_payload_when_coordinates_are_missing(): void
    {
        $eta = GeoService::estimateEta(null, null, 31.5, 74.3);

        $this->assertFalse($eta['has_position']);
        $this->assertNull($eta['eta_minutes']);
        $this->assertNull($eta['distance_km']);
    }

    public function test_pings_older_than_five_minutes_are_stale(): void
    {
        $fresh = GeoService::estimateEta(31.5, 74.3, 31.52, 74.32, null, now()->subSeconds(290));
        $this->assertFalse($fresh['stale']);
        $this->assertNotNull($fresh['eta_minutes']);

        $stale = GeoService::estimateEta(31.5, 74.3, 31.52, 74.32, null, now()->subSeconds(301));
        $this->assertTrue($stale['stale']);
        $this->assertNull($stale['eta_minutes']);
        $this->assertNotNull($stale['last_seen']);
    }

    public function test_speed_is_clamped_to_sane_bounds(): void
    {
        $fast = GeoService::estimateEta(31.5, 74.3, 32.5, 75.3, 500.0);
        $this->assertSame(GeoService::MAX_SPEED_KMH, $fast['speed_kmh']);

        $slow = GeoService::estimateEta(31.5, 74.3, 32.5, 75.3, 0.5);
        $this->assertSame(GeoService::MIN_SPEED_KMH, $slow['speed_kmh']);
    }

    public function test_default_speed_used_when_no_device_speed_available(): void
    {
        $eta = GeoService::estimateEta(31.5, 74.3, 32.5, 75.3, null);

        $this->assertSame(GeoService::DEFAULT_SPEED_KMH, $eta['speed_kmh']);
    }

    public function test_eta_is_clamped_between_one_minute_and_ten_hours(): void
    {
        $tiny = GeoService::estimateEta(31.5, 74.3, 31.5001, 74.3001);
        $this->assertGreaterThanOrEqual(1, $tiny['eta_minutes']);

        $huge = GeoService::estimateEta(-33.9, 151.2, 31.5, 74.3);
        $this->assertLessThanOrEqual(600, $huge['eta_minutes']);
    }

    public function test_road_distance_is_straight_line_times_road_factor(): void
    {
        $eta = GeoService::estimateEta(31.5, 74.3, 31.6, 74.4);

        $this->assertNotNull($eta['straight_line_km']);
        $this->assertEqualsWithDelta(
            round($eta['straight_line_km'] * GeoService::ROAD_FACTOR, 2),
            $eta['distance_km'],
            0.01
        );
    }

    public function test_distance_is_zero_when_any_coordinate_is_missing(): void
    {
        $this->assertSame(0.0, GeoService::distance(null, 74.3, 31.5, 74.4));
        $this->assertSame(0.0, GeoService::distance(31.5, 74.3, null, 74.4));
    }

    public function test_distance_between_known_points_is_positive(): void
    {
        $km = GeoService::distance(31.5, 74.3, 31.6, 74.4);

        $this->assertGreaterThan(0.0, $km);
    }
}
