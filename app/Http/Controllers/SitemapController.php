<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use App\Models\Country;
use App\Models\State;
use App\Models\Region;
use App\Models\DiveSite;
use Carbon\Carbon;

class SitemapController extends Controller
{
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', now()->addHours(6), function () {
            $now = Carbon::now();

            // 🌍 Load all relationships once
            $countries = Country::with(['states.regions'])->get();
            $diveSites = DiveSite::where('is_active', true)
                ->with(['region.state.country'])
                ->select(['id', 'slug', 'region_id', 'updated_at'])
                ->get();

            // 🧭 Dive site directory pages
            $directoryUrls = collect();

            // /dive-sites (main index)
            $directoryUrls->push([
                'loc'        => route('dive-sites.countries', [], true),
                'lastmod'    => $now->toAtomString(),
                'changefreq' => 'weekly',
                'priority'   => '0.9',
            ]);

            // /dive-sites/{country}
            foreach ($countries as $country) {
                $directoryUrls->push([
                    'loc'        => route('dive-sites.country', [$country->slug], true),
                    'lastmod'    => optional($country->updated_at)->toAtomString() ?? $now->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority'   => '0.8',
                ]);

                foreach ($country->states as $state) {
                    // /dive-sites/{country}/{state}
                    $directoryUrls->push([
                        'loc'        => route('dive-sites.state', [$country->slug, $state->slug], true),
                        'lastmod'    => optional($state->updated_at)->toAtomString() ?? $now->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority'   => '0.7',
                    ]);

                    foreach ($state->regions as $region) {
                        // /dive-sites/{country}/{state}/{region}
                        $directoryUrls->push([
                            'loc'        => route('dive-sites.region', [$country->slug, $state->slug, $region->slug], true),
                            'lastmod'    => optional($region->updated_at)->toAtomString() ?? $now->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority'   => '0.6',
                        ]);
                    }
                }
            }

            // 🐠 Individual dive sites
            $siteUrls = $diveSites->map(function (DiveSite $site) {
                $params = $site->getFullRouteParams();
                if (!$params) return null;

                return [
                    'loc'        => route('dive-sites.show', $params, true),
                    'lastmod'    => optional($site->updated_at)->toAtomString(),
                    'changefreq' => 'daily',
                    'priority'   => '0.8',
                ];
            })->filter();

            // 🧱 Static pages
            $static = collect([
                ['loc' => route('home', [], true), 'lastmod' => $now->toAtomString(), 'changefreq' => 'weekly', 'priority' => '1.0'],
                ['loc' => url('/dive-map'), 'lastmod' => $now->toAtomString(), 'changefreq' => 'daily', 'priority' => '0.9'],
                ['loc' => url('/logbook'), 'lastmod' => $now->toAtomString(), 'changefreq' => 'monthly', 'priority' => '0.7'],
                ['loc' => url('/how-it-works'), 'lastmod' => $now->toAtomString(), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ]);

            // 🔗 Combine all URLs
            $urls = $static->merge($directoryUrls)->merge($siteUrls)->values();

            return view('sitemap', ['urls' => $urls])->render();
        });

        // Gzip response
        $content = gzencode($xml, 9);

        return Response::make($content, 200, [
            'Content-Type'     => 'application/xml; charset=UTF-8',
            'Content-Encoding' => 'gzip',
            'Cache-Control'    => 'public, max-age=21600',
        ]);
    }
}