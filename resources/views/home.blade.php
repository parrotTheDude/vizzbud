@extends('layouts.vizzbud')

@section('content')

@if (!$featured)
    <div class="p-6 text-center text-red-500 font-bold">
        No featured site available. Please check if dive sites have conditions.
    </div>
@endif

@if ($featured)
<section class="relative bg-cover bg-center text-white py-20" style="background-image: url('/bg-waves.png');" data-aos="fade-up">
    <div class="absolute inset-0 bg-slate-950/70"></div>

    <div class="relative z-10 max-w-5xl mx-auto px-6 text-center">
        <h2 class="text-4xl font-bold mb-10">📍 Featured Dive Site</h2>

        <div class="bg-slate-800 rounded-2xl overflow-hidden shadow-lg grid md:grid-cols-2 mb-10">
            <div id="featured-map" class="h-64 md:h-full w-full"></div>

            <div class="p-8 flex flex-col justify-center text-left space-y-4">
                <h3 class="text-2xl font-semibold text-cyan-400">{{ $featured->name }}</h3>
                <p class="text-slate-300">{{ $featured->description }}</p>

                <ul class="text-sm space-y-1 text-slate-400">
                    <li>🌡️ <strong class="text-white">Water Temp:</strong> {{ data_get($featured->latestCondition->data, 'hours.0.waterTemperature.noaa', 'N/A') }}°C</li>
                    <li>🌊 <strong class="text-white">Wave Height:</strong> {{ data_get($featured->latestCondition->data, 'hours.0.waveHeight.noaa', 'N/A') }} m</li>
                    <li>🧭 <strong class="text-white">Wind Speed:</strong>
                        @php $wind = data_get($featured->latestCondition->data, 'hours.0.windSpeed.noaa'); @endphp
                        {{ $wind ? number_format($wind * 1.94384, 1) . ' kn' : 'N/A' }}
                    </li>
                    <li>📏 <strong class="text-white">Depth:</strong> {{ $featured->avg_depth }}m avg / {{ $featured->max_depth }}m max</li>
                    <li>🎓 <strong class="text-white">Level:</strong> {{ $featured->suitability }}</li>
                </ul>

                <a href="{{ route('dive-sites.index') }}" class="mt-4 inline-block text-cyan-400 hover:underline text-sm">
                    → Explore All Dive Sites
                </a>
            </div>
        </div>

        <!-- Report Form -->
        <div x-data="{ openReport: false }" class="text-center">
            <button @click="openReport = !openReport"
                class="bg-cyan-500 hover:bg-cyan-600 px-6 py-3 rounded-full font-semibold text-white mb-4 transition">
                📝 <span x-text="openReport ? 'Close Report Form' : 'Submit a Quick Dive Report'"></span>
            </button>

            <div x-show="openReport" x-transition class="bg-slate-900 rounded-xl p-6 mt-4 text-left max-w-2xl mx-auto">
                <form method="POST" action="{{ route('report.store') }}" class="space-y-4">
                    @csrf
                    <select name="dive_site_id" class="w-full rounded p-2 text-black" required>
                        <option value="">Choose a site...</option>
                        @foreach ($sites ?? \App\Models\DiveSite::all() as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.1" name="viz_rating" class="w-full rounded p-2 text-black" placeholder="Visibility (m)" />
                    <textarea name="comment" rows="3" class="w-full rounded p-2 text-black" placeholder="Comments (optional)"></textarea>
                    <input type="datetime-local" name="reported_at" class="w-full rounded p-2 text-black" required value="{{ now()->format('Y-m-d\TH:i') }}">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 px-5 py-2 rounded text-white font-semibold transition">
                        ✅ Submit Report
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
@endif

<!-- Recent Conditions -->
<section class="bg-white py-16" data-aos="fade-up">
    <div class="max-w-6xl mx-auto px-6">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">🧭 Recent Dive Conditions</h3>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach ($recentSites ?? [] as $site)
            <div class="bg-slate-100 rounded-xl shadow p-5">
                <h4 class="text-cyan-700 font-semibold text-lg mb-2">{{ $site->name }}</h4>
                <ul class="text-sm text-gray-700">
                    <li>🌊 Wave: {{ $site->wave_height ?? 'N/A' }} m</li>
                    <li>🌡 Temp: {{ $site->water_temp ?? 'N/A' }} °C</li>
                    <li>🧭 Wind: {{ $site->wind_speed ?? 'N/A' }} kn</li>
                </ul>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA -->
<section class="bg-cyan-600 text-white py-16" data-aos="fade-up">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <h3 class="text-3xl font-bold mb-4">🌊 Dive Deeper with Vizzbud</h3>
        <p class="text-lg mb-6">Help keep dive data fresh. Submit reports, explore sites, and stay ocean-smart.</p>
        <a href="{{ route('report.index') }}" class="bg-white text-cyan-700 px-6 py-3 rounded-full font-semibold hover:bg-gray-100 transition">
            📝 Join VizzBud
        </a>
    </div>
</section>

@endsection
@if ($featured && $featured->lat && $featured->lng)
@push('scripts')
<script>
window.addEventListener('load', function () {
    mapboxgl.accessToken = '{{ env('MAPBOX_TOKEN') }}';

    const map = new mapboxgl.Map({
        container: 'featured-map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [{{ $featured->lng }}, {{ $featured->lat }}],
        zoom: 13,
        interactive: false
    });

    const featuredGeoJSON = {
        type: 'FeatureCollection',
        features: [{
            type: 'Feature',
            properties: {
                waveHeight: {{ data_get($featured->latestCondition->data, 'hours.0.waveHeight.noaa', 'null') }},
            },
            geometry: {
                type: 'Point',
                coordinates: [{{ $featured->lng }}, {{ $featured->lat }}]
            }
        }]
    };

    map.on('load', () => {
        map.addSource('featured-site', {
            type: 'geojson',
            data: featuredGeoJSON
        });

        map.addLayer({
            id: 'featured-pin',
            type: 'circle',
            source: 'featured-site',
            paint: {
                'circle-radius': 10,
                'circle-color': [
                    'case',
                    ['<', ['get', 'waveHeight'], 1], '#00ff88',
                    ['<', ['get', 'waveHeight'], 2], '#ffcc00',
                    '#ff4444'
                ],
                'circle-stroke-width': 2,
                'circle-stroke-color': '#ffffff'
            }
        });
    });
});
</script>
@endpush
@endif