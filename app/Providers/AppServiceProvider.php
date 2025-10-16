<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
    public function boot()
    {
        Schema::defaultStringLength(191);
        date_default_timezone_set(config('app.timezone'));

        if (!function_exists('log_activity')) {
            function log_activity(string $action, $model = null, array $meta = []): void {
                \App\Helpers\ActivityHelper::log($action, $model, $meta);
            }
        }
    }
}