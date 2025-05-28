@extends('layouts.vizzbud')

@section('title', 'Dive Site Map | Vizzbud')
@section('meta_description', 'Explore live scuba dive site conditions on an interactive map. Filter by dive level, type, and get wave, wind, and tide data for each location.')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<img id="arrow-icon" src="/icons/right-arrow.svg" style="display:none;" />
<div class="fixed top-[64px] left-0 right-0 bottom-0 w-full h-[calc(100vh-64px)] overflow-hidden bg-white z-10" x-data="diveSiteMap({ sites: @js($sites) })">
    {{-- Search and Controls --}}
    <div class="absolute top-4 left-1/2 transform -translate-x-1/2 z-20 space-y-2 w-full px-4 sm:left-1 sm:transform-none sm:max-w-[428px] sm:w-auto">
        <div class="flex items-center gap-2">
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
mapboxgl.accessToken = @json(config('services.mapbox.token'));

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

            this.map.on('click', (e) => {
                console.log('Map clicked at:', e.lngLat);
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
                if (!site || !site.forecast || !site.forecast.length) return;

                if (site) {
                    const offset = this.isMobileView ? [0, -window.innerHeight * 0.3] : [0, 0];

                    this.map.easeTo({
                        center: [site.lng, site.lat],
                        zoom: 12,
                        offset
                    });
                }

                setTimeout(() => {
                    console.log("Forecast data:", site.forecast);

                    const chartEl = document.getElementById('swellChart');
                    if (!chartEl) return;

                    if (window.swellChart && typeof window.swellChart.destroy === 'function') {
                        window.swellChart.destroy();
                    }

                    const ctx = chartEl.getContext('2d');

                    const forecastSlice = site.forecast.slice(0, 48);

                    const labels = forecastSlice.map(f =>
                        new Date(f.forecast_time).toLocaleTimeString('en-AU', {
                            hour: 'numeric',
                            hour12: true
                        }).toUpperCase()
                    );
                    const swellData = forecastSlice.map(f => f.wave_height ?? null);
                    const periodData = forecastSlice.map(f => f.wave_period ?? null);

                    const currentTimePlugin = {
                        id: 'currentTimeLine',
                        beforeDatasetsDraw(chart) {
                            const now = new Date();
                            const hour = now.getHours();

                            const xAxis = chart.scales.x;
                            const index = chart.data.labels.findIndex(label => {
                                const labelHour = parseInt(label);
                                return labelHour === hour;
                            });

                            if (index === -1) return;

                            const ctx = chart.ctx;
                            const x = xAxis.getPixelForValue(index);

                            ctx.save();
                            ctx.beginPath();
                            ctx.moveTo(x, chart.chartArea.top);
                            ctx.lineTo(x, chart.chartArea.bottom);
                            ctx.lineWidth = 2;
                            ctx.strokeStyle = 'rgba(37,99,235,0.4)'; // Tailwind blue-500 faint
                            ctx.stroke();
                            ctx.restore();
                        }
                    };

                    const swellDirectionPlugin = {
                        id: 'swellDirectionArrows',
                        afterDatasetsDraw(chart, args, options) {
                            const { ctx, data } = chart;
                            const arrowImg = document.getElementById('arrow-icon');
                            const forecast = data.forecastRaw;
                            if (!forecast || !arrowImg?.complete) return;

                            const xAxis = chart.scales.x;
                            const yAxis = chart.scales.y;

                            forecast.forEach((point, i) => {
                                if (i % 3 !== 0) return; // Show arrow every 3rd hour

                                const value = chart.data.datasets[0].data[i];
                                if (value === null || value === undefined) return;

                                const x = xAxis.getPixelForValue(i);
                                const y = yAxis.getPixelForValue(value);

                                const angle = ((point.waveDirection + 180) - 90) * Math.PI / 180;

                                const size = 16;
                                ctx.save();
                                ctx.translate(x, y);
                                ctx.rotate(angle);
                                ctx.drawImage(arrowImg, -size / 2, -size / 2, size, size);
                                ctx.restore();
                            });
                        }
                    };

                    const hoverLineAndNightShadePlugin = {
                        id: 'hoverLineAndNightShade',
                        beforeDraw(chart) {
                            const { ctx, chartArea, scales, tooltip } = chart;
                            const x = tooltip?.caretX;

                            // 🔹 Draw hover line
                            if (x) {
                                ctx.save();
                                ctx.beginPath();
                                ctx.moveTo(x, chartArea.top);
                                ctx.lineTo(x, chartArea.bottom);
                                ctx.lineWidth = 1;
                                ctx.strokeStyle = 'rgba(0,0,0,0.2)';
                                ctx.stroke();
                                ctx.restore();
                            }

                            // 🔹 Shade night hours (7 PM to 6 AM)
                            const xScale = scales.x;
                            const labels = chart.data.labels;

                            labels.forEach((label, index) => {
                                // Parse "2 PM", "5 AM", etc.
                                const [hourRaw, period] = label.split(' ');
                                let hour = parseInt(hourRaw);

                                if (period === 'PM' && hour !== 12) hour += 12;
                                if (period === 'AM' && hour === 12) hour = 0;

                                if (hour >= 19 || hour < 6) {
                                    const barX = xScale.getPixelForValue(index);
                                    const barW = xScale.getPixelForValue(index + 1) - barX;

                                    ctx.save();
                                    ctx.fillStyle = 'rgba(30,41,59,0.1)'; // Tailwind slate-800 w/ opacity
                                    ctx.fillRect(barX, chartArea.top, barW, chartArea.bottom - chartArea.top);
                                    ctx.restore();
                                }
                            });
                        }
                    };

                    window.swellChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: 'Swell Height (m)',
                                    data: swellData,
                                    borderColor: '#0ea5e9',
                                    backgroundColor: 'rgba(14,165,233,0.15)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    pointRadius: 2,
                                    yAxisID: 'y'
                                },
                                {
                                    label: 'Swell Period (s)',
                                    data: periodData,
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16,185,129,0.2)',
                                    borderDash: [4, 4],
                                    borderWidth: 2,
                                    tension: 0.4,
                                    pointRadius: 3,
                                    yAxisID: 'y1',
                                    spanGaps: true
                                }
                            ],
                            forecastRaw: forecastSlice.map(f => ({
                                waveDirection: f.wave_direction ?? 0,
                                wavePeriod: f.wave_period ?? null
                            }))
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                tooltip: {
                                    enabled: true,
                                    backgroundColor: 'rgba(17,24,39,0.9)', // Tailwind gray-900
                                    borderColor: '#94a3b8', // Tailwind slate-400
                                    borderWidth: 1,
                                    titleColor: '#fff',
                                    bodyColor: '#e2e8f0',
                                    padding: 12,
                                    cornerRadius: 6,
                                    callbacks: {
                                        title: (tooltipItems) => {
                                            return `Time: ${tooltipItems[0].label}`;
                                        },
                                        label: (tooltipItem) => {
                                            return `${tooltipItem.dataset.label}: ${tooltipItem.formattedValue}`;
                                        }
                                    }
                                },
                                legend: {
                                    labels: {
                                        color: '#334155' // slate-700
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    title: { display: true, text: 'Height (m)' },
                                    beginAtZero: true,
                                    position: 'left',
                                    max: 8
                                },
                                y1: {
                                    title: { display: true, text: 'Period (s)' },
                                    beginAtZero: true,
                                    position: 'right',
                                    grid: { drawOnChartArea: false },
                                    max: 16
                                },
                                x: {
                                    title: { display: true, text: 'Time' },
                                    ticks: {
                                        color: '#666'
                                    }
                                }
                            }
                        },
                        plugins: [currentTimePlugin, swellDirectionPlugin, hoverLineAndNightShadePlugin]
                    });
                }, 50);
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
                    mapComponent.showFilters = false;
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