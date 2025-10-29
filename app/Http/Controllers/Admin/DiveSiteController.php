<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiveSite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiveSiteController extends Controller
{
    public function index(Request $request)
    {
        $query = DiveSite::query();

        // 🔍 Search by name, region, or country
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('region', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('state', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                            ->orWhereHas('country', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            });
                        });
                });
            });
        }

        // 🔹 Filter by status
        $status = $request->input('status');
        if ($status === 'active') {
            $query->where('is_active', true)->where('needs_review', false);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false)->where('needs_review', false);
        } elseif ($status === 'review') {
            $query->where('needs_review', true);
        }

        // 🏁 Sort order: Needs Review first, then Active, then Inactive
        $query->orderByDesc('needs_review')
            ->orderByDesc('is_active')
            ->orderBy('name');

        $sites = $query->paginate(20);

        return view('admin.divesites.index', compact('sites'));
    }

    public function create()
    {
        return view('admin.divesites.create', [
            'mapboxToken' => config('services.mapbox.token'),
            'regions' => \App\Models\Region::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'region_id' => 'required|exists:regions,id',
            'dive_type' => 'required|in:shore,boat',
            'suitability' => 'required|in:Open Water,Advanced,Deep',
            'map_image_path' => 'nullable|string|max:255',
            'map_caption' => 'nullable|string|max:255',
            'marine_life' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = false;
        $validated['needs_review'] = true;

        $site = DiveSite::create($validated);

        return redirect()
            ->route('admin.divesites.edit', $site->slug)
            ->with('success', 'Dive site created successfully. You can now edit details and activate it.');
    }

    public function edit(DiveSite $diveSite)
    {
        return view('admin.divesites.edit', [
            'site' => $diveSite,
            'regions' => \App\Models\Region::orderBy('name')->get(),
            'mapboxToken' => config('services.mapbox.token'),
        ]);
    }

    public function update(Request $request, DiveSite $diveSite)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'region_name' => 'nullable|string|max:191',
            'state_name' => 'nullable|string|max:191',
            'country_name' => 'nullable|string|max:191',
            'dive_type' => 'nullable|in:shore,boat',
            'suitability' => 'nullable|in:Open Water,Advanced,Deep',
            'max_depth' => 'nullable|integer|min:0|max:100',
            'avg_depth' => 'nullable|integer|min:0|max:100',
            'description' => 'nullable|string',
            'hazards' => 'nullable|string',
            'pro_tips' => 'nullable|string',
            'entry_notes' => 'nullable|string',
            'parking_notes' => 'nullable|string',
            'marine_life' => 'nullable|string',
            'map_image_path' => 'nullable|string|max:255',
            'map_caption' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'needs_review' => 'boolean',
        ]);

        // ✅ Sanitize text fields (trim, normalize capitalization)
        $regionName = $this->sanitizeName($request->input('region_name'));
        $stateName  = $this->sanitizeName($request->input('state_name'));
        $countryName = $this->sanitizeName($request->input('country_name'));

        // ✅ Build / find related hierarchy
        $country = null;
        $state   = null;
        $region  = null;

        if ($countryName) {
            $country = \App\Models\Country::firstOrCreate(
                ['name' => $countryName],
                ['slug' => Str::slug($countryName)]
            );
        }

        if ($stateName && $country) {
            $state = \App\Models\State::firstOrCreate(
                ['name' => $stateName, 'country_id' => $country->id],
                ['slug' => Str::slug($stateName)]
            );
        }

        if ($regionName && $state) {
            $region = \App\Models\Region::firstOrCreate(
                ['name' => $regionName, 'state_id' => $state->id],
                ['slug' => Str::slug($regionName)]
            );
        }

        // ✅ Update slug if name changed
        if ($diveSite->name !== $request->name) {
            $validated['slug'] = \App\Models\DiveSite::uniqueSlugFrom($request->name, $diveSite->id);
        }

        // ✅ Merge relationships
        if ($region) {
            $validated['region_id'] = $region->id;
        }

        $diveSite->update(array_merge($validated, [
            'is_active' => $request->has('is_active'),
            'needs_review' => $request->has('needs_review'),
        ]));

        return redirect()
            ->route('admin.divesites.index')
            ->with('success', 'Dive site updated successfully, with location hierarchy synced.');
    }

    /**
     * 🔤 Helper: sanitize text names before saving
     */
    private function sanitizeName(?string $name): ?string
    {
        if (!$name) return null;

        $name = trim($name);
        // Normalize capitalization (Sydney → Sydney, new south wales → New South Wales)
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');

        return preg_replace('/\s+/', ' ', $name);
    }

    public function destroy(DiveSite $diveSite)
    {
        $diveSite->delete();

        return back()->with('success', 'Dive site deleted.');
    }
}