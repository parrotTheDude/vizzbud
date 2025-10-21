<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserDiveLog;
use App\Models\DiveSite;

class ProfileController extends Controller
{
    /**
     * Display the logged-in user's profile.
     */
    public function show()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to view your profile.');
        }

        // --- Dive stats
        $totalDives = UserDiveLog::where('user_id', $user->id)->count();
        $avgDepth   = round(UserDiveLog::where('user_id', $user->id)->avg('depth') ?? 0, 1);
        $totalMins  = UserDiveLog::where('user_id', $user->id)->sum('duration');
        $totalHours = round($totalMins / 60, 1);

        // --- Top site (most frequently visited)
        $topSiteRow = UserDiveLog::where('user_id', $user->id)
            ->selectRaw('dive_site_id, COUNT(*) as total')
            ->groupBy('dive_site_id')
            ->orderByDesc('total')
            ->with('site:id,name,slug,region,country')
            ->first();

        $topSite = $topSiteRow?->site?->name ?? null;

        // --- Top 3 most dived sites
        $topSites = UserDiveLog::where('user_id', $user->id)
            ->selectRaw('dive_site_id, COUNT(*) as dives_count')
            ->groupBy('dive_site_id')
            ->orderByDesc('dives_count')
            ->with('site:id,name,slug,region,country')
            ->take(3)
            ->get()
            ->map(fn($row) => (object)[
                'name'        => $row->site->name,
                'slug'        => $row->site->slug,
                'region'      => $row->site->region,
                'country'     => $row->site->country,
                'dives_count' => $row->dives_count,
            ]);

        // --- Recent dives
        $recentDives = UserDiveLog::where('user_id', $user->id)
            ->with('site:id,name,slug')
            ->orderByDesc('dive_date')
            ->take(5)
            ->get();

        // --- Map data (unique sites with lat/lng for user)
        $mapSites = UserDiveLog::where('user_id', $user->id)
            ->selectRaw('dive_site_id, COUNT(*) as dives_count')
            ->groupBy('dive_site_id')
            ->with('site:id,name,slug,lat,lng,region,country')
            ->get()
            ->map(function ($row) {
                return [
                    'id'       => $row->site->id,
                    'name'     => $row->site->name,
                    'slug'     => $row->site->slug,
                    'lat'      => (float) $row->site->lat,
                    'lng'      => (float) $row->site->lng,
                    'region'   => $row->site->region,
                    'country'  => $row->site->country,
                    'count'    => $row->dives_count,
                ];
            });

        // --- Compile summary stats
        $stats = [
            'total_dives' => $totalDives,
            'avg_depth'   => $avgDepth,
            'total_hours' => $totalHours,
            'top_site'    => $topSite,
        ];

        return view('profile.show', [
            'user'        => $user,
            'stats'       => $stats,
            'topSites'    => $topSites,
            'recentDives' => $recentDives,
            'mapSites'    => $mapSites,
        ]);
    }

    /**
     * Show edit form for profile.
     */
    public function edit()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        return view('profile.edit', compact('user'));
    }

    /**
     * Handle profile updates.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'bio'           => 'nullable|string|max:1000',
            'certification' => 'nullable|string|max:255',
            'avatar'        => 'nullable|image|max:2048',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_url'] = "/storage/{$path}";
        }

        $user->update($data);

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully!');
    }
}