@extends('layouts.vizzbud')

@section('title', 'Dive Site Map | Vizzbud')
@section('meta_description', 'Explore live scuba dive site conditions on an interactive map. Filter by dive level, type, and get wave, wind, and tide data for each location.')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<img id="arrow-icon" src="/icons/right-arrow.svg" style="display:none;" />
<div
  class="relative w-full bg-white"
  style="height: calc(100vh - 64px);"
  x-data="diveSiteMap({ sites: @js($sites) })"
>
    {{-- Search and Controls --}}
   <div class="absolute top-4 left-1/2 -translate-x-1/2 z-20 space-y-2 w-full px-4 sm:left-1 sm:translate-x-0 sm:max-w-[428px] sm:w-auto">
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
                  class="w-full rounded-full px-5 py-3 pr-12 
                          text-black placeholder-slate-600 
                          bg-white/30 backdrop-blur-md
                          hover:ring-2 hover:ring-cyan-400
                          border border-white/30 shadow-lg
                          focus:outline-none focus:ring-2 focus:ring-cyan-400
                          transition"
              />

              {{-- Clear button --}}
              <button 
                  x-show="query.length" 
                  type="button" 
                  @click="query=''; selectedId=null; open=false; const m=Alpine.$data(document.querySelector('[x-data^=diveSiteMap]')); if(m) m.selectedSite=null;" 
                  class="absolute right-3 top-1/2 -translate-y-1/2 
                          text-slate-500 hover:text-black 
                          text-2xl z-40 transition">
                  ×
              </button>

              <input type="hidden" name="dive_site_id" :value="selectedId">

              <ul
                x-show="open && filtered.length"
                data-search-list
                class="absolute z-30 bg-white/30 backdrop-blur-md text-black rounded-2xl shadow-lg w-full mt-2 max-h-60 overflow-y-auto border border-white/30"
                x-transition
              >
                <template x-for="(site, index) in filtered" :key="site.id">
                  <li
                    :data-item="index"
                    :class="{
                      'bg-white/50 backdrop-blur-sm': index === focusedIndex,
                      'px-4 py-2 cursor-pointer transition-colors': true
                    }"
                    @click="select(index)"
                    @mouseover="focusedIndex = index"
                    x-html="highlight(site.name, debouncedQuery)"
                  ></li>
                </template>
              </ul>

              <!-- Optional “no results” block -->
              <div
                x-show="open && !filtered.length && debouncedQuery"
                class="mt-2 rounded-xl bg-white/30 backdrop-blur-md border border-white/30 p-3 text-sm text-slate-700"
              >
                No results for “<span x-text="query"></span>”.
              </div>
            </div>

            <button
                @click="showFilters = !showFilters"
                class="bg-white/30 backdrop-blur-md border border-white/30 
                      hover:ring-2 hover:ring-cyan-400
                      text-slate-800 rounded-full w-10 h-10 
                      shadow-md flex items-center justify-center 
                      transition relative -top-1 z-40"
            >
                <img src="/icons/filter.svg" alt="Filter" class="w-6 h-6">
            </button>
        </div>

       <div x-show="showFilters" x-transition>
        {{-- Filters --}}
        <div class="bg-white/30 backdrop-blur-md border border-white/30
                    rounded-xl p-3 shadow-lg space-y-3 text-sm relative z-30
                    w-full sm:w-[395px]">

          <select
            x-model="filterLevel"
            @change="$dispatch('filter-changed')"
            class="w-full rounded-lg px-3 py-2
                  bg-white/40 backdrop-blur-sm border border-white/30
                  text-slate-800 font-semibold shadow-sm
                  focus:ring-2 focus:ring-cyan-400 focus:outline-none">
            <option value="">All Levels</option>
            <option value="Open Water">Open Water</option>
            <option value="Advanced">Advanced</option>
            <option value="Deep">Deep</option>
          </select>

          <select
            x-model="filterType"
            @change="$dispatch('filter-changed')"
            class="w-full rounded-lg px-3 py-2
                  bg-white/40 backdrop-blur-sm border border-white/30
                  text-slate-800 font-semibold shadow-sm
                  focus:ring-2 focus:ring-cyan-400 focus:outline-none">
            <option value="">All Types</option>
            <option value="shore">Shore</option>
            <option value="boat">Boat</option>
          </select>

          {{-- Actions: full-width when no filters, split 2 columns when any filter is set --}}
          <div :class="hasActiveFilters ? 'grid grid-cols-2 gap-2' : ''">
            <button
              @click="centerMap"
              class="w-full rounded-full px-4 py-2 font-semibold
                    bg-cyan-500/90 hover:bg-cyan-500 text-white shadow-md transition">
              Center Map
            </button>

            <template x-if="hasActiveFilters">
              <button
                @click="resetFilters"
                class="w-full rounded-full px-4 py-2 font-semibold
                      bg-white/40 hover:bg-white/50 text-slate-900
                      border border-white/40 backdrop-blur-sm shadow transition">
                Reset
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    {{-- Map --}}
    <div id="map" class="w-full" style="height: calc(100vh - 64px);"></div>

  {{-- Info Sidebar for Desktop (glassy, capped to screen height) --}}
  <div
    x-show="!isMobileView"
    :class="selectedSite && !isMobileView ? 'translate-x-0' : '-translate-x-full'"
    class="absolute top-0 left-0 h-screen max-w-[430px] w-full
          bg-white/40 backdrop-blur-2xl
          border border-white/30 ring-1 ring-white/20 shadow-2xl
          z-10 text-slate-900
          transition-transform transform flex flex-col">

    <!-- glow accent -->
    <div class="pointer-events-none absolute inset-x-6 top-4 h-10 rounded-full bg-white/60 blur-2xl"></div>

    <!-- scrollable content, but only if taller than viewport -->
    <div class="px-6 pt-[4.5rem] pb-6 overflow-y-auto max-h-screen">
      <div class="mb-4 h-px bg-white/50"></div>
      @include('dive-sites.partials.info')
    </div>
  </div>

