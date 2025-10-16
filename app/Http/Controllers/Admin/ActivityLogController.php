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

        // 🔍 Search filters
        if ($user = $request->input('user')) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('name', 'like', "%{$user}%")
                  ->orWhere('email', 'like', "%{$user}%");
            });
        }

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($model = $request->input('model')) {
            $query->where('model_type', 'like', "%{$model}%");
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($search = $request->input('q')) {
            $query->where(function ($q2) use ($search) {
                $q2->where('action', 'like', "%{$search}%")
                    ->orWhere('metadata', 'like', "%{$search}%")
                    ->orWhere('model_type', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->paginate(25)->appends($request->query());

        // 📊 Summary analytics
        $summary = cache()->remember('activity_summary', 30, function () {
            return [
                'recent' => ActivityLog::where('created_at', '>=', now()->subDay())->count(),
                'active_users' => ActivityLog::where('created_at', '>=', now()->subDays(7))
                    ->distinct('user_id')->count(),
                'top_action' => ActivityLog::select('action', DB::raw('COUNT(*) as c'))
                    ->groupBy('action')->orderByDesc('c')->first(),
            ];
        });

        return view('admin.activity.index', [
            'logs' => $logs,
            'summary' => $summary,
        ]);
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