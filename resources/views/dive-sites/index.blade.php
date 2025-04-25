@extends('layouts.vizzbud')

@section('content')
<div class="relative h-[calc(100vh-64px)] w-full" x-data="diveSiteMap({ sites: @js($sites) })">
    {{-- Search and Controls --}}
    <div class="absolute top-4 left-4 z-20 space-y-2 w-[364px]">
        {{-- Search Bar --}}
        <div class="relative z-40 mb-2" x-data="siteSearch()">
            <input type="text"
                   x-model="query"
                   @focus="open = true"
                   @click.away="open = false"
                   @keydown.arrow-down.prevent="move(1)"
                   @keydown.arrow-up.prevent="move(-1)"
                   @keydown.enter.prevent="select(focusedIndex)"
                   placeholder="Search dive sites..."
                   class="w-full rounded-full p-2 pr-10 text-black shadow z-30 relative">

            <button x-show="query.length" type="button" @click="query=''; selectedId=null; open=false; const m=Alpine.$data(document.querySelector('[x-data^=diveSiteMap]')); if(m) m.selectedSite=null;" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-black text-2xl z-40">×</button>

            <input type="hidden" name="dive_site_id" :value="selectedId">

            <ul x-show="open && filtered.length"
                class="absolute z-30 bg-white text-black rounded shadow w-full mt-1 max-h-60 overflow-y-auto border border-gray-300"
                x-transition>
                <template x-for="(site, index) in filtered" :key="site.id">
                    <li :class="{
                            'bg-cyan-100': index === focusedIndex,
                            'px-4 py-2 cursor-pointer': true
                        }"
                        @click="select(index)"
                        @mouseover="focusedIndex = index"
                        x-text="site.name"
                    ></li>
                </template>
            </ul>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded p-2 shadow space-y-2 text-sm relative z-30 w-[364px]">
            <select x-model="filterLevel" @change="$dispatch('filter-changed')" class="w-full border border-slate-500 rounded p-2 bg-white text-slate-800 font-semibold shadow-sm">
                <option value="">All Levels</option>
                <option value="Open Water">Open Water</option>
                <option value="Advanced">Advanced</option>
                <option value="Deep">Deep</option>
            </select>

            <select x-model="filterType" @change="$dispatch('filter-changed')" class="w-full border border-slate-500 rounded p-2 bg-white text-slate-800 font-semibold shadow-sm">
                <option value="">All Types</option>
                <option value="shore">Shore</option>
                <option value="boat">Boat</option>
            </select>

            <button @click="centerMap" class="w-full bg-cyan-500 hover:bg-cyan-600 text-white rounded px-2 py-1">🎯 Center Map</button>
        </div>

        {{-- Info Panel --}}
        <div :class="selectedSite ? 'translate-x-0' : '-translate-x-full'"
             class="fixed top-[57px] left-0 bottom-0 max-w-[400px] w-full bg-white shadow-xl z-10 overflow-y-auto px-6 text-slate-800 transition-transform transform pt-[160px]">
            <div class="mt-12">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-cyan-600" x-text="selectedSite.name"></h2>
                    <p class="text-sm mt-1" x-text="selectedSite.description"></p>
                </div>
                <ul class="space-y-1 text-sm">
                    <li>📏 <strong>Depth:</strong> <span x-text="`${selectedSite.avg_depth}m avg / ${selectedSite.max_depth}m max`"></span></li>
                    <li>🚶 <strong>Entry:</strong> <span x-text="selectedSite.dive_type ?? '—'"></span></li>
                    <li>🎓 <strong>Level:</strong> <span x-text="selectedSite.suitability ?? '—'"></span></li>
                    <li>🌡️ <strong>Water Temp:</strong> <span x-text="selectedSite.conditions?.waterTemperature?.noaa ?? '—'"></span> °C</li>
                    <li>🌊 <strong>Wave:</strong> <span x-text="selectedSite.conditions?.waveHeight?.noaa ?? '—'"></span> m @
                        <span x-text="selectedSite.conditions?.wavePeriod?.noaa ?? '—'"></span> s</li>
                    <li>🧭 <strong>Direction:</strong> <span x-text="compass(selectedSite.conditions?.waveDirection?.noaa)"></span></li>
                    <li>🌬️ <strong>Wind:</strong> <span x-text="formatWind(selectedSite.conditions?.windSpeed?.noaa, selectedSite.conditions?.windDirection?.noaa)"></span></li>
                    <li>📅 <strong>Updated:</strong> <span x-text="formatDate(selectedSite.retrieved_at)"></span></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Map --}}
    <div id="map" class="w-full h-full"></div>