{{-- Backdrop --}}
<div
  x-show="isMobileView && selectedSite"
  x-transition.opacity
  class="fixed inset-0 z-20 bg-slate-900/40 backdrop-blur-sm"
  @click="selectedSite = null"
></div>

{{-- Info Bottom Sheet for Mobile --}}
<div
  x-show="isMobileView"
  id="mobileInfoPanel"
  :class="selectedSite && isMobileView ? 'translate-y-0' : 'translate-y-full'"
  class="fixed bottom-0 left-0 right-0 z-30
         w-full max-h-[80vh]
         bg-white/30 backdrop-blur-xl border-t border-white/30
         ring-1 ring-white/20 shadow-2xl
         text-slate-900 rounded-t-2xl
         transition-transform duration-300 ease-out overflow-hidden"
>
  <!-- drag handle -->
  <div class="flex justify-center pt-3">
    <div class="h-1.5 w-12 rounded-full bg-white/60"></div>
  </div>

  <!-- scrollable content -->
  <div class="px-5 pt-4 pb-[max(env(safe-area-inset-bottom),1rem)] overflow-y-auto max-h-[calc(80vh-3rem)]">
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
              center: [151.2653, -33.8568],
              zoom: 11
            });

            this.map.setPadding({ left: 430, right: 0, top: 0, bottom: 0 });
            this.map.resize();

            const urlParams = new URLSearchParams(window.location.search);
            const lat = parseFloat(urlParams.get('lat'));
            const lng = parseFloat(urlParams.get('lng'));
            const zoom = parseFloat(urlParams.get('zoom'));

            if (!isNaN(lat) && !isNaN(lng) && !isNaN(zoom)) {
                this.map.setCenter([lng, lat]);
                this.map.setZoom(zoom);
            }

            // 🔁 Restore last selected site (from localStorage)
            const lastSlug = localStorage.getItem('vizzbud_last_site');
            if (lastSlug) {
                const match = this.sites.find(s => s.slug === lastSlug);
                if (match) {
                    this.selectedSite = match;
                    localStorage.removeItem('vizzbud_last_site');
                }
            }

            this.map.on('click', (e) => {
                console.log('Map clicked at:', e.lngLat);
            });

            this.map.on('load', () => {
                // Add source for dive sites
                this.map.addSource('dive-sites', {
                    type: 'geojson',
                    data: { type: 'FeatureCollection', features: [] }
                });

                this.map.addLayer({
                  id: 'site-layer',
                  type: 'circle',
                  source: 'dive-sites',
                  paint: {
                    // solid inner dot (match your nav color)
                    'circle-color': '#0e7490', // Tailwind cyan-700

                    // status ring from feature property "color"
                    'circle-stroke-color': ['get', 'color'],
                    'circle-stroke-width': [
                      'case', ['boolean', ['get', 'selected'], false],
                      4, // thicker if selected
                      3
                    ],

                    'circle-radius': [
                      'case', ['boolean', ['get', 'selected'], false],
                      10,
                      7
                    ]
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

        get hasActiveFilters() {
          return !!(this.filterLevel || this.filterType);
        },

        resetFilters() {
          this.filterLevel = '';
          this.filterType  = '';
          this.selectedSite = null;     // optional: clear selection
          this.renderSites();

          // optional: clear the search input component
          const searchRoot = document.querySelector('[x-data^="siteSearch"]');
          const searchComponent = window.Alpine && Alpine.$data(searchRoot);
          if (searchComponent) {
            searchComponent.query = '';
            searchComponent.selectedId = null;
          }
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
            geometry: { type: 'Point', coordinates: [site.lng, site.lat] },
            properties: {
              id: site.id,
              color: this.getStatusColor(site.status || site.latestCondition?.status),
              selected: this.selectedSite && this.selectedSite.id === site.id
            }
          }));

          const coll = { type: 'FeatureCollection', features };

          if (this.map.getSource('dive-sites')) {
            this.map.getSource('dive-sites').setData(coll);
          }
        },

        getStatusColor(status) {
          switch ((status || '').toLowerCase()) {
            case 'green':
              return 'rgba(0, 255, 55, 1)';   // cyan-400, bright / neon teal
            case 'yellow':
              return 'rgba(251, 255, 0, 1)';   // yellow-300, glowing yellow
            case 'red':
              return 'rgba(255, 0, 0, 1)';    // red-500, bold electric red
            default:
              return 'rgba(156, 163, 175, 0.9)'; // slate-500, neutral but strong
          }
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
    debouncedQuery: '',
    open: false,
    selectedId: null,
    focusedIndex: 0,
    _t: null, // debounce timer

    init() {
      // Debounce the query so filtering isn’t done on every keypress
      this.$watch('query', () => {
        clearTimeout(this._t);
        this._t = setTimeout(() => {
          this.debouncedQuery = (this.query || '').trim();
          this.focusedIndex = 0;
        }, 120);
      });

      // Close on Escape, clear if already closed
      this.$el.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          if (this.open && this.debouncedQuery) this.query = '';
          this.open = false;
        }
      });
    },

    // --- Filtering / ranking ---
    get filtered() {
      const q = this.normalize(this.debouncedQuery);
      const max = 20;

      // Show nothing if empty query
      if (!q) return [];

      // Try to boost by closeness to user if available
      const mapRoot = document.querySelector('[x-data^="diveSiteMap"]');
      const mapComponent = window.Alpine && Alpine.$data(mapRoot);
      const hasUser = !!(mapComponent && mapComponent.userLat && mapComponent.userLng);

      const results = this.sites
        .map(site => {
          const name = site.name || '';
          const score = this.score(name, q)
            + (hasUser ? this.distanceBoost(site, mapComponent.userLat, mapComponent.userLng) : 0);
          return { site, score };
        })
        .filter(r => r.score > 0) // keep only meaningful matches
        .sort((a, b) => b.score - a.score)
        .slice(0, max)
        .map(r => r.site);

      return results;
    },

    score(name, q) {
      const n = this.normalize(name);
      if (!n) return 0;
      if (n === q) return 100;                 // exact
      if (n.startsWith(q)) return 80;          // prefix
      if (n.split(' ').some(w => w.startsWith(q))) return 60; // word-prefix
      if (n.includes(q)) return 40;            // substring
      // lightweight fuzzy: all chars in order
      let i = 0, hits = 0;
      for (const c of n) { if (c === q[i]) { hits++; i++; if (i >= q.length) break; } }
      return hits >= Math.max(2, Math.ceil(q.length * 0.6)) ? 25 : 0;
    },

    distanceBoost(site, userLat, userLng) {
      if (!site.lat || !site.lng) return 0;
      // very rough distance scaling to give a small bump to closer sites
      const d = Math.hypot(site.lat - userLat, site.lng - userLng);
      // closer → bigger boost, cap it
      return Math.max(0, 12 - d * 50); // tune as you like
    },

    normalize(s) {
      return (s || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // strip accents
        .trim();
    },

    // --- UI helpers ---
    move(direction) {
      if (!this.filtered.length) return;
      this.focusedIndex = (this.focusedIndex + direction + this.filtered.length) % this.filtered.length;
      // keep focused item in view
      this.$nextTick(() => {
        const list = this.$el.querySelector('[data-search-list]');
        const item = list?.querySelector(`[data-item="${this.focusedIndex}"]`);
        if (item && list) {
          const top = item.offsetTop, bottom = top + item.offsetHeight;
          if (top < list.scrollTop) list.scrollTop = top;
          else if (bottom > list.scrollTop + list.clientHeight) list.scrollTop = bottom - list.clientHeight;
        }
      });
    },

    select(index) {
      const site = this.filtered[index];
      if (!site) return;

      this.query = site.name;
      this.selectedId = site.id;
      this.open = false;

      const root = document.querySelector('[x-data^="diveSiteMap"]');
      const mapComponent = window.Alpine && Alpine.$data(root);

      if (mapComponent) {
        mapComponent.selectedSite = site;
        mapComponent.showFilters = false;
        mapComponent.map.flyTo({ center: [site.lng, site.lat], zoom: 12 });

        if (mapComponent.map.getSource('dive-sites')) {
          mapComponent.renderSites();
        }
      }
    },

    setQuery(name) {
      this.query = name;
    },

    highlight(label, q) {
      if (!q) return label;
      const safe = (s) => s.replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
      const nLabel = this.normalize(label);
      const nq = this.normalize(q);
      const i = nLabel.indexOf(nq);
      if (i === -1) return safe(label);
      return safe(label.slice(0, i)) +
             '<mark class="bg-cyan-300/40 text-inherit rounded px-0.5">' +
             safe(label.slice(i, i + q.length)) +
             '</mark>' +
             safe(label.slice(i + q.length));
    },
  };
}
</script>
@endpush