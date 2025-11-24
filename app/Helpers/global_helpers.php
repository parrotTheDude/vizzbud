<?php

use App\Services\ActivityLogger;

/**
 * ---------------------------------------------------------
 * LOG ACTIVITY
 * ---------------------------------------------------------
 */
if (!function_exists('log_activity')) {
    function log_activity(string $action, $model = null, array $meta = []): void
    {
        ActivityLogger::log($action, $model, $meta);
    }
}

/**
 * ---------------------------------------------------------
 * NORMALIZE EMAIL
 * ---------------------------------------------------------
 */
if (!function_exists('normalize_email')) {
    function normalize_email(?string $email): ?string
    {
        if ($email === null) return null;
        return strtolower(trim($email));
    }
}

/**
 * ---------------------------------------------------------
 * ANGLE DIFFERENCE (0–180)
 * ---------------------------------------------------------
 */
if (!function_exists('cond_angle_diff')) {
    function cond_angle_diff(float $a, float $b): float
    {
        $a = fmod($a + 360, 360);
        $b = fmod($b + 360, 360);
        $d = abs($a - $b);
        return $d > 180 ? 360 - $d : $d;
    }
}

/**
 * ---------------------------------------------------------
 * COMPUTE NUMERIC CONDITION SCORE 0–10
 * ---------------------------------------------------------
 */
if (!function_exists('compute_condition_score')) {
    function compute_condition_score(
        ?float $waveHeightM,
        ?float $wavePeriodS,
        ?float $waveDirDeg,
        ?float $windSpeedKt,
        ?float $windDirDeg,
        ?int   $exposureBearing = null
    ): float {

        if ($waveHeightM === null || $windSpeedKt === null) {
            return -1; // unknown
        }

        $score = 0;

        // 1) Wave height (0–4)
        $score += min(4, $waveHeightM / 0.6);

        // 2) Wind speed score — de-emphasise < 10 kt
        $windPoints = 0.0;

        if ($windSpeedKt !== null) {
            if ($windSpeedKt <= 10) {
                // Up to 10 kt: tiny influence (max 0.5 points)
                $windPoints = $windSpeedKt / 20.0;   // 0–0.5
            } else {
                // Above 10 kt: ramp up more aggressively, cap at 4 total
                // 10→25 kt gives roughly 0.5 → 4 points
                $windPoints = 0.5 + min(3.5, ($windSpeedKt - 10) / 4.0);
            }
        }

        $score += $windPoints;

        // 3) Long period swell penalty (0–2)
        if ($wavePeriodS !== null && $wavePeriodS >= 12) {
            $score += min(2, ($wavePeriodS - 12) / 2);
        }

        // 4) Exposure adjustments
        if ($exposureBearing !== null) {

            // Swell direction
            if ($waveDirDeg !== null) {
                $diff = cond_angle_diff($waveDirDeg, $exposureBearing);

                if ($diff < 30) {
                    $score += 2;      // direct swell exposure
                } elseif ($diff > 110) {
                    $score -= 1;      // significantly sheltered
                }
            }

            // Wind direction
            if ($windDirDeg !== null) {
                $diff = cond_angle_diff($windDirDeg, $exposureBearing);

                if ($diff < 30) {
                    $score += 1.5;    // direct wind exposure
                } elseif ($diff > 110) {
                    $score -= 0.5;    // sheltered from wind
                }
            }
        }

        return max(0, min(10, $score));
    }
}

/**
 * ---------------------------------------------------------
 * SCORE → STATUS
 * ---------------------------------------------------------
 */
if (!function_exists('compute_condition_status_from_score')) {
    function compute_condition_status_from_score(float $score): string
    {
        if ($score < 0) return 'unknown';
        return match (true) {
            $score <= 3 => 'green',
            $score <= 6 => 'yellow',
            default     => 'red',
        };
    }
}

/**
 * ---------------------------------------------------------
 * FULL STATUS CONVENIENCE WRAPPER
 * ---------------------------------------------------------
 */
if (!function_exists('compute_condition_status')) {
    function compute_condition_status(
        ?float $waveHeightM,
        ?float $windSpeedKt,
        ?float $wavePeriodS = null,
        ?float $waveDirDeg = null,
        ?float $windDirDeg = null,
        ?int   $exposureBearing = null,
    ): string {

        $score = compute_condition_score(
            $waveHeightM,
            $wavePeriodS,
            $waveDirDeg,
            $windSpeedKt,
            $windDirDeg,
            $exposureBearing
        );

        return compute_condition_status_from_score($score);
    }
}