</div>
@endsection

@push('head')
<script src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>
@endpush

@push('scripts')
<script>
mapboxgl.accessToken = '{{ env('MAPBOX_TOKEN') }}';

function diveSiteMap({ sites }) {
    return {
        map: null,
        sites,
        selectedSite: null,
        userLat: null,
        userLng: null,
        filterLevel: '',
        filterType: '',

        init() {
            this.map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [151.2153, -33.8568],
                zoom: 10
            });

            this.map.on('load', () => {
                this.renderSites();
            });

            this.map.on('click', () => {
                this.selectedSite = null;
            });

            navigator.geolocation.getCurrentPosition(position => {
                this.userLat = position.coords.latitude;
                this.userLng = position.coords.longitude;
            });

            this.$watch('filterLevel', () => this.renderSites());
            this.$watch('filterType', () => this.renderSites());
        },

        get filteredSites() {
            return this.sites.filter(site =>
                (this.filterLevel === '' || site.suitability === this.filterLevel) &&
                (this.filterType === '' || site.dive_type === this.filterType)
            );
        },

        renderSites() {
            const features = this.filteredSites.map(site => ({
                type: 'Feature',
                geometry: {
                    type: 'Point',
                    coordinates: [site.lng, site.lat]
                },
                properties: {
                    id: site.id,
                    color: this.getConditionColor(site.conditions?.waveHeight?.noaa)
                }
            }));

            if (this.map.getSource('dive-sites')) {
                this.map.getSource('dive-sites').setData({
                    type: 'FeatureCollection',
                    features: features
                });
            } else {
                this.map.addSource('dive-sites', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: features
                    }
                });

                this.map.addLayer({
                    id: 'site-layer',
                    type: 'circle',
                    source: 'dive-sites',
                    paint: {
                        'circle-radius': 8,
                        'circle-color': ['get', 'color'],
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#ffffff'
                    }
                });

                this.map.on('click', 'site-layer', (e) => {
                    const id = e.features[0].properties.id;
                    this.selectedSite = this.sites.find(site => site.id == id);
                    const site = this.selectedSite;
                    if (site) {
                        this.map.flyTo({ center: [site.lng, site.lat], zoom: 12 });
                    }

                    const searchRoot = document.querySelector('[x-data^="siteSearch"]');
                    const searchComponent = Alpine && Alpine.$data(searchRoot);
                    if (searchComponent) {
                        searchComponent.setQuery(this.selectedSite.name);
                    }
                });
            }
        },

        getConditionColor(waveHeight) {
            if (waveHeight < 1) return '#00ff88';
            if (waveHeight < 2) return '#ffcc00';
            return '#ff4444';
        },

        centerMap() {
            if (this.userLat && this.userLng) {
                this.map.flyTo({ center: [this.userLng, this.userLat], zoom: 11 });
            }
        },

        compass(deg) {
            if (!deg) return '—';
            const directions = ['N','NE','E','SE','S','SW','W','NW'];
            return directions[Math.round(deg / 45) % 8];
        },

        formatDate(utc) {
            if (!utc) return '—';
            const d = new Date(utc + 'Z');
            return d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
        },

        formatWind(speed, dir) {
            if (!speed) return '—';
            return (speed * 1.94384).toFixed(1) + ' kn from ' + this.compass(dir);
        }
    }
}

function siteSearch() {
    return {
        sites: @json($sites),
        query: '',
        open: false,
        selectedId: null,
        focusedIndex: 0,

        get filtered() {
            const q = this.query.toLowerCase();
            return this.sites.filter(site => site.name.toLowerCase().includes(q));
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
                this.open = false;

                const root = document.querySelector('[x-data^="diveSiteMap"]');
                const mapComponent = Alpine && Alpine.$data(root);

                if (mapComponent) {
                    mapComponent.selectedSite = site;
                    mapComponent.map.flyTo({ center: [site.lng, site.lat], zoom: 12 });

                    if (mapComponent.map.getSource('dive-sites')) {
                        mapComponent.renderSites();
                    }
                }
            }
        },

        setQuery(name) {
            this.query = name;
        }
    };
}
</script>
@endpush