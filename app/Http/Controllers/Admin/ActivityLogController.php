<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        // Optional filters
        if ($request->filled('user')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->user}%")
                ->orWhere('email', 'like', "%{$request->user}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->filled('model')) {
            $query->where('model_type', 'like', "%{$request->model}%");
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // ✅ Hide current user's logs only when explicitly requested
        if ($request->boolean('hide_self')) {
            $query->where(function ($q) {
                $q->whereNull('user_id')   // keep system logs
                ->orWhere('user_id', '!=', auth()->id()); // include everyone else
            });
        }

        $logs = $query->paginate(25);

        $filtersUsed = $request->hasAny(['user', 'action', 'model', 'from', 'to', 'include_self']);

        // Summary for cards (keep your existing logic)
        $summary = [
            'recent' => ActivityLog::where('created_at', '>=', now()->subDay())->count(),
            'active_users' => ActivityLog::distinct('user_id')
                                ->where('created_at', '>=', now()->subDays(7))
                                ->count('user_id'),
            'top_action' => ActivityLog::select('action')
                                ->groupBy('action')
                                ->orderByRaw('COUNT(*) DESC')
                                ->first(),
        ];

        return view('admin.activity.index', compact('logs', 'summary', 'filtersUsed'));
    }

    /** Export JSON or CSV */
    public function export(Request $request)
    {
        $format = $request->query('format', 'json');
        $logs = ActivityLog::latest()->take(1000)->get();

        if ($format === 'csv') {
            $csv = $logs->map(function ($log) {
                return implode(',', [
                    $log->id,
                    '"' . ($log->user->name ?? 'System') . '"',
                    '"' . $log->action . '"',
                    '"' . class_basename($log->model_type ?? '-') . '"',
                    '"' . str_replace('"', '""', json_encode($log->metadata)) . '"',
                    '"' . $log->created_at . '"',
                ]);
            })->implode("\n");

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="activity_logs.csv"');
        }

        return response()->streamDownload(function () use ($logs) {
            echo $logs->toJson(JSON_PRETTY_PRINT);
        }, 'activity_logs.json');
    }

    /** Show all logs for a specific user */
    public function user($id)
    {
        $user = User::findOrFail($id);
        $logs = ActivityLog::where('user_id', $id)->latest()->paginate(25);
        return view('admin.activity.user', compact('user', 'logs'));
    }
}