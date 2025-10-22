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
     * Search API endpoint
     */
    public function search(Request $request)
    {
        $query = trim($request->input('query', ''));
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $worldwide = filter_var($request->input('worldwide'), FILTER_VALIDATE_BOOLEAN);
        $radiusKm = $request->input('radius', 200);

        if (!$query && !$lat && !$lng) {
            return response()->json([
                'results' => [],
                'country' => null,
                'message' => 'No query or location provided.',
            ]);
        }

        $userCountry = $request->input('country') ?: $this->getCountryFromCoords($lat, $lng);

        // --- 1️⃣ Local search attempt ---
        $sites = DiveSite::query()->with('region.state.country');

        if ($query) {
            $sites->where(function ($q) use ($query) {
                $q->whereRaw('SOUNDEX(name) = SOUNDEX(?)', [$query])
                ->orWhere('name', 'like', "%{$query}%")
                // Search by region/state/country names via relationships
                ->orWhereHas('region', fn($r) => $r->where('name', 'like', "%{$query}%"))
                ->orWhereHas('region.state', fn($s) => $s->where('name', 'like', "%{$query}%"))
                ->orWhereHas('region.state.country', fn($c) => $c->where('name', 'like', "%{$query}%"));
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
            $sites->whereHas('region.state.country', fn($q) =>
                $q->where('name', 'like', "%{$userCountry}%")
            );
        }

        $results = $sites->limit(50)->get();

        // --- 2️⃣ Expanded / worldwide fallback ---
        if ($results->isEmpty() && !$worldwide) {
            if ($lat && $lng) {
                foreach ([500, 1000] as $expandedRadius) {
                    $expanded = DiveSite::query()
                        ->with('region.state.country')
                        ->when($query, function ($q) use ($query) {
                            $q->whereRaw('SOUNDEX(name) = SOUNDEX(?)', [$query])
                            ->orWhere('name', 'like', "%{$query}%")
                            ->orWhereHas('region', fn($r) => $r->where('name', 'like', "%{$query}%"))
                            ->orWhereHas('region.state', fn($s) => $s->where('name', 'like', "%{$query}%"))
                            ->orWhereHas('region.state.country', fn($c) => $c->where('name', 'like', "%{$query}%"));
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

            if ($results->isEmpty()) {
                $results = DiveSite::query()
                    ->with('region.state.country')
                    ->when($query, function ($q) use ($query) {
                        $q->whereRaw('SOUNDEX(name) = SOUNDEX(?)', [$query])
                        ->orWhere('name', 'like', "%{$query}%")
                        ->orWhereHas('region', fn($r) => $r->where('name', 'like', "%{$query}%"))
                        ->orWhereHas('region.state', fn($s) => $s->where('name', 'like', "%{$query}%"))
                        ->orWhereHas('region.state.country', fn($c) => $c->where('name', 'like', "%{$query}%"));
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
                'region' => optional($site->region)->name,
                'state' => optional($site->region?->state)->abbreviation ?? optional($site->region?->state)->name,
                'country' => optional($site->region?->state?->country)->name,
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