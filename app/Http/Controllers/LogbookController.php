<?php


namespace App\Http\Controllers;

use App\Models\ConditionReport;

class LogbookController extends Controller
{
    public function index()
    {
        $logs = ConditionReport::with('site')->latest('reported_at')->take(50)->get();
        return view('logbook.index', compact('logs'));
    }
}