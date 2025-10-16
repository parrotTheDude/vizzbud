<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(string $action, $model = null, array $metadata = []): void
    {
        try {
            ActivityLog::create([
                'user_id'    => Auth::id(),
                'action'     => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id'   => $model?->id,
                'ip_address' => Request::ip(),
                'user_agent' => substr(Request::userAgent(), 0, 255),
                'metadata'   => $metadata ?: null,
            ]);
        } catch (\Throwable $e) {
            // Prevent logger failures from breaking app flow
            \Log::warning('Activity log failed: '.$e->getMessage());
        }
    }
}