<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Suggestion;

class SuggestionController extends Controller
{
    public function store(Request $request)
    {
        if ($request->filled('website')) {
            abort(403, 'Spam detected.');
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:120',
            'message' => 'required|string|max:2000',
            'dive_site' => 'required|string|max:191',
            'dive_site_id' => 'nullable|integer|exists:dive_sites,id',
            'category' => 'nullable|string|max:120',
        ]);

        Suggestion::create([
            'dive_site_id'   => $validated['dive_site_id'] ?? null,
            'dive_site_name' => $validated['dive_site'],
            'name'           => $validated['name'] ?? null,
            'email'          => $validated['email'] ?? null,
            'category'       => $validated['category'] ?? null,
            'message'        => $validated['message'],
        ]);

        // ✅ Redirect instead of JSON
        return back()->with('success', 'Thanks for your feedback! We’ll review it soon.');
    }

    public function index()
    {
        $suggestions = Suggestion::latest()->paginate(20);

        return view('admin.suggestions.index', compact('suggestions'));
    }

    public function markReviewed(Suggestion $suggestion)
    {
        $suggestion->update([
            'reviewed' => true,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Suggestion marked as reviewed.');
    }
}