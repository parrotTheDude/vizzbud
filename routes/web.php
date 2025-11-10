<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DiveSiteController,
    ConditionReportController,
    UserDiveLogController,
    BlogPostController,
    SitemapController,
    AdminController,
    SuggestionController,
    ProfileController,
    DiveDirectoryController,
    Admin\ActivityLogController,
    Admin\DiveSiteSearchController,
    Auth\ForgotPasswordController,
    Auth\VerifyEmailController,
    Auth\EmailVerificationNotificationController
};
use App\Http\Middleware\AdminMiddleware;
use App\Models\DiveSite;
use Spatie\Sitemap\SitemapGenerator;

/*
|--------------------------------------------------------------------------
| 🌐 Public Routes
|--------------------------------------------------------------------------
*/

// 🏠 Home
Route::get('/', function () {
    $featured = DiveSite::whereHas('latestCondition')
        ->with('latestCondition')
        ->inRandomOrder()
        ->first();

    return view('home', [
        'featured'    => $featured,
        'sites'       => DiveSite::all(),
        'siteOptions' => DiveSite::select('id', 'name')->orderBy('name')->get(),
    ]);
})->name('home');

// 🗺️ Dive Map (interactive)
Route::prefix('dive-map')->name('dive-map.')->group(function () {
    Route::get('/', [DiveSiteController::class, 'index'])->name('index');

    // Make region search open to all
    Route::get('/region-search', [DiveSiteController::class, 'regionSearch'])
        ->name('region-search');
});

// 🌍 Dive Site Directory (hierarchical listings + show)
Route::prefix('dive-sites')->name('dive-sites.')->group(function () {
    Route::get('/{country}/{state}/{region}/{diveSite:slug}', [DiveDirectoryController::class, 'show'])->name('show');
    Route::get('/{country}/{state}/{region}', [DiveDirectoryController::class, 'region'])->name('region');
    Route::get('/{country}/{state}', [DiveDirectoryController::class, 'state'])->name('state');
    Route::get('/{country}', [DiveDirectoryController::class, 'country'])->name('country');
    Route::get('/', [DiveDirectoryController::class, 'countries'])->name('countries');
});

// 📘 Dive Log (Public)
Route::get('/logbook', [UserDiveLogController::class, 'index'])->name('logbook.index');

// 📰 Blog
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogPostController::class, 'index'])->name('index');
    Route::get('/{slug}', [BlogPostController::class, 'show'])->name('show');
});

// 🗺 Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// 🔑 Password Reset
Route::prefix('forgot-password')->name('password.')->group(function () {
    Route::get('/', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('request');
    Route::post('/', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('email');
});

// 📧 Email Verification
Route::middleware('auth')->group(function () {
    Route::view('/verify-email', 'auth.verify-email')->name('verification.notice'); // Step 1
    Route::get('/verify-email/{token}', [VerifyEmailController::class, 'verify'])->name('verify.email'); // Step 2
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// 📄 Static Pages
Route::prefix('pages')->group(function () {
    Route::view('/how-it-works', 'pages.how-vizzbud-works')->name('how_it_works');
    Route::view('/privacy', 'pages.privacy')->name('privacy');
    Route::view('/terms', 'pages.terms')->name('terms');
});

// 💬 Suggestions (Public)
Route::post('/suggestions', [SuggestionController::class, 'store'])
    ->middleware('throttle:3,1')
    ->name('suggestions.store');

Route::get('/api/dive-sites/search', [DiveSiteSearchController::class, 'search'])->name('api.dive-sites.search');

Route::get('/api/dive-sites/nearby', [DiveSiteSearchController::class, 'nearby'])
    ->name('api.dive-sites.nearby');

/*
|--------------------------------------------------------------------------
| 🔐 Authenticated + Verified Routes
|--------------------------------------------------------------------------
*/

// ✏️ Dive Log Management
Route::middleware(['auth', 'verified'])
    ->prefix('logbook')
    ->name('logbook.')
    ->group(function () {
        Route::get('/create', [UserDiveLogController::class, 'create'])->name('create');
        Route::post('/', [UserDiveLogController::class, 'store'])->name('store');
        Route::get('/chart', [UserDiveLogController::class, 'chart'])->name('chart');
        Route::get('/table', [UserDiveLogController::class, 'table'])->name('table');
        Route::get('/count', [UserDiveLogController::class, 'countBySiteAndDate'])->name('count');
        Route::get('/{log}', [UserDiveLogController::class, 'show'])->name('show');
        Route::get('/{log}/edit', [UserDiveLogController::class, 'edit'])->name('edit');
        Route::put('/{log}', [UserDiveLogController::class, 'update'])->name('update');
    });

/*
|--------------------------------------------------------------------------
| 👤 User Profile Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('profile')
    ->name('profile.')
    ->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/remove-avatar', [ProfileController::class, 'removeAvatar'])->name('removeAvatar');
    });

/*
|--------------------------------------------------------------------------
| 🛠 Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', AdminMiddleware::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');

        // Blog Management
        Route::prefix('blog')->name('blog.')->group(function () {
            Route::get('/', [BlogPostController::class, 'adminIndex'])->name('index');
            Route::get('/create', [BlogPostController::class, 'create'])->name('create');
            Route::post('/', [BlogPostController::class, 'store'])->name('store');
            Route::get('/{post}/edit', [BlogPostController::class, 'edit'])->name('edit');
            Route::put('/{post}', [BlogPostController::class, 'update'])->name('update');
            Route::delete('/{post}', [BlogPostController::class, 'destroy'])->name('destroy');
            Route::post('/upload-image', [BlogPostController::class, 'uploadImage'])->name('upload');
            Route::patch('/{post}/toggle-publish', [BlogPostController::class, 'togglePublish'])->name('togglePublish');
        });

        // Suggestions Management
        Route::prefix('suggestions')->name('suggestions.')->group(function () {
            Route::get('/', [SuggestionController::class, 'index'])->name('index');
            Route::post('/{suggestion}/reviewed', [SuggestionController::class, 'markReviewed'])->name('markReviewed');
        });

        // Activity Logs
        Route::prefix('activity')->name('activity.')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index'])->name('index');
            Route::get('/export', [ActivityLogController::class, 'export'])->name('export');
            Route::get('/user/{id}', [ActivityLogController::class, 'user'])->name('user');
        });

        // Dive Site Management
        Route::prefix('dive-sites')->name('divesites.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DiveSiteController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\DiveSiteController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\DiveSiteController::class, 'store'])->name('store');
            Route::get('/{diveSite}/edit', [\App\Http\Controllers\Admin\DiveSiteController::class, 'edit'])->name('edit');
            Route::put('/{diveSite}', [\App\Http\Controllers\Admin\DiveSiteController::class, 'update'])->name('update');
            Route::delete('/{diveSite}', [\App\Http\Controllers\Admin\DiveSiteController::class, 'destroy'])->name('destroy');
        });
    });

/*
|--------------------------------------------------------------------------
| 🧭 Sitemap Generator
|--------------------------------------------------------------------------
*/

Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index'])
    ->name('sitemap');

require __DIR__.'/auth.php';