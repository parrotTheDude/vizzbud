<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiveSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class DiveSiteSearchController extends Controller
{
    /**
     * Test page for search
     */
    public function index()
    {
        return view('admin.dive-sites.search-test');
    }

    /**
     * Search API endpoint
     */
    public function search(Request $request)
{
    $query = trim($request->input('query', ''));
    $lat = $request->input('lat');
    $lng = $request->input('lng');
    $worldwide = filter_var($request->input('worldwide'), FILTER_VALIDATE_BOOLEAN);
    $radiusKm = $request->input('radius', 200);

    // 🧩 Early exit guard
    if (!$query && !$lat && !$lng) {
        return response()->json([
            'results' => [],
            'country' => null,
            'message' => 'No query or location provided.',
        ]);
    }

    // 🌏 Detect user country (if coords provided)
    $userCountry = $request->input('country') ?: $this->getCountryFromCoords($lat, $lng);

    // --- 1️⃣ Local search attempt ---
    $sites = DiveSite::query();

    if ($query) {
        $sites->where(function ($q) use ($query) {
            $q->whereRaw('SOUNDEX(name) = SOUNDEX(?)', [$query])
              ->orWhere('name', 'like', "%{$query}%")
              ->orWhere('region', 'like', "%{$query}%")
              ->orWhere('country', 'like', "%{$query}%");
        });
    }

    if (!$worldwide && $lat && $lng) {
        $sites->selectRaw("
            dive_sites.*,
            (6371 * acos(
                cos(radians(?)) * cos(radians(lat)) *
                cos(radians(lng) - radians(?)) +
                sin(radians(?)) * sin(radians(lat))
            )) AS distance_km
        ", [$lat, $lng, $lat])
        ->having('distance_km', '<=', $radiusKm)
        ->orderBy('distance_km');
    }

    if (!$worldwide && $userCountry) {
        $sites->where('country', 'like', "%{$userCountry}%");
    }

    $results = $sites->limit(50)->get();

    // --- 2️⃣ If no results locally, retry with expanded search ---
    if ($results->isEmpty() && !$worldwide) {

        // First try expanding the radius a few times
        if ($lat && $lng) {
            foreach ([500, 1000] as $expandedRadius) {
                $expanded = DiveSite::query()
                    ->when($query, function ($q) use ($query) {
                        $q->whereRaw('SOUNDEX(name) = SOUNDEX(?)', [$query])
                          ->orWhere('name', 'like', "%{$query}%")
                          ->orWhere('region', 'like', "%{$query}%")
                          ->orWhere('country', 'like', "%{$query}%");
                    })
                    ->selectRaw("
                        dive_sites.*,
                        (6371 * acos(
                            cos(radians(?)) * cos(radians(lat)) *
                            cos(radians(lng) - radians(?)) +
                            sin(radians(?)) * sin(radians(lat))
                        )) AS distance_km
                    ", [$lat, $lng, $lat])
                    ->having('distance_km', '<=', $expandedRadius)
                    ->orderBy('distance_km')
                    ->limit(50)
                    ->get();

                if ($expanded->isNotEmpty()) {
                    $results = $expanded;
                    break;
                }
            }
        }

        // If still empty, fallback to worldwide
        if ($results->isEmpty()) {
            $results = DiveSite::query()
                ->when($query, function ($q) use ($query) {
                    $q->whereRaw('SOUNDEX(name) = SOUNDEX(?)', [$query])
                      ->orWhere('name', 'like', "%{$query}%")
                      ->orWhere('region', 'like', "%{$query}%")
                      ->orWhere('country', 'like', "%{$query}%");
                })
                ->limit(50)
                ->get();
        }
    }

    // --- 3️⃣ Normalize for JSON output ---
    $clean = $results->map(function ($site) {
        return [
            'id' => $site->id,
            'name' => trim($site->name),
            'region' => $site->region,
            'country' => $site->country,
            'lat' => $site->lat,
            'lng' => $site->lng,
            'distance_km' => isset($site->distance_km)
                ? round($site->distance_km, 1)
                : null,
        ];
    });

    return response()->json([
        'results' => $clean,
        'country' => $userCountry ?? null,
    ]);
}

    /**
     * 🗺 Reverse-geocode to find user’s country (cached)
     */
    private function getCountryFromCoords($lat, $lng)
    {
        if (!$lat || !$lng) {
            return null;
        }

        $cacheKey = 'country_from_coords_' . round($lat, 2) . '_' . round($lng, 2);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lng) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'VizzbudDiveSiteSearch/1.0 (contact@vizzbud.com)',
                ])->timeout(5)->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                    'zoom' => 5,
                    'addressdetails' => 1,
                ]);

                $data = $response->json();
                $country = $data['address']['country'] ?? null;
                $countryCode = strtoupper($data['address']['country_code'] ?? '');

                if (!$country && $countryCode) {
                    // Fallback conversion (AU → Australia)
                    $map = [
                        'AU' => 'Australia',
                        'NZ' => 'New Zealand',
                        'ID' => 'Indonesia',
                        'TH' => 'Thailand',
                        'PH' => 'Philippines',
                    ];
                    $country = $map[$countryCode] ?? $countryCode;
                }

                return $country;
            } catch (\Throwable $e) {
                \Log::warning('Reverse geocode failed: ' . $e->getMessage());
                return null;
            }
        });
    }
}