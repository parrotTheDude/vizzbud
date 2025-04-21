<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiveSiteController;
use App\Http\Controllers\ConditionReportController;
use App\Http\Controllers\UserDiveLogController;
use App\Models\DiveSite;

// 🌐 Home Page
Route::get('/', function () {
    $featured = DiveSite::whereHas('latestCondition') // Only with condition data
        ->with('latestCondition')
        ->inRandomOrder()
        ->first();

    $sites = DiveSite::all();

    return view('home', compact('featured', 'sites'));
})->name('home');

// 📍 Dive Sites Map/List
Route::get('/dive-sites', [DiveSiteController::class, 'index'])->name('dive-sites.index');

// 🌊 Public Condition Reports
Route::get('/reports', [ConditionReportController::class, 'index'])->name('report.index');
Route::get('/report', [ConditionReportController::class, 'create'])->name('report.create');
Route::post('/report', [ConditionReportController::class, 'store'])->name('report.store');

// 📘 Personal Dive Log (guest-friendly view)
Route::get('/logbook', [UserDiveLogController::class, 'index'])->name('logbook.index');

// ✍️ Authenticated routes for logging dives
Route::middleware(['auth'])->group(function () {
    Route::get('/logbook/create', [UserDiveLogController::class, 'create'])->name('logbook.create');
    Route::post('/logbook', [UserDiveLogController::class, 'store'])->name('logbook.store');
    Route::get('/logbook/chart', [UserDiveLogController::class, 'chart'])->name('logbook.chart');
    Route::get('/logbook/table', [UserDiveLogController::class, 'table'])->name('logbook.table');
    Route::get('/logbook/{log}', [UserDiveLogController::class, 'show'])->name('logbook.show');
});

require __DIR__.'/auth.php';