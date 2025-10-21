<?php

namespace App\Http\Controllers;

use App\Models\DiveSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        // Cache sitemap content for 6 hours
        $xml = Cache::remember('sitemap.xml', now()->addHours(6), function () {
            $diveSites = DiveSite::select(['slug', 'updated_at'])
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->get();

            $base = rtrim(config('app.url'), '/');

            $urls = $diveSites->map(function ($site) use ($base) {
                return [
                    'loc'        => "{$base}/dive-sites/{$site->slug}",
                    'lastmod'    => optional($site->updated_at)->toAtomString(),
                    'changefreq' => 'daily',
                    'priority'   => '0.8',
                ];
            });

            // Add your main static pages here
            $static = collect([
                [
                    'loc'        => "{$base}/",
                    'lastmod'    => now()->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority'   => '1.0',
                ],
                [
                    'loc'        => "{$base}/about",
                    'lastmod'    => now()->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority'   => '0.5',
                ],
                [
                    'loc'        => "{$base}/how-it-works",
                    'lastmod'    => now()->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority'   => '0.6',
                ],
            ]);

            $urls = $static->merge($urls);

            return view('sitemap', ['urls' => $urls])->render();
        });

        // Gzip compression (optional but nice for SEO bots)
        $content = gzencode($xml, 9);

        return Response::make($content, 200, [
            'Content-Type'     => 'application/xml; charset=UTF-8',
            'Content-Encoding' => 'gzip',
            'Cache-Control'    => 'public, max-age=21600', // 6 hours
        ]);
    }
}