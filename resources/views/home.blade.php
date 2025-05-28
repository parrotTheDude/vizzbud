@extends('layouts.vizzbud')

@section('title', 'Vizzbud | Real-Time Dive Conditions, Logs & Stats')
@section('meta_description', 'Explore live scuba dive site conditions and log your underwater adventures with Vizzbud.')

@section('content')

@if (!$featured)
    <div class="p-6 text-center text-red-500 font-bold">
        No featured site available. Please check if dive sites have conditions.
    </div>
@endif

@if ($featured)
<section class="relative bg-cover bg-center text-white min-h-screen flex items-center justify-center" style="background-image: url('/bg-waves.png');">
    <div class="absolute inset-0 bg-slate-950/70"></div>

    <div class="relative z-10 max-w-5xl w-full px-4 sm:px-6 text-center">
        <h2 class="text-4xl font-bold text-white mb-6">📍 Featured Dive Site</h2>

        <div class="bg-slate-800 rounded-2xl overflow-hidden shadow-lg flex flex-col md:grid md:grid-cols-2 mb-10">
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

        {{-- Quick Report Button + Form --}}
        <div x-data="reportForm()" class="text-center">
        <button @click="openReport = !openReport"
            class="bg-cyan-600 hover:bg-cyan-700 px-6 py-3 rounded-xl font-semibold text-white transition w-full sm:w-auto shadow">
            📝 <span x-text="openReport ? 'Close Report Form' : 'Submit a Quick Dive Report'"></span>
        </button>

            {{-- Show status outside of the form --}}
            <div x-show="status" class="text-sm mb-4 text-center" 
                :class="error ? 'text-red-400' : 'text-green-400'" 
                x-text="status"></div>

            <div x-show="openReport" x-transition class="bg-slate-900 rounded-xl p-6 mt-4 text-left max-w-md mx-auto">
                <form @submit.prevent="submitReport" class="space-y-4">
                    @csrf

                    {{-- Dive Site Search --}}
                    <label class="block relative">
                        <span class="block mb-1 text-sm text-white">Dive Site <span class="text-red-500">*</span></span>
                        <input
                            type="text"
                            x-model="query"
                            @focus="open = true"
                            @click.away="open = false"
                            @keydown.arrow-down.prevent="move(1)"
                            @keydown.arrow-up.prevent="move(-1)"
                            @keydown.enter.prevent="select(focusedIndex)"
                            placeholder="Search dive sites..."
                            class="w-full rounded p-2 text-black"
                        >
                        <input type="hidden" name="dive_site_id" :value="selectedId">
                        <ul x-show="open && filtered.length"
                            class="absolute z-10 bg-white text-black rounded shadow w-full mt-1 max-h-60 overflow-y-auto border border-gray-300"
                            x-transition>
                            <template x-for="(site, index) in filtered" :key="site.id">
                                <li
                                    :class="{
                                        'bg-cyan-100': index === focusedIndex,
                                        'px-4 py-2 cursor-pointer': true
                                    }"
                                    @click="select(index)"
                                    @mouseover="focusedIndex = index"
                                    x-text="site.name"
                                ></li>
                            </template>
                        </ul>
                    </label>

                    {{-- Visibility --}}
                    <input type="number" step="0.1" name="viz_rating"
                        class="w-full rounded p-3 text-black text-base"
                        placeholder="Visibility (m)" required />

                    {{-- Hidden Timestamp --}}
                    <input type="hidden" name="reported_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                    <input type="text" name="website" style="display: none;">

                    <button type="submit"
                        x-bind:disabled="submitting"
                        class="bg-green-500 hover:bg-green-600 px-6 py-3 rounded text-white font-semibold w-full sm:w-auto text-base">
                        ✅ Submit Report
                    </button>
                </form>
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