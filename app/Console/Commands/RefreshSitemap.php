<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\SitemapController;

class RefreshSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-sitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears the cached sitemap and regenerates it immediately.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Forget the cache
        Cache::forget('sitemap.xml');

        // Optionally regenerate sitemap immediately
        $controller = new SitemapController();
        $controller->index(); // Triggers regeneration and caching

        $this->info('Sitemap cache cleared and regenerated successfully.');
    }
}