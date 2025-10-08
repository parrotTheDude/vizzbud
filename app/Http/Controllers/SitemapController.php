<?php

namespace App\Http\Controllers;

use App\Models\DiveSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class SitemapController extends Controller
{
    public function index()
    {
        // Cache sitemap for 6 hours
        $xml = Cache::remember('sitemap.xml', now()->addHours(6), function () {
            $diveSites = DiveSite::select(['slug', 'updated_at'])->orderBy('updated_at', 'desc')->get();

            $base = config('app.url');

            $urls = $diveSites->map(function ($site) use ($base) {
                return [
                    'loc' => "{$base}/dive-site/{$site->slug}",
                    'lastmod' => optional($site->updated_at)->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ];
            });

            // Include static pages too
            $urls->prepend([
                'loc' => $base . '/',
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ]);

            return view('sitemap', ['urls' => $urls])->render();
        });

        // Optional: gzip compression (saves bandwidth)
        $content = gzencode($xml, 9);

        return Response::make($content, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Encoding' => 'gzip',
            'Cache-Control' => 'public, max-age=21600', // 6 hours
        ]);
    }
}