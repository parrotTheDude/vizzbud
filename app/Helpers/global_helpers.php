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
}