<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiveSiteController;
use App\Http\Controllers\ConditionReportController;
use App\Http\Controllers\UserDiveLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Models\DiveSite;
use App\Http\Controllers\BlogPostController;

// 🌐 Home Page
Route::get('/', function () {
    $featured = DiveSite::whereHas('latestCondition')
        ->with('latestCondition')
        ->inRandomOrder()
        ->first();

    $sites = DiveSite::all();
    $siteOptions = DiveSite::select('id', 'name')->orderBy('name')->get();

    return view('home', compact('featured', 'sites', 'siteOptions'));
})->name('home');

// 📍 Dive Sites Map
Route::get('/dive-sites', [DiveSiteController::class, 'index'])->name('dive-sites.index');
Route::get('/dive-sites/{diveSite}', [DiveSiteController::class, 'show'])->name('dive-sites.show');

// 🌊 Public Condition Reports
Route::prefix('report')->group(function () {
    Route::get('/', [ConditionReportController::class, 'create'])->name('report.create');
    Route::post('/', [ConditionReportController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('report.store');
});
Route::get('/reports', [ConditionReportController::class, 'index'])->name('report.index');

// 📘 Public Dive Log View
Route::get('/logbook', [UserDiveLogController::class, 'index'])->name('logbook.index');

// ✍️ Authenticated + Verified Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/logbook/create', [UserDiveLogController::class, 'create'])->name('logbook.create');
    Route::post('/logbook', [UserDiveLogController::class, 'store'])->name('logbook.store');
    Route::get('/logbook/chart', [UserDiveLogController::class, 'chart'])->name('logbook.chart');
    Route::get('/logbook/table', [UserDiveLogController::class, 'table'])->name('logbook.table');
    Route::get('/logbook/{log}', [UserDiveLogController::class, 'show'])->name('logbook.show');
    Route::get('/logbook/{log}/edit', [UserDiveLogController::class, 'edit'])->name('logbook.edit');
    Route::put('/logbook/{log}', [UserDiveLogController::class, 'update'])->name('logbook.update');
});

// 🔑 Password Reset
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

// ✅ Email Verification (Custom)
Route::get('/verify-email', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/verify-email/{token}', [VerifyEmailController::class, 'verify'])->name('verify.email');

Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index']);

Route::get('/blog', [BlogPostController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogPostController::class, 'show'])->name('blog.show');

// 🔐 Auth Routes (Login/Register/etc.)
require __DIR__.'/auth.php';