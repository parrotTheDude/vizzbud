@extends('layouts.vizzbud')

@section('content')
<div class="fixed top-[64px] left-0 right-0 bottom-0 w-full h-[calc(100vh-64px)] overflow-hidden bg-white z-10" x-data="diveSiteMap({ sites: @js($sites) })">
    {{-- Search and Controls --}}
    <div class="absolute top-4 left-1/2 transform -translate-x-1/2 z-20 space-y-2 w-full px-4 sm:left-1 sm:transform-none sm:max-w-[428px] sm:w-auto">
        <div class="flex items-center gap-2">
        {{-- Temporarily output this to test --}}
        <p>Mapbox token is: {{ env('MAPBOX_TOKEN') }}</p>
            {{-- Search Bar --}}
            <div class="relative z-40 mb-2 w-full sm:w-[364px]" x-data="siteSearch()">
            <input
                type="text"
                x-model="query"
                @focus="open = true"
                @click.away="open = false"
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.enter.prevent="select(focusedIndex)"
                placeholder="Search dive sites..."
                class="w-full rounded-full px-5 py-3 pr-12 text-black bg-slate-100 z-30 relative"
            />

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

            <button
                @click="showFilters = !showFilters"
                class="bg-cyan-500 hover:bg-cyan-600 text-white rounded-full w-10 h-10 shadow-sm flex items-center justify-center relative -top-1 z-40"
            >
                <img src="/icons/filter.svg" alt="Filter" class="w-6 h-6 invert">
            </button>
        </div>

        <div x-show="showFilters" x-transition>
            {{-- Filters --}}
            <div class="bg-white rounded p-2 shadow space-y-2 text-sm relative z-30 w-full sm:w-[395px]">
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
        </div>
    </div>

    {{-- Map --}}
    <div id="map" class="w-full h-full"></div>

    {{-- Info Sidebar for Desktop --}}
    <div 
        x-show="!isMobileView"
        :class="selectedSite && !isMobileView ? 'translate-x-0' : '-translate-x-full'"
        class="absolute top-0 left-0 h-full max-w-[430px] w-full bg-white shadow-xl z-10 overflow-y-auto px-6 text-slate-800 transition-transform transform pt-[4.5rem]">
        @include('dive-sites.partials.info')
    </div>

    {{-- Info Bottom Sheet for Mobile --}}
    <div 
        x-show="isMobileView"
        id="mobileInfoPanel"
        :class="selectedSite && isMobileView ? 'translate-y-0' : 'translate-y-full'"
        class="fixed bottom-0 left-0 right-0 h-[60vh] bg-white shadow-xl z-20 overflow-y-auto px-6 text-slate-800 transition-transform transform pt-6 rounded-t-2xl"
    >
        @include('dive-sites.partials.info')
    </div>
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
        showFilters: false,
        isMobileView: window.innerWidth < 640,
        dragStartY: 0,
        dragging: false,

        init() {
            this.map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [151.2153, -33.8568],
                zoom: 10
            });

            this.map.on('load', () => {
                // Add source for dive sites
                this.map.addSource('dive-sites', {
                    type: 'geojson',
                    data: { type: 'FeatureCollection', features: [] }
                });

                // Add circle layer for dive sites
                this.map.addLayer({
                    id: 'site-layer',
                    type: 'circle',
                    source: 'dive-sites',
                    paint: {
                        'circle-radius': [
                            'case',
                            ['boolean', ['get', 'selected'], false],
                            14,
                            8
                        ],
                        'circle-color': ['get', 'color'],
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#ffffff'
                    }
                });

                // Click handler for selecting a site
                this.map.on('click', 'site-layer', (e) => {
                    const id = e.features[0].properties.id;
                    this.selectedSite = this.sites.find(site => site.id == id);
                    this.showFilters = false;

                    const site = this.selectedSite;
                    if (site) {
                        const offset = this.isMobileView ? [0, -window.innerHeight * 0.3] : [0, 0];
                        this.map.easeTo({ center: [site.lng, site.lat], zoom: 12, offset });
                    }

                    this.renderSites();

                    const searchRoot = document.querySelector('[x-data^="siteSearch"]');
                    const searchComponent = Alpine && Alpine.$data(searchRoot);
                    if (searchComponent) {
                        searchComponent.setQuery(this.selectedSite.name);
                    }
                });

                // Initial render
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

            window.addEventListener('resize', () => {
                this.isMobileView = window.innerWidth < 640;
            });

            this.$watch('selectedSite', site => {
                this.renderSites();
                if (site) {
                    const offset = this.isMobileView ? [0, -window.innerHeight * 0.3] : [0, 0];

                    this.map.easeTo({
                        center: [site.lng, site.lat],
                        zoom: 12,
                        offset
                    });
                }
                setTimeout(() => {
                    const chartEl = document.getElementById('forecastChart');
                    if (!site || !site.forecast || !site.forecast.length || !chartEl) return;

                    if (window.forecastChart) {
                        window.forecastChart.destroy();
                    }

                    const ctx = chartEl.getContext('2d');
                    const labels = site.forecast.map(f =>
                        new Date(f.time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    );
                    const waveData = site.forecast.map(f => f.waveHeight?.noaa ?? null);
                    const directionData = site.forecast.map(f => f.waveDirection?.noaa ?? null);

                    window.forecastChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Wave Height (m)',
                                data: waveData,
                                borderColor: '#0ea5e9',
                                backgroundColor: 'rgba(14,165,233,0.15)',
                                borderWidth: 2,
                                tension: 0.3,
                                pointRadius: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    title: { display: true, text: 'Time' },
                                    ticks: { maxTicksLimit: 12 }
                                },
                                y: {
                                    title: { display: true, text: 'Height (m)' },
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        afterBody: function(context) {
                                            const i = context[0].dataIndex;
                                            const dir = directionData[i];
                                            return `Wave Direction: ${dir}°`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }, 50); // Delay to ensure Alpine renders DOM first
            });
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
                    color: this.getConditionColor(site.conditions?.waveHeight?.noaa),
                    selected: this.selectedSite && this.selectedSite.id === site.id
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
                        'circle-radius': [
                            'case',
                            ['boolean', ['get', 'selected'], false],
                            14,
                            8
                        ],
                        'circle-color': ['get', 'color'],
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#ffffff'
                    }
                });

                this.map.addLayer({
                    id: 'site-symbols',
                    type: 'symbol',
                    source: 'dive-sites',
                    layout: {
                        'icon-image': 'diving-icon',
                        'icon-size': 0.05, // Adjust to suit your icon size
                        'icon-allow-overlap': true
                    }
                });

                this.map.on('click', 'site-layer', (e) => {
                    const id = e.features[0].properties.id;
                    this.selectedSite = this.sites.find(site => site.id == id);
                    this.showFilters = false;
                    const site = this.selectedSite;
                    if (site) {
                        this.map.flyTo({ center: [site.lng, site.lat], zoom: 12 });
                    }

                    this.renderSites();

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