@extends('layouts.vizzbud')

@section('title', 'Vizzbud | Real-Time Dive Conditions, Logs & Stats')
@section('meta_description', 'Explore live scuba dive site conditions and log your underwater adventures with Vizzbud.')

@section('content')

{{-- 🌊 HERO SECTION --}}
<section class="relative min-h-screen bg-cover bg-center flex items-center justify-center" style="background-image: url('/turtle.webp');">
    {{-- Dark overlay layer for contrast --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-950/80 via-slate-950/60 to-slate-900/80 backdrop-blur-sm"></div>

    <div class="relative z-10 text-center px-6 py-24 sm:py-32 max-w-4xl">
        <h1 class="text-6xl sm:text-7xl font-extrabold text-white leading-tight drop-shadow mb-6">
            Plan. Dive. Log. Explore.
        </h1>
        <p class="text-xl sm:text-2xl text-slate-200 max-w-2xl mx-auto mb-8">
            Track live dive conditions, explore dive sites, and record your underwater adventures with <span class="text-cyan-400 font-semibold">Vizzbud</span>.
        </p>
        <a href="{{ route('dive-sites.index') }}"
        class="inline-flex items-center gap-3 px-5 py-3 rounded-xl text-white text-base font-semibold shadow-lg transition backdrop-blur-md"
        style="background-color: rgba(5, 62, 155, 0.6);">
        
        @include('components.icon', ['name' => 'map'])

        <span>Explore Dive Map</span>
        </a>
    </div>
</section>

@if ($featured)
<section class="relative bg-cover bg-center text-white py-16 px-4 sm:px-6" style="background-image: url('/bg-waves.png');">
    <div class="absolute inset-0 bg-slate-950/70"></div>

    <div class="relative z-10 max-w-5xl mx-auto text-center">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-8">📍 Featured Dive Site</h2>

        <div class="bg-slate-800/80 backdrop-blur-md rounded-2xl overflow-hidden shadow-lg flex flex-col md:grid md:grid-cols-2">
            <div id="featured-map" class="h-64 md:h-full w-full"></div>

            <div class="p-6 sm:p-8 flex flex-col justify-center text-left space-y-4">
                <h3 class="text-2xl font-semibold text-cyan-400">{{ $featured->name }}</h3>
                <p class="text-slate-300 text-sm sm:text-base">{{ $featured->description }}</p>

                <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
                    <!-- 🌊 Wave Height -->
                    <div class="flex items-center space-x-3 bg-slate-700/50 p-3 rounded-xl">
                        <img src="/icons/wave.svg" class="w-5 h-5" alt="Wave Height">
                        <div>
                            <div class="font-semibold text-cyan-400">Wave Height</div>
                            <div class="text-white text-base">
                                @php $wave = $featured->latestCondition->wave_height; @endphp
                                {{ $wave !== null ? $wave . ' m' : 'N/A' }}
                            </div>
                        </div>
                    </div>

                    <!-- 🧭 Wind Speed -->
                    <div class="flex items-center space-x-3 bg-slate-700/50 p-3 rounded-xl">
                        <img src="/icons/wind.svg" class="w-5 h-5" alt="Wind Speed">
                        <div>
                            <div class="font-semibold text-cyan-400">Wind Speed</div>
                            <div class="text-white text-base">
                                @php $wind = $featured->latestCondition->wind_speed; @endphp
                                {{ $wind !== null ? number_format($wind * 1.94384, 1) . ' kn' : 'N/A' }}
                            </div>
                        </div>
                    </div>

                    <!-- 🌡️ Water Temp -->
                    <div class="flex items-center space-x-3 bg-slate-700/50 p-3 rounded-xl">
                        <img src="/icons/temperature.svg" class="w-5 h-5" alt="Water Temp">
                        <div>
                            <div class="font-semibold text-cyan-400">Water Temp</div>
                            <div class="text-white text-base">
                                @php $temp = $featured->latestCondition->water_temperature; @endphp
                                {{ $temp !== null ? $temp . '°C' : 'N/A' }}
                            </div>
                        </div>
                    </div>

                    <!-- 📏 Avg Depth -->
                    <div class="flex items-center space-x-3 bg-slate-700/50 p-3 rounded-xl">
                        <img src="/icons/pool-depth.svg" class="w-5 h-5" alt="Avg Depth">
                        <div>
                            <div class="font-semibold text-cyan-400">Avg Depth</div>
                            <div class="text-white text-base">
                                {{ $featured->avg_depth ?? '—' }}m
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('dive-sites.index') }}" class="mt-4 inline-block text-cyan-400 hover:underline text-sm">
                    → Explore All Dive Sites
                </a>
            </div>
        </div>
    </div>
</section>
@endif

@endsection

@if ($featured && $featured->lat && $featured->lng)
@push('scripts')
<script>
window.addEventListener('load', function () {
    mapboxgl.accessToken = @json(config('services.mapbox.token'));

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
                waveHeight: {{ $featured->latestCondition->wave_height ?? 'null' }},
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

function reportForm() {
    return {
        openReport: false,
        submitting: false,
        sites: @json($siteOptions),
        query: '',
        selectedId: null,
        focusedIndex: 0,
        open: false,
        status: null,
        error: false,

        get filtered() {
            return this.sites.filter(site =>
                site.name.toLowerCase().includes(this.query.toLowerCase())
            );
        },

        move(direction) {
            if (!this.filtered.length) return;
            this.focusedIndex = (this.focusedIndex + direction + this.filtered.length) % this.filtered.length;
        },

        select(index) {
            const site = this.filtered[index];
            if (site) {
                this.query = site.name;
                this.selectedId = site.id;

                // Slight delay before closing to allow smoother UX
                setTimeout(() => {
                    this.open = false;
                }, 150);
            }
        },

        submitReport() {
            if (this.submitting) return;

            this.submitting = true;
            const formData = new FormData();
            formData.append('dive_site_id', this.selectedId);
            formData.append('viz_rating', this.$el.querySelector('[name="viz_rating"]').value);
            formData.append('reported_at', this.$el.querySelector('[name="reported_at"]').value);

            fetch('{{ route('report.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to submit');
                return response.json();
            })
            .then(() => {
                this.status = '✅ Report submitted successfully!';
                this.error = false;
                this.query = '';
                this.selectedId = null;
                this.$el.querySelector('[name="viz_rating"]').value = '';
                this.openReport = false;
            })
            .catch(() => {
                this.status = '❌ Failed to submit report. Please try again.';
                this.error = true;
            });
            setTimeout(() => {
                this.submitting = false;
            }, 3000);
        }
    };
}
</script>
@endpush
@endif