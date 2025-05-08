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

        $logs = UserDiveLog::with('site')
            ->where('user_id', $userId)
            ->orderByDesc('dive_date')
            ->get()
            ->values();
        
        $totalDives = $logs->count();
        
        $logs->each(function ($log, $index) use ($totalDives) {
            $log->dive_number = $totalDives - $index;
        });

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
        $recentDives = $logs->take(3); // Already sorted latest first

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

        $uniqueSitesVisited = $logs->pluck('dive_site_id')->filter()->unique()->count();

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
            'logs', 'recentDives', 'totalDives', 'totalHours', 'remainingMinutes',
            'deepestDive', 'longestDive', 'averageDepth', 'averageDuration',
            'siteName', 'dailyDiveCounts', 'siteCoords',
            'availableYears', 'selectedYear',
            'monthLabels', 'dayLabels', 'weeks',
            'uniqueSitesVisited'
        ));
    }

    public function create()
    {
        $sites = DiveSite::orderBy('name')->get();
        $siteOptions = $sites->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'lat' => $s->lat,
                'lng' => $s->lng,
            ];
        });
        return view('logbook.create', compact('sites', 'siteOptions'));
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'dive_site_id' => 'nullable|exists:dive_sites,id',
        'dive_date' => 'required|date',
        'depth' => 'required|numeric|min:0',
        'duration' => 'required|integer|min:0',
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

    // Force time to now even if user only picked date
    $date = Carbon::parse($validated['dive_date'])->setTimeFrom(Carbon::now());
    $validated['dive_date'] = $date;

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

    public function table(Request $request)
    {
        $userId = auth()->id();

        $query = UserDiveLog::with('site')
            ->where('user_id', $userId)
            ->latest('dive_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('site', fn($site) => $site->where('name', 'like', "%{$search}%"))
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhere('depth', $search)
                ->orWhere('duration', $search);
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('logbook._table', compact('logs'));
    }

    public function show($id)
    {
        $log = UserDiveLog::with('site')->where('user_id', auth()->id())->findOrFail($id);

        $sortedLogs = UserDiveLog::where('user_id', auth()->id())
            ->orderByDesc('dive_date')
            ->pluck('id')
            ->toArray();

        $index = array_search($log->id, $sortedLogs);
        $diveNumber = $index + 1;

        $prevId = $sortedLogs[$index + 1] ?? null;
        $nextId = $sortedLogs[$index - 1] ?? null;

        return view('logbook.show', compact('log', 'diveNumber', 'prevId', 'nextId'));
    }

    protected function getSortedDiveIds(): array
    {
        return UserDiveLog::where('user_id', auth()->id())
            ->orderBy('dive_date', 'desc')
            ->pluck('id')
            ->toArray();
    }

    public function edit($id)
    {
        $log = UserDiveLog::where('user_id', auth()->id())->findOrFail($id);
        $sites = DiveSite::orderBy('name')->get();

        return view('logbook.edit', compact('log', 'sites'));
    }

    public function update(Request $request, $id)
    {
        $log = UserDiveLog::where('user_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'dive_site_id' => 'nullable|exists:dive_sites,id',
            'dive_date' => 'required|date',
            'depth' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:0',
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

        $validated['dive_date'] = Carbon::parse($validated['dive_date'])->setTimeFrom(Carbon::now());

        $log->update($validated);

        return redirect()->route('logbook.show', $log->id)->with('success', 'Dive updated!');
    }
    
}