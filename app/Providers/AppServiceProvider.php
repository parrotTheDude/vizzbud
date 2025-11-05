<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Illuminate\Support\Facades\Auth;
use App\Auth\EncryptedUserProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $helpers = app_path('Helpers/global_helpers.php');
        if (file_exists($helpers)) {
            require_once $helpers;
        }

        $this->app->singleton(MarkdownConverter::class, function () {
            $environment = new Environment();
            $environment->addExtension(new CommonMarkCoreExtension());

            return new MarkdownConverter($environment);
        });

        $this->app->singleton(ImageManager::class, function () {
            return new ImageManager(new GdDriver()); 
        });

        $this->app->alias(ImageManager::class, 'image'); // optional
    }

    /**
     * Bootstrap any application services.
    */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        date_default_timezone_set(config('app.timezone'));

        Auth::provider('encrypted', function ($app, array $config) {
            $model = $config['model'] ?? null;
            return new EncryptedUserProvider($app['hash'], $model);
        });
    }
}