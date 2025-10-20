<?php

namespace App\Http\Controllers;

use App\Models\UserDiveLog;
use App\Models\DiveSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class UserDiveLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) auth()->id();
        $selectedYear = (int) $request->input('year', now()->year);

        // Cache the heavy bits briefly per user+year
        $cacheKey = "logbook:index:{$userId}:{$selectedYear}";
        [$logs, $recentDives, $stats, $availableYears, $siteCoords, $dailyDiveCounts, $calendar] =
            Cache::remember($cacheKey, 60, function () use ($userId, $selectedYear) {

                // Base logs (only columns we render in the table/cards)
                $logs = UserDiveLog::with(['site:id,name,lat,lng'])
                    ->where('user_id', $userId)
                    ->orderByDesc('dive_date')
                    ->get([
                        'id','user_id','dive_site_id','dive_date','title','depth','duration',
                        'buddy','notes','visibility','rating'
                    ])
                    ->values();

                // dive numbers (latest = highest number)
                $totalDives = $logs->count();
                $logs->each(function ($log, $index) use ($totalDives) {
                    $log->dive_number = $totalDives - $index;
                });

                // Stats (SQL aggregates over all years)
                $agg = UserDiveLog::where('user_id', $userId)->selectRaw('
                        COUNT(*) as total_dives,
                        COALESCE(SUM(duration),0) as total_minutes,
                        COALESCE(MAX(depth),0) as deepest_dive,
                        COALESCE(MAX(duration),0) as longest_dive,
                        COALESCE(AVG(depth),0) as avg_depth,
                        COALESCE(AVG(duration),0) as avg_duration
                    ')->first();

                $totalMinutes = (int) $agg->total_minutes;
                $stats = [
                    'totalDives'       => (int) $agg->total_dives,
                    'totalHours'       => intdiv($totalMinutes, 60),
                    'remainingMinutes' => $totalMinutes % 60,
                    'deepestDive'      => (float) $agg->deepest_dive,
                    'longestDive'      => (int) $agg->longest_dive,
                    'averageDepth'     => round((float) $agg->avg_depth, 1),
                    'averageDuration'  => (int) round((float) $agg->avg_duration),
                ];

                // Recent (already ordered)
                $recentDives = $logs->take(3);

                // Most-dived site name (SQL)
                $mostSite = UserDiveLog::where('user_id', $userId)
                    ->whereNotNull('dive_site_id')
                    ->select('dive_site_id', DB::raw('COUNT(*) as c'))
                    ->groupBy('dive_site_id')
                    ->orderByDesc('c')
                    ->first();
                $stats['siteName'] = $mostSite
                    ? optional(DiveSite::find($mostSite->dive_site_id))->name ?? 'N/A'
                    : 'N/A';

                // Unique sites visited (count distinct)
                $stats['uniqueSitesVisited'] = (int) UserDiveLog::where('user_id', $userId)
                    ->whereNotNull('dive_site_id')
                    ->distinct('dive_site_id')
                    ->count('dive_site_id');

                // Available years (distinct YEAR(dive_date))
                $availableYears = UserDiveLog::where('user_id', $userId)
                    ->selectRaw('DISTINCT YEAR(dive_date) as y')
                    ->orderByDesc('y')
                    ->pluck('y')
                    ->map(fn ($y) => (int) $y)
                    ->values();

                // Site markers (distinct sites actually used)
                $siteCoords = DiveSite::whereIn('id', function ($q) use ($userId) {
                        $q->select('dive_site_id')
                          ->from('user_dive_logs')
                          ->where('user_id', $userId)
                          ->whereNotNull('dive_site_id');
                    })
                    ->get(['name','lat','lng'])
                    ->map(fn ($s) => [
                        'name' => $s->name,
                        'lat'  => (float) $s->lat,
                        'lng'  => (float) $s->lng,
                    ])->values();

                // Chart data: counts per day for selected year (SQL)
                $dailyDiveCounts = UserDiveLog::where('user_id', $userId)
                    ->whereYear('dive_date', $selectedYear)
                    ->selectRaw('DATE(dive_date) as d, COUNT(*) as c')
                    ->groupBy('d')
                    ->pluck('c', 'd'); // ['YYYY-MM-DD' => N]

                // Calendar scaffold (weeks + labels)
                $calendar = $this->buildCalendarGrid($selectedYear);

                return [$logs, $recentDives, $stats, $availableYears, $siteCoords, $dailyDiveCounts, $calendar];
            });

        // Unpack calendar for the view
        [$monthLabels, $dayLabels, $weeks] = $calendar;

        return view('logbook.index', [
            'logs'               => $logs,
            'recentDives'        => $recentDives,
            'totalDives'         => $stats['totalDives'],
            'totalHours'         => $stats['totalHours'],
            'remainingMinutes'   => $stats['remainingMinutes'],
            'deepestDive'        => $stats['deepestDive'],
            'longestDive'        => $stats['longestDive'],
            'averageDepth'       => $stats['averageDepth'],
            'averageDuration'    => $stats['averageDuration'],
            'siteName'           => $stats['siteName'],
            'uniqueSitesVisited' => $stats['uniqueSitesVisited'],
            'dailyDiveCounts'    => $dailyDiveCounts,
            'siteCoords'         => $siteCoords,
            'availableYears'     => $availableYears,
            'selectedYear'       => $selectedYear,
            'monthLabels'        => $monthLabels,
            'dayLabels'          => $dayLabels,
            'weeks'              => $weeks,
        ]);
    }

    public function create()
    {
        $sites = DiveSite::orderBy('name')->get(['id','name','lat','lng']);
        $siteOptions = $sites->map(fn ($s) => [
            'id' => $s->id, 'name' => $s->name, 'lat' => $s->lat, 'lng' => $s->lng,
        ]);

        return view('logbook.create', compact('sites', 'siteOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dive_site_id' => 'nullable|exists:dive_sites,id',
            'dive_date'    => 'required|date',
            'title'        => 'nullable|string|max:255',
            'depth'        => 'required|numeric|min:0',
            'duration'     => 'required|integer|min:0',
            'buddy'        => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:2000',
            'air_start'    => 'nullable|numeric|min:0',
            'air_end'      => 'nullable|numeric|min:0',
            'temperature'  => 'nullable|numeric',
            'suit_type'    => 'nullable|string|max:100',
            'tank_type'    => 'nullable|string|max:100',
            'weight_used'  => 'nullable|string|max:100',
            'visibility'   => 'nullable|numeric|min:0',
            'rating'       => 'nullable|integer|min:1|max:5',
        ]);

        // logical check: end ≤ start (when both present)
        if (isset($validated['air_start'], $validated['air_end']) &&
            $validated['air_end'] > $validated['air_start']) {
            return back()->withErrors(['air_end' => 'Ending pressure cannot exceed starting pressure.'])
                        ->withInput();
        }

        // Attach time (keep current time of day)
        $validated['dive_date'] = \Carbon\Carbon::parse($validated['dive_date'])->setTimeFrom(\Carbon\Carbon::now());
        $validated['user_id']   = auth()->id();

        \App\Models\UserDiveLog::create($validated);

        log_activity('dive_log_created', auth()->user(), [
            'dive_site_id' => $validated['dive_site_id'] ?? null,
            'dive_date' => $validated['dive_date']->toDateString(),
            'depth' => $validated['depth'],
            'duration' => $validated['duration'],
        ]);

       $userId = (int) auth()->id();
        $diveYear = (int) Carbon::parse($validated['dive_date'])->year;
        Cache::forget("logbook:index:{$userId}:{$diveYear}");
        Cache::forget("logbook:index:{$userId}:" . now()->year);

        return redirect()->route('logbook.index')->with('success', 'Dive logged!');
    }

    public function chart(Request $request)
    {
        $userId = (int) auth()->id();
        $selectedYear = (int) $request->input('year', now()->year);

        $dailyDiveCounts = \App\Models\UserDiveLog::where('user_id', $userId)
            ->whereYear('dive_date', $selectedYear)
            ->selectRaw('DATE(dive_date) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        [$monthLabels, $dayLabels, $weeks] = $this->buildCalendarGrid($selectedYear);

        return view('logbook._chart', compact('monthLabels','dayLabels','weeks','dailyDiveCounts'))->render();
    }

    public function table(Request $request)
    {
        $userId = (int) auth()->id();

        $query = \App\Models\UserDiveLog::with(['site:id,name'])
            ->where('user_id', $userId)
            ->latest('dive_date');

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('site', fn($s) => $s->where('name', 'like', "%{$search}%"))
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
        $userId = (int) auth()->id();

        $log = \App\Models\UserDiveLog::with('site')
            ->where('user_id', $userId)
            ->findOrFail($id);

        // ✅ Order oldest → newest so newest has highest number
        $sortedIds = \App\Models\UserDiveLog::where('user_id', $userId)
            ->orderBy('dive_date', 'asc')
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->toArray();

        $index = array_search($log->id, $sortedIds, true);
        $diveNumber = $index !== false ? $index + 1 : null;

        // ✅ Find neighbors relative to this order
        $prevId = $sortedIds[$index - 1] ?? null; // previous = older dive
        $nextId = $sortedIds[$index + 1] ?? null; // next = newer dive

        log_activity('dive_log_viewed', $log, [
            'id' => $log->id,
            'site' => optional($log->site)->name,
        ]);

        return view('logbook.show', compact('log', 'diveNumber', 'prevId', 'nextId'));
    }

    public function edit($id)
    {
        $userId = auth()->id();

        $log = \App\Models\UserDiveLog::where('user_id', $userId)
            ->with('site')
            ->findOrFail($id);

        $sites = \App\Models\DiveSite::orderBy('name')->get(['id', 'name', 'lat', 'lng']);
        $siteOptions = $sites->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'lat' => $s->lat,
            'lng' => $s->lng,
        ]);

        // ✅ Order by oldest → newest
        $userLogs = \App\Models\UserDiveLog::where('user_id', $userId)
            ->orderBy('dive_date', 'asc')
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->toArray();

        // Find position
        $diveIndex = array_search($log->id, $userLogs, true);

        // ✅ Dive #1 = oldest, highest number = newest
        $log->dive_number = $diveIndex !== false ? $diveIndex + 1 : null;

        log_activity('dive_log_edit_viewed', $log, [
            'id' => $log->id,
        ]);

        return view('logbook.edit', compact('log', 'siteOptions'));
    }

    public function update(Request $request, $id)
    {
        $userId = (int) auth()->id();
        $log = \App\Models\UserDiveLog::where('user_id', $userId)->findOrFail($id);

        $validated = $request->validate([
            'dive_site_id' => 'nullable|exists:dive_sites,id',
            'dive_date'    => 'required|date',
            'title'        => 'nullable|string|max:255',
            'depth'        => 'required|numeric|min:0',
            'duration'     => 'required|integer|min:0',
            'buddy'        => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:2000',
            'air_start'    => 'nullable|numeric|min:0',
            'air_end'      => 'nullable|numeric|min:0',
            'temperature'  => 'nullable|numeric',
            'suit_type'    => 'nullable|string|max:100',
            'tank_type'    => 'nullable|string|max:100',
            'weight_used'  => 'nullable|string|max:100',
            'visibility'   => 'nullable|numeric|min:0',
            'rating'       => 'nullable|integer|min:1|max:5',
        ]);

        if (isset($validated['air_start'], $validated['air_end']) &&
            $validated['air_end'] > $validated['air_start']) {
            return back()->withErrors(['air_end' => 'Ending pressure cannot exceed starting pressure.'])
                        ->withInput();
        }

        $validated['dive_date'] = \Carbon\Carbon::parse($validated['dive_date'])->setTimeFrom(\Carbon\Carbon::now());

        $log->update($validated);

        log_activity('dive_log_updated', $log, [
            'id' => $log->id,
            'dive_site_id' => $validated['dive_site_id'] ?? null,
            'depth' => $validated['depth'],
            'duration' => $validated['duration'],
        ]);

        // bust both caches (this year + all years)
        \Illuminate\Support\Facades\Cache::forget("logbook:index:{$userId}:{$log->dive_date->year}");
        \Illuminate\Support\Facades\Cache::forget("logbook:index:{$userId}:".now()->year);

        return redirect()->route('logbook.show', $log->id)->with('success', 'Dive updated!');
    }


    /** Build calendar scaffold: [monthLabels, dayLabels, weeks(Carbon[])] */
    private function buildCalendarGrid(int $year): array
    {
        $startDate = \Carbon\Carbon::create($year, 1, 1)->startOfWeek(\Carbon\Carbon::SUNDAY);
        $endDate   = \Carbon\Carbon::create($year, 12, 31)->endOfWeek(\Carbon\Carbon::SATURDAY);

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
                if ($day->day === 1 && $day->year == $year) {
                    $month = $day->format('M');
                    if (!in_array($month, $monthsSeen, true)) {
                        $monthsSeen[] = $month;
                        $label = $month;
                        break;
                    }
                }
            }
            $monthLabels[$i] = $label;
        }

        $dayLabels = ['S','M','T','W','T','F','S'];

        return [$monthLabels, $dayLabels, $weeks];
    }

    protected function getSortedDiveIds(?int $userId = null): array
    {
        $userId ??= auth()->id();

        return UserDiveLog::query()
            ->where('user_id', $userId)
            ->orderByDesc('dive_date')
            ->pluck('id')
            ->all(); 
    }

    public function countBySiteAndDate(Request $request)
    {
        $userId = auth()->id();
        $siteId = $request->query('site_id');
        $date   = $request->query('date');

        if (!$siteId || !$date) {
            return response()->json(['count' => 0]);
        }

        $count = UserDiveLog::where('user_id', $userId)
            ->where('dive_site_id', $siteId)
            ->whereDate('dive_date', $date)
            ->count();

        return response()->json(['count' => $count]);
    }
    
}