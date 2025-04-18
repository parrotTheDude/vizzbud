<?php

namespace App\Http\Controllers;

use App\Models\UserDiveLog;
use App\Models\DiveSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserDiveLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        // All logs for stats + table
        $logs = UserDiveLog::with('site')
            ->where('user_id', $userId)
            ->latest('dive_date')
            ->get();

        // Selected year for the chart
        $selectedYear = $request->input('year', now()->year);

        // Filter logs for the selected year (chart only)
        $chartLogs = $logs->filter(fn($log) =>
            Carbon::parse($log->dive_date)->year == $selectedYear
        );

        // Daily counts for the chart
        $dailyDiveCounts = $chartLogs->groupBy(function ($log) {
            return Carbon::parse($log->dive_date)->format('Y-m-d');
        })->map->count();

        // Available years for dropdown
        $availableYears = $logs->pluck('dive_date')->map(function ($date) {
            return Carbon::parse($date)->year;
        })->unique()->sortDesc()->values();

        // Dive stats across ALL years
        $totalDives = $logs->count();
        $totalMinutes = $logs->sum('duration');
        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;
        $deepestDive = $logs->max('depth');
        $longestDive = $logs->max('duration');
        $averageDepth = round($logs->avg('depth'), 1);
        $averageDuration = round($logs->avg('duration'));

        $mostDivedSiteId = $logs->groupBy('dive_site_id')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->keys()
            ->first();

        $siteName = optional(DiveSite::find($mostDivedSiteId))->name ?? 'N/A';

        // Dive site markers for map
        $siteCoords = $logs->pluck('site')->filter()->unique('id')->map(fn($site) => [
            'name' => $site->name,
            'lat' => $site->lat,
            'lng' => $site->lng,
        ])->values();

        // --- Chart layout prep (same as chart() method) ---
        $startDate = Carbon::create($selectedYear, 1, 1)->startOfWeek(Carbon::SUNDAY);
        $endDate = Carbon::create($selectedYear, 12, 31)->endOfWeek(Carbon::SATURDAY);

        $days = collect();
        for ($cursor = $startDate->copy(); $cursor <= $endDate; $cursor->addDay()) {
            $days->push($cursor->copy());
        }

        $weeks = $days->chunk(7);
        $monthLabels = [];
        $monthsSeen = [];

        foreach ($weeks as $i => $week) {
            $label = '';
            foreach ($week as $day) {
                if ($day->day === 1 && $day->year == $selectedYear) {
                    $month = $day->format('M');
                    if (!in_array($month, $monthsSeen)) {
                        $monthsSeen[] = $month;
                        $label = $month;
                        break;
                    }
                }
            }
            $monthLabels[$i] = $label;
        }

        $dayLabels = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

        return view('logbook.index', compact(
            'logs', 'totalDives', 'totalHours', 'remainingMinutes',
            'deepestDive', 'longestDive', 'averageDepth', 'averageDuration',
            'siteName', 'dailyDiveCounts', 'siteCoords',
            'availableYears', 'selectedYear',
            'monthLabels', 'dayLabels', 'weeks'
        ));
    }

    public function create()
    {
        $sites = DiveSite::orderBy('name')->get();
        return view('logbook.create', compact('sites'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dive_site_id' => 'nullable|exists:dive_sites,id',
            'dive_date' => 'required|date',
            'depth' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:0',
            'buddy' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'air_start' => 'nullable|numeric|min:0',
            'air_end' => 'nullable|numeric|min:0',
            'temperature' => 'nullable|numeric',
            'suit_type' => 'nullable|string|max:100',
            'tank_type' => 'nullable|string|max:100',
            'weight_used' => 'nullable|string|max:100',
            'visibility' => 'nullable|numeric|min:0',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        $validated['user_id'] = auth()->id();

        UserDiveLog::create($validated);

        return redirect()->route('logbook.index')->with('success', 'Dive logged!');
    }

    public function chart(Request $request)
    {
        $selectedYear = $request->input('year', now()->year);
        $userId = auth()->id();

        $logs = UserDiveLog::where('user_id', $userId)->get();

        $chartLogs = $logs->filter(fn($log) =>
            Carbon::parse($log->dive_date)->year == $selectedYear
        );

        $dailyDiveCounts = $chartLogs->groupBy(function ($log) {
            return Carbon::parse($log->dive_date)->format('Y-m-d');
        })->map->count();

        $startDate = Carbon::create($selectedYear, 1, 1)->startOfWeek(Carbon::SUNDAY);
        $endDate = Carbon::create($selectedYear, 12, 31)->endOfWeek(Carbon::SATURDAY);

        $days = collect();
        for ($cursor = $startDate->copy(); $cursor <= $endDate; $cursor->addDay()) {
            $days->push($cursor->copy());
        }

        $weeks = $days->chunk(7);
        $monthLabels = [];
        $monthsSeen = [];

        foreach ($weeks as $i => $week) {
            $label = '';
            foreach ($week as $day) {
                if ($day->day === 1 && $day->year == $selectedYear) {
                    $month = $day->format('M');
                    if (!in_array($month, $monthsSeen)) {
                        $monthsSeen[] = $month;
                        $label = $month;
                        break;
                    }
                }
            }
            $monthLabels[$i] = $label;
        }

        $dayLabels = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

        return view('logbook._chart', compact(
            'monthLabels', 'dayLabels', 'weeks', 'dailyDiveCounts'
        ))->render();
    }
}