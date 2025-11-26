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
function compute_condition_score(
    ?float $waveHeightM,
    ?float $wavePeriodS,
    ?float $waveDirDeg,
    ?float $windSpeedKt,
    ?float $windDirDeg,
    ?int   $exposureBearing = null,
    string $diveType = 'shore'
): float {

    if ($waveHeightM === null || $windSpeedKt === null) {
        return -1; // unknown
    }

    $score = 0;

    // ------------------------------------------------
    // 1) Wave height (0–4)
    // ------------------------------------------------
    $score += min(4, $waveHeightM / 0.6);

    // ------------------------------------------------
    // 2) Wind base score (keep your logic)
    // ------------------------------------------------
    // Wind impact: soft ≤12 kt, then ramps to max 4 at 30 kt
    if ($windSpeedKt <= 12) {
        // 0–12 kt → 0–0.4 (gentle)
        $score += $windSpeedKt / 30.0;   // 12/30 = 0.4
    } else {
        // 12→30 kt → 0.4 → 4.0
        // Increase needed from 0.4 to 4.0 = 3.6 points
        // Over 18 kt range (30-12)
        $score += min(4.0, 0.4 + (($windSpeedKt - 12) * (3.6 / 18)));
    }

    // ------------------------------------------------
    // 3) Long-period swell penalty (0–2)
    // ------------------------------------------------
    if ($wavePeriodS !== null && $wavePeriodS >= 12) {
        $score += min(2, ($wavePeriodS - 12) / 2);
    }

    // ------------------------------------------------
    // 4) Exposure adjustments (Sydney realistic)
    // ------------------------------------------------
    if ($exposureBearing !== null) {

        // ---- Helper: determine exposure band ----
        $exp = function(float $dir, int $bearing) {
            $diff = cond_angle_diff($dir, $bearing);

            if ($diff <= 20)       return ['zone' => 'strong', 'diff' => $diff];
            elseif ($diff <= 60)   return ['zone' => 'medium', 'diff' => $diff];
            elseif ($diff <= 120)  return ['zone' => 'weak',   'diff' => $diff];
            else                   return ['zone' => 'off',    'diff' => $diff];
        };

        // ----------------------
        //  SWELL EXPOSURE
        // ----------------------
        if ($waveDirDeg !== null) {
            $e = $exp($waveDirDeg, $exposureBearing);

            switch ($e['zone']) {
                case 'strong':
                    $score += 1.8;  // direct hit
                    break;

                case 'medium':
                    // 20° → +1.8  ,  60° → 0
                    $score += 1.8 * (1 - (($e['diff'] - 20) / 40));
                    break;

                case 'off':
                    $score -= 0.6; // offshore swell
                    break;
            }
        }

        // ----------------------
        //  WIND EXPOSURE
        // ----------------------
        if ($windDirDeg !== null) {
            $e = $exp($windDirDeg, $exposureBearing);

            $maxWind =
                ($diveType === 'shore') ? 0.7 :  // strong
                0.5;                              // moderate

            $maxShelter =
                ($diveType === 'shore') ? -0.15 : // strong shelter benefit
                -0.1;                            // mild shelter

            switch ($e['zone']) {
                case 'strong':
                    $score += $maxWind;
                    break;

                case 'medium':
                    $score += $maxWind * (1 - (($e['diff'] - 20) / 40));
                    break;

                case 'off':
                    $score += $maxShelter;
                    break;
            }
        }
    }

    return max(0, min(10, $score));
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