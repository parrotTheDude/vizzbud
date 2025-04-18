<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiveSiteController;
use App\Http\Controllers\ConditionReportController;
use App\Models\DiveSite;

Route::get('/', function () {
    $featured = DiveSite::whereHas('latestCondition') // 🔥 Only with data
        ->with('latestCondition')
        ->inRandomOrder()
        ->first();

    $sites = DiveSite::all();

    return view('home', compact('featured', 'sites'));
})->name('home');

Route::get('/dive-sites', [DiveSiteController::class, 'index'])->name('dive-sites.index');

Route::view('/logbook', 'logbook.index')->name('logbook.index');

Route::get('/reports', [ConditionReportController::class, 'index'])->name('report.index');
Route::get('/report', [ConditionReportController::class, 'create'])->name('report.create');
Route::post('/report', [ConditionReportController::class, 'store'])->name('report.store');