<?php

namespace App\Services;

use App\Models\Plot;

class GeospatialService
{
    /**
     * Verify submitted coordinates against plot centroid and optional boundary polygon.
     *
     * @return array{
     *   passed: bool,
     *   method: string,
     *   distance_meters: float,
     *   allowed_distance_meters: float,
     *   inside_boundary: bool|null,
     *   accuracy_ok: bool,
     *   spoof_risk: bool,
     *   reasons: array<int, string>
     * }
     */
    public function verifyLocation(
        Plot $plot,
        float $latitude,
        float $longitude,
        ?float $accuracyMeters = null,
        ?float $altitude = null,
        ?float $speedMps = null,
    ): array {
        $reasons = [];
        $centroidDistance = $this->haversineMeters(
            $latitude,
            $longitude,
            (float) $plot->gps_latitude,
            (float) $plot->gps_longitude,
        );

        $allowed = (float) config('land_verification.max_distance_meters', 250);
        $insideBoundary = null;
        $method = 'centroid';

        $polygon = $this->normalizePolygon($plot->boundary_geojson ?? null);
        if ($polygon !== null) {
            $method = 'boundary';
            $insideBoundary = $this->pointInPolygon($latitude, $longitude, $polygon);
            $buffer = (float) ($plot->boundary_buffer_meters ?? 15);
            $passedByBoundary = $insideBoundary || $centroidDistance <= max($allowed, $buffer);
            $passed = $passedByBoundary;
            if (! $insideBoundary && $centroidDistance > $buffer) {
                $reasons[] = 'Point is outside the registered plot boundary.';
            }
        } else {
            $passed = $centroidDistance <= $allowed;
            if (! $passed) {
                $reasons[] = 'Point is farther than the allowed radius from the plot centroid.';
            }
        }

        $maxAccuracy = (float) config('land_verification.max_gps_accuracy_meters', 50);
        $accuracyOk = $accuracyMeters === null || $accuracyMeters <= $maxAccuracy;
        if (! $accuracyOk) {
            $reasons[] = "GPS accuracy too low ({$accuracyMeters}m > {$maxAccuracy}m).";
            $passed = false;
        }

        $spoofRisk = false;
        if ($speedMps !== null && $speedMps > 40) {
            $spoofRisk = true;
            $reasons[] = 'Unrealistic movement speed detected.';
            $passed = false;
        }
        if ($altitude !== null && ($altitude < -100 || $altitude > 6000)) {
            $spoofRisk = true;
            $reasons[] = 'Implausible altitude reading.';
            $passed = false;
        }

        return [
            'passed' => $passed,
            'method' => $method,
            'distance_meters' => round($centroidDistance, 2),
            'allowed_distance_meters' => $allowed,
            'inside_boundary' => $insideBoundary,
            'accuracy_ok' => $accuracyOk,
            'spoof_risk' => $spoofRisk,
            'reasons' => $reasons,
        ];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>|null  $raw
     * @return array<int, array{0: float, 1: float}>|null
     */
    public function normalizePolygon(mixed $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($raw) || $raw === []) {
            return null;
        }

        // GeoJSON Polygon: coordinates[0] = outer ring [[lng,lat],...]
        if (($raw['type'] ?? null) === 'Polygon' && isset($raw['coordinates'][0])) {
            $ring = $raw['coordinates'][0];
            $points = [];
            foreach ($ring as $pair) {
                if (is_array($pair) && count($pair) >= 2) {
                    $points[] = [(float) $pair[1], (float) $pair[0]]; // lat, lng
                }
            }

            return count($points) >= 3 ? $points : null;
        }

        // Simple [[lat,lng],...]
        $points = [];
        foreach ($raw as $pair) {
            if (is_array($pair) && count($pair) >= 2) {
                $points[] = [(float) $pair[0], (float) $pair[1]];
            }
        }

        return count($points) >= 3 ? $points : null;
    }

    /**
     * Ray-casting point-in-polygon. Points are [lat, lng].
     *
     * @param  array<int, array{0: float, 1: float}>  $polygon
     */
    public function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $n = count($polygon);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $latI = $polygon[$i][0];
            $lngI = $polygon[$i][1];
            $latJ = $polygon[$j][0];
            $lngJ = $polygon[$j][1];

            $intersect = (($lngI > $lng) !== ($lngJ > $lng))
                && ($lat < ($latJ - $latI) * ($lng - $lngI) / (($lngJ - $lngI) ?: 1e-12) + $latI);

            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    public function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
