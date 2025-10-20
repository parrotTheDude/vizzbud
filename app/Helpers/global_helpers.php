<?php

use App\Services\ActivityLogger;

if (!function_exists('log_activity')) {
    /**
     * Global helper to log user/system actions.
     *
     * @param  string       $action
     * @param  mixed|null   $model
     * @param  array        $meta
     */
    function log_activity(string $action, $model = null, array $meta = []): void
    {
        ActivityLogger::log($action, $model, $meta);
    }

    if (! function_exists('normalize_email')) {
        function normalize_email(?string $email): ?string
        {
            if ($email === null) return null;
            return strtolower(trim($email));
        }
    }

    if (!function_exists('compute_condition_status')) {
        function compute_condition_status(
            ?float $waveM,
            ?float $windKt,
            float $greenMaxWaveM = 1.1,
            float $greenMaxWindKt = 12,
            float $yellowMaxWaveM = 1.8,
            float $yellowMaxWindKt = 18,
        ): string {
            // 🚫 Missing data — safest to mark as "unknown" (grey)
            if ($waveM === null || $windKt === null) {
                return 'unknown';
            }

            // ✅ Green (Good conditions)
            if ($waveM <= $greenMaxWaveM && $windKt <= $greenMaxWindKt) {
                return 'green';
            }

            // 🟡 Yellow (Fair / Borderline)
            if ($waveM <= $yellowMaxWaveM && $windKt <= $yellowMaxWindKt) {
                return 'yellow';
            }

            // 🔴 Red (Poor / Unsafe)
            return 'red';
        }
    }
}