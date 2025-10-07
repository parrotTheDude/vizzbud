<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiveSiteController;
use App\Http\Controllers\ConditionReportController;
use App\Http\Controllers\UserDiveLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\AdminMiddleware;
use App\Models\DiveSite;
use Spatie\Sitemap\SitemapGenerator;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Home
Route::get('/', function () {
    $featured = DiveSite::whereHas('latestCondition')
        ->with('latestCondition')
        ->inRandomOrder()
        ->first();

    return view('home', [
        'featured'     => $featured,
        'sites'        => DiveSite::all(),
        'siteOptions'  => DiveSite::select('id','name')->orderBy('name')->get(),
    ]);
})->name('home');

// Dive Sites
Route::prefix('dive-sites')->name('dive-sites.')->group(function () {
    Route::get('/', [DiveSiteController::class, 'index'])->name('index');
    Route::get('/{diveSite}', [DiveSiteController::class, 'show'])->name('show');
});

// Dive Log (public view only)
Route::get('/logbook', [UserDiveLogController::class, 'index'])->name('logbook.index');

// Blog
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogPostController::class, 'index'])->name('index');
    Route::get('/{slug}', [BlogPostController::class, 'show'])->name('show');
});

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// 🔑 Password Reset
Route::prefix('forgot-password')->name('password.')->group(function () {
    Route::get('/', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('request');
    Route::post('/', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('email');
});

// Email Verification
Route::middleware('auth')->group(function () {
    Route::view('/verify-email', 'auth.verify-email')->name('verification.notice');
    Route::get('/verify-email/{token}', [VerifyEmailController::class, 'verify'])->name('verify.email');
});

Route::view('/how-it-works', 'pages.how-vizzbud-works')->name('how_it_works');

/*
|--------------------------------------------------------------------------
| Authenticated + Verified Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('logbook')->name('logbook.')->group(function () {
    Route::get('/create', [UserDiveLogController::class, 'create'])->name('create');
    Route::post('/', [UserDiveLogController::class, 'store'])->name('store');
    Route::get('/chart', [UserDiveLogController::class, 'chart'])->name('chart');
    Route::get('/table', [UserDiveLogController::class, 'table'])->name('table');
    Route::get('/{log}', [UserDiveLogController::class, 'show'])->name('show');
    Route::get('/{log}/edit', [UserDiveLogController::class, 'edit'])->name('edit');
    Route::put('/{log}', [UserDiveLogController::class, 'update'])->name('update');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');

    // Blog management
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
});

Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');

    SitemapGenerator::create(config('app.url'))
        ->writeToFile($path);

    return response()->file($path, [
        'Content-Type' => 'application/xml',
    ]);
});

require __DIR__.'/auth.php';