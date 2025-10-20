<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiveSite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiveSiteController extends Controller
{
    public function index()
    {
        $sites = DiveSite::orderBy('name')
            ->select(['id', 'slug', 'name', 'region', 'country', 'dive_type', 'suitability', 'is_active', 'needs_review'])
            ->paginate(20);

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
            'timezone' => 'nullable|string|max:64',
            'country' => 'nullable|string|max:191',
            'region' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'dive_type' => 'nullable|in:shore,boat',
            'suitability' => 'nullable|in:Open Water,Advanced,Deep',
            'needs_review' => 'boolean',
        ]);

        // Generate unique slug
        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);

        // Default status values
        $validated['is_active'] = false; // 👈 New dive sites start inactive
        $validated['needs_review'] = true;

        // Create site
        $site = \App\Models\DiveSite::create($validated);

        // Redirect to edit
        return redirect()->route('admin.divesites.edit', $site->slug)
            ->with('success', 'Dive site created and set to inactive. You can now review and activate it.');
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