<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use App\Models\DiveSite;

class SitemapController extends Controller
{
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', now()->addHours(6), function () {
            // Eager-load so we can build hierarchical URLs
            $diveSites = DiveSite::query()
                ->where('is_active', true)
                ->with(['region.state.country'])
                ->select(['id','slug','region_id','updated_at'])
                ->orderByDesc('updated_at')
                ->get();

            // Build site URLs from route params
            $siteUrls = $diveSites->map(function (DiveSite $site) {
                $params = $site->getFullRouteParams(); // returns ['country','state','region','diveSite'] or null
                if (!$params) {
                    return null; // skip if relationships incomplete
                }
                return [
                    // absolute URL; third arg 'true' forces absolute
                    'loc'        => route('dive-sites.show', $params, true),
                    'lastmod'    => optional($site->updated_at)->toAtomString(),
                    'changefreq' => 'daily',
                    'priority'   => '0.8',
                ];
            })->filter(); // remove nulls

            // Static pages (absolute URLs)
            $static = collect([
                [
                    'loc'        => route('home', [], true),
                    'lastmod'    => now()->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority'   => '1.0',
                ],
                [
                    'loc'        => url('/about'),
                    'lastmod'    => now()->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority'   => '0.5',
                ],
                [
                    'loc'        => url('/how-it-works'),
                    'lastmod'    => now()->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority'   => '0.6',
                ],
            ]);

            $urls = $static->merge($siteUrls)->values();

            return view('sitemap', ['urls' => $urls])->render();
        });

        // Gzip response (fine for bots)
        $content = gzencode($xml, 9);

        return Response::make($content, 200, [
            'Content-Type'     => 'application/xml; charset=UTF-8',
            'Content-Encoding' => 'gzip',
            'Cache-Control'    => 'public, max-age=21600',
        ]);
    }
}