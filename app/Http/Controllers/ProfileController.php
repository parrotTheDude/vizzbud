<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserDiveLog;
use App\Models\DiveSite;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

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

        // --- Retrieve all user logs once with site relationships
        $logs = UserDiveLog::where('user_id', $user->id)
            ->with(['site.region.state.country'])
            ->get();

        // --- If no dives, short-circuit gracefully
        if ($logs->isEmpty()) {
            return view('profile.show', [
                'user'        => $user,
                'stats'       => [
                    'total_dives' => 0,
                    'avg_depth'   => 0,
                    'total_hours' => 0,
                    'top_site'    => null,
                ],
                'topSites'    => collect(),
                'recentDives' => collect(),
                'mapSites'    => collect(),
            ]);
        }

        // --- Stats
        $totalDives = $logs->count();
        $avgDepth   = round($logs->avg('depth') ?? 0, 1);
        $totalMins  = $logs->sum('duration');
        $totalHours = round($totalMins / 60, 1);

        // --- Top site (most frequently visited)
        $topSiteGroup = $logs->groupBy('dive_site_id')
            ->map->count()
            ->sortDesc();

        $topSiteId = $topSiteGroup->keys()->first();
        $topSite   = optional(DiveSite::with('region.state.country')->find($topSiteId))->name;

        // --- Top 3 sites
        $topSites = $topSiteGroup->take(3)->map(function ($count, $siteId) {
            $site = DiveSite::with('region.state.country')->find($siteId);
            if (!$site) return null;
            return (object)[
                'name'        => $site->name,
                'slug'        => $site->slug,
                'region'      => optional($site->region)->name,
                'country'     => optional(optional($site->region)->state->country)->name,
                'dives_count' => $count,
            ];
        })->filter()->values();

        // --- Recent dives
        $recentDives = $logs->sortByDesc('dive_date')->take(5);

        // --- Map data (unique sites)
        $mapSites = $logs->groupBy('dive_site_id')->map(function ($group, $siteId) {
            $site = $group->first()->site;
            if (!$site) return null;
            return [
                'id'       => $site->id,
                'name'     => $site->name,
                'slug'     => $site->slug,
                'lat'      => (float) $site->lat,
                'lng'      => (float) $site->lng,
                'region'   => optional($site->region)->name,
                'country'  => optional(optional($site->region)->state->country)->name,
                'count'    => $group->count(),
            ];
        })->filter()->values();

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

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'avatar' => 'nullable|mimes:jpg,jpeg,png,webp,heic,heif|max:8192',
        ]);

        // 🖼 Handle avatar upload
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');

            // Use the bound ImageManager service (from AppServiceProvider)
            $manager = app(\Intervention\Image\ImageManager::class);
            $image = $manager
                ->read($file)          // new v3 syntax replaces ->make()
                ->cover(400, 400)      // crop + resize while keeping centre
                ->toWebp(80);          // compress to WebP format

            // Generate filename
            $filename = 'avatars/' . uniqid('avatar_') . '.webp';

            // Delete old avatar if exists
            if ($user->avatar_url && str_contains($user->avatar_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $user->avatar_url);
                \Storage::disk('public')->delete($oldPath);
            }

            // Save new image
            \Storage::disk('public')->put($filename, (string) $image);

            // Update DB path
            $data['avatar_url'] = '/storage/' . $filename;
        }

        $user->update($data);

        return redirect()
            ->route('profile.show')
            ->with('success', 'Profile updated successfully!');
    }
}