<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiveSiteController;
use App\Http\Controllers\ConditionReportController;
use App\Http\Controllers\UserDiveLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Models\DiveSite;

// 🌐 Home Page
Route::get('/', function () {
    $featured = DiveSite::whereHas('latestCondition') // Only with condition data
        ->with('latestCondition')
        ->inRandomOrder()
        ->first();

    $sites = DiveSite::all();

    $siteOptions = DiveSite::select('id', 'name')->orderBy('name')->get();

    return view('home', compact('featured', 'sites', 'siteOptions'));
})->name('home');

// 📍 Dive Sites Map/List
Route::get('/dive-sites', [DiveSiteController::class, 'index'])->name('dive-sites.index');

// 🌊 Public Condition Reports
Route::get('/reports', [ConditionReportController::class, 'index'])->name('report.index');
Route::get('/report', [ConditionReportController::class, 'create'])->name('report.create');
Route::post('/report', [ConditionReportController::class, 'store'])
    ->middleware('throttle:5,1') // max 5 submissions per minute
    ->name('report.store');

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

Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

Route::get('/verify-email/{token}', [VerifyEmailController::class, 'verify'])->name('verification.verify');

require __DIR__.'/auth.php';