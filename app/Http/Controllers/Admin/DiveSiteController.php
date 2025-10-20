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
                ->orWhere('region', 'like', "%{$search}%")
                ->orWhere('country', 'like', "%{$search}%");
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
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'region' => 'required|string|max:191',
            'country' => 'required|string|max:191',
            'dive_type' => 'required|in:shore,boat',
            'suitability' => 'required|in:Open Water,Advanced,Deep',
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        $validated['is_active'] = false; 
        $validated['needs_review'] = true;

        $site = \App\Models\DiveSite::create($validated);

        return redirect()
            ->route('admin.divesites.edit', $site->slug)
            ->with('success', 'Dive site created successfully. You can now edit details and activate it.');
    }

    public function edit(DiveSite $diveSite)
    {
        return view('admin.divesites.edit', [
            'site' => $diveSite, 
            'mapboxToken' => config('services.mapbox.token'),
        ]);
    }

    public function update(Request $request, DiveSite $diveSite)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'region' => 'nullable|string|max:191',
            'country' => 'nullable|string|max:191',
            'timezone' => 'nullable|string|max:64',
            'dive_type' => 'nullable|in:shore,boat',
            'suitability' => 'nullable|in:Open Water,Advanced,Deep',
            'max_depth' => 'nullable|integer|min:0|max:100',
            'avg_depth' => 'nullable|integer|min:0|max:100',
            'description' => 'nullable|string',
            'hazards' => 'nullable|string',
            'pro_tips' => 'nullable|string',
            'entry_notes' => 'nullable|string',
            'parking_notes' => 'nullable|string',
            'is_active' => 'boolean',
            'needs_review' => 'boolean',
        ]);

        // Auto-generate unique slug if name changed
        if ($diveSite->isDirty('name') || $request->name !== $diveSite->name) {
            $validated['slug'] = \App\Models\DiveSite::uniqueSlugFrom($request->name, $diveSite->id);
        }

        $diveSite->update(array_merge($validated, [
            'is_active' => $request->has('is_active'),
            'needs_review' => $request->has('needs_review'),
        ]));

        return redirect()->route('admin.divesites.index')
            ->with('success', 'Dive site updated successfully.');
    }

    public function destroy(DiveSite $diveSite)
    {
        $diveSite->delete();

        return back()->with('success', 'Dive site deleted.');
    }
}