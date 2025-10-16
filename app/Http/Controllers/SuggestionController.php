<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Suggestion;

class SuggestionController extends Controller
{
    public function store(Request $request)
    {
        if ($request->filled('website')) {
            log_activity('suggestion_spam_blocked', null, [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
                'message' => $request->input('message'),
            ]);
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

        log_activity('suggestion_submitted', null, [
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'] ?? null,
            'dive_site' => $validated['dive_site'],
            'category' => $validated['category'] ?? null,
        ]);

        // ✅ Redirect instead of JSON
        return back()->with('success', 'Thanks for your feedback! We’ll review it soon.');
    }

    public function index()
    {
        $suggestions = Suggestion::latest()->paginate(20);

        log_activity('admin_suggestions_index_viewed', $request->user());

        return view('admin.suggestions.index', compact('suggestions'));
    }

    public function markReviewed(Suggestion $suggestion)
    {
        $suggestion->update([
            'reviewed' => true,
            'reviewed_at' => now(),
        ]);

        log_activity('suggestion_marked_reviewed', $suggestion, [
            'reviewed_by' => optional(auth()->user())->id,
        ]);

        return back()->with('success', 'Suggestion marked as reviewed.');
    }
}