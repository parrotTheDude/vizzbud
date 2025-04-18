<?php

namespace App\Http\Controllers;

use App\Models\ConditionReport;
use App\Models\DiveSite;
use Illuminate\Http\Request;

class ConditionReportController extends Controller
{
    public function index()
    {
        $reports = \App\Models\DiveReport::latest()->paginate(10); // adjust model as needed
        return view('reports.index', compact('reports'));
    }

    public function create()
    {
        $sites = DiveSite::orderBy('name')->get();
        return view('report.create', compact('sites'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dive_site_id' => 'required|exists:dive_sites,id',
            'viz_rating' => 'nullable|numeric|min:0|max:30',
            'comment' => 'nullable|string|max:1000',
            'reported_at' => 'required|date',
        ]);
    
        ConditionReport::create($validated);
    
        return redirect()->route('report.create')->with('success', 'Thanks for your report!');
    }
}
