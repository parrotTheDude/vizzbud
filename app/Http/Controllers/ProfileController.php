<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use App\Models\UserDiveLog;
use App\Models\DiveSite;
use App\Models\DiveLevel;
use App\Models\UserProfile;

class ProfileController extends Controller
{
    /**
     * Display the logged-in user's profile.
     */
    public function show()
    {
        $user = Auth::user()->load('profile.diveLevel');

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to view your profile.');
        }

        // Ensure profile exists
        $user->load('profile.diveLevel');
        if (!$user->profile) {
            $user->profile()->create();
            $user->load('profile');
        }

        // Retrieve all user logs with site relationships
        $logs = UserDiveLog::where('user_id', $user->id)
            ->with(['site.region.state.country'])
            ->get();

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

        // Stats
        $totalDives = $logs->count();
        $avgDepth   = round($logs->avg('depth') ?? 0, 1);
        $totalMins  = $logs->sum('duration');
        $totalHours = round($totalMins / 60, 1);

        // Top site
        $topSiteGroup = $logs->groupBy('dive_site_id')->map->count()->sortDesc();
        $topSiteId = $topSiteGroup->keys()->first();
        $topSite = optional(DiveSite::with('region.state.country')->find($topSiteId))->name;

        // Top 3 sites
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

        $recentDives = $logs->sortByDesc('dive_date')->take(5);

        $mapSites = $logs->groupBy('dive_site_id')->map(function ($group, $siteId) {
            $site = $group->first()->site;
            if (!$site) return null;
            return [
                'id'      => $site->id,
                'name'    => $site->name,
                'slug'    => $site->slug,
                'lat'     => (float) $site->lat,
                'lng'     => (float) $site->lng,
                'region'  => optional($site->region)->name,
                'country' => optional(optional($site->region)->state->country)->name,
                'count'   => $group->count(),
            ];
        })->filter()->values();

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
        $user->load('profile');

        if (!$user->profile) {
            $user->profile()->create();
            $user->load('profile');
        }

        $diveLevels = DiveLevel::orderBy('rank')->get();

        return view('profile.edit', compact('user', 'diveLevels'));
    }

    /**
     * Update profile info and avatar.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $user->load('profile');

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'avatar'        => 'nullable|mimes:jpg,jpeg,png,webp,heic,heif|max:8192',
            'dive_level_id' => 'nullable|exists:dive_levels,id',
            'bio'           => 'nullable|string|max:160',
        ]);

        // 🧾 Update basic user info
        $user->update(['name' => $validated['name']]);

        // 🧱 Ensure profile exists
        if (!$user->profile) {
            $user->profile()->create();
            $user->load('profile');
        }

        $profileData = [
            'dive_level_id' => $validated['dive_level_id'] ?? null,
            'bio'           => $validated['bio'] ?? null,
        ];

        // 🖼 Handle avatar upload
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $manager = app(\Intervention\Image\ImageManager::class);

            $image = $manager->read($file)->cover(400, 400)->toWebp(80);
            $filename = 'avatars/' . uniqid('avatar_') . '.webp';

            // Delete old avatar if exists
            if ($user->profile->avatar_url && str_contains($user->profile->avatar_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $user->profile->avatar_url);
                Storage::disk('public')->delete($oldPath);
            }

            Storage::disk('public')->put($filename, (string) $image);
            $profileData['avatar_url'] = '/storage/' . $filename;
        }

        // 💾 Save updated profile data
        $user->profile->update($profileData);
        $user->refresh(); // ensure we have the latest data

        // ✅ Onboarding completion check
        $profile = $user->profile;
        $hasAvatar = !empty($profile->avatar_url);
        $hasCert   = !empty($profile->dive_level_id);
        $hasBio    = !empty($profile->bio);
        $hasDive   = ($user->diveLogs()->count() ?? 0) > 0;

        // Only mark true once — and only if all criteria are met
        if ($hasAvatar && $hasCert && $hasBio && $hasDive && !$profile->onboarding_complete) {
            $profile->onboarding_complete = true;
            $profile->save();
        }

        return redirect()
            ->route('profile.show')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Remove user's avatar safely.
     */
    public function removeAvatar(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile || !$profile->avatar_url) {
            return redirect()
                ->route('profile.edit')
                ->with('error', 'No profile photo found.');
        }

        // Delete file
        if (str_contains($profile->avatar_url, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $profile->avatar_url);
            Storage::disk('public')->delete($oldPath);
        }

        $profile->update(['avatar_url' => null]);

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Profile photo removed.');
    }
}