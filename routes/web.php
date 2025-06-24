<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiveSiteController;
use App\Http\Controllers\ConditionReportController;
use App\Http\Controllers\UserDiveLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\SitemapController;
use App\Models\DiveSite;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// 🌐 Home
Route::get('/', function () {
    $featured = DiveSite::whereHas('latestCondition')
        ->with('latestCondition')
        ->inRandomOrder()
        ->first();

    $sites = DiveSite::all();
    $siteOptions = DiveSite::select('id', 'name')->orderBy('name')->get();

    return view('home', compact('featured', 'sites', 'siteOptions'));
})->name('home');

// 📍 Dive Sites
Route::get('/dive-sites', [DiveSiteController::class, 'index'])->name('dive-sites.index');
Route::get('/dive-sites/{diveSite}', [DiveSiteController::class, 'show'])->name('dive-sites.show');

// 🌊 Condition Reports
Route::prefix('report')->group(function () {
    Route::get('/', [ConditionReportController::class, 'create'])->name('report.create');
    Route::post('/', [ConditionReportController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('report.store');
});
Route::get('/reports', [ConditionReportController::class, 'index'])->name('report.index');

// 📘 Dive Log (Public View)
Route::get('/logbook', [UserDiveLogController::class, 'index'])->name('logbook.index');

// 📚 Blog
Route::get('/blog', [BlogPostController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogPostController::class, 'show'])->name('blog.show');

// 🗺️ Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// 🔑 Password Reset
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

// ✅ Email Verification
Route::get('/verify-email', fn () => view('auth.verify-email'))->middleware('auth')->name('verification.notice');
Route::get('/verify-email/{token}', [VerifyEmailController::class, 'verify'])->name('verify.email');

/*
|--------------------------------------------------------------------------
| Authenticated + Verified Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Dive Log (Private)
    Route::get('/logbook/create', [UserDiveLogController::class, 'create'])->name('logbook.create');
    Route::post('/logbook', [UserDiveLogController::class, 'store'])->name('logbook.store');
    Route::get('/logbook/chart', [UserDiveLogController::class, 'chart'])->name('logbook.chart');
    Route::get('/logbook/table', [UserDiveLogController::class, 'table'])->name('logbook.table');
    Route::get('/logbook/{log}', [UserDiveLogController::class, 'show'])->name('logbook.show');
    Route::get('/logbook/{log}/edit', [UserDiveLogController::class, 'edit'])->name('logbook.edit');
    Route::put('/logbook/{log}', [UserDiveLogController::class, 'update'])->name('logbook.update');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Blog - Admin Only
Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->group(function () {
    Route::get('/admin', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.dashboard');

    // Add these if you want blog management:
    Route::get('/admin/blog', [BlogPostController::class, 'adminIndex'])->name('admin.blog.index');
    Route::get('/admin/blog/create', [BlogPostController::class, 'create'])->name('admin.blog.create');
    Route::post('/admin/blog', [BlogPostController::class, 'store'])->name('admin.blog.store');
    Route::get('/admin/blog/{post}/edit', [BlogPostController::class, 'edit'])->name('admin.blog.edit');
    Route::put('/admin/blog/{post}', [BlogPostController::class, 'update'])->name('admin.blog.update');
    Route::delete('/admin/blog/{post}', [BlogPostController::class, 'destroy'])->name('admin.blog.destroy');
    Route::post('/admin/blog/upload-image', [BlogPostController::class, 'uploadImage'])->name('admin.blog.upload');
});

require __DIR__.'/auth.php';