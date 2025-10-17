@extends('layouts.vizzbud')

@section('title', 'Dive Site Map | Vizzbud')
@section('meta_description', 'Explore live scuba dive site conditions on an interactive map. Filter by dive level, type, and get wave, wind, and tide data for each location.')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<img id="arrow-icon" src="/icons/right-arrow.svg" style="display:none;" />
<div
  class="relative w-full bg-white"
  style="height: calc(100vh - 64px);"
  x-data="diveSiteMap({ sites: @js($sites) })"
>
  {{-- Search and Controls --}}
<div class="absolute top-4 left-2 z-20 w-[90%] sm:w-[410px]" x-data="siteSearch()">
  <div class="flex items-start gap-2 relative w-full">
    {{-- Search Bar --}}
    <div class="relative flex-1">
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
               text-slate-900 placeholder-slate-500
               bg-white/25 backdrop-blur-2xl backdrop-saturate-150
               border border-white/30 ring-1 ring-white/20
               shadow-[0_8px_32px_rgba(31,38,135,0.25)]
               focus:ring-2 focus:ring-cyan-400 focus:shadow-[0_8px_36px_rgba(14,165,233,0.3)]
               hover:bg-white/30 hover:shadow-[0_8px_36px_rgba(31,38,135,0.3)]
               transition-all duration-200 ease-out
               placeholder:opacity-70 placeholder:not-italic"
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
    </div>

  {{-- Filter Button --}}
  <button
    @click="showFilters = !showFilters"
    class="bg-white/30 backdrop-blur-md border border-white/30 
           hover:ring-2 hover:ring-cyan-400
           text-slate-800 rounded-full w-12 h-12
           shadow-md flex items-center justify-center 
           transition z-40"
  >
    <img src="/icons/filter.svg" alt="Filter" class="w-6 h-6">
  </button>

    {{-- Dropdown (matches full 430 px width) --}}
    <ul
      x-show="open && filtered.length"
      data-search-list
      x-transition.opacity
      class="absolute left-0 right-0 top-full mt-2
             max-h-72 overflow-y-auto overflow-x-hidden
             rounded-2xl border border-white/30
             bg-white/20 backdrop-blur-xl backdrop-saturate-150
             ring-1 ring-white/20 divide-y divide-white/20
             shadow-[0_8px_32px_rgba(31,38,135,0.37)]
             scrollbar-thin scrollbar-thumb-white/30 scrollbar-track-transparent"
    >
      <template x-for="(site, index) in filtered" :key="site.id">
        <li
          :data-item="index"
          @click="select(index)"
          @mouseover="focusedIndex = index"
          :class="[
            'px-4 py-3 cursor-pointer select-none transition-all duration-200',
            index === focusedIndex
              ? 'bg-white/40 backdrop-blur-md shadow-inner scale-[1.01]'
              : 'hover:bg-white/30 hover:backdrop-blur-md'
          ]"
        >
          <div class="font-semibold text-slate-900 truncate tracking-wide" x-text="site.name"></div>
          <div class="text-xs text-slate-700 mt-0.5 truncate opacity-90">
            <template x-if="site.region || site.country">
              <span>
                <span x-text="site.region || ''"></span>
                <template x-if="site.region && site.country">, </template>
                <span x-text="site.country || ''"></span>
              </span>
            </template>
          </div>
        </li>
      </template>
    </ul>
  </div>

  <!-- Loading & No Results -->
  <div
    x-show="open && loading"
    x-transition.opacity
    class="mt-2 rounded-xl bg-white/30 backdrop-blur-md border border-white/30 p-3 text-sm text-slate-700 text-center">
    Searching...
  </div>

  <div
    x-show="open && !filtered.length && debouncedQuery && !loading"
    x-transition.opacity
    class="mt-2 rounded-xl bg-white/30 backdrop-blur-md border border-white/30 p-3 text-sm text-slate-700 text-center">
    No results for “<span x-text="query"></span>”.
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
    <div class="px-2 pt-[4.5rem] pb-6 overflow-y-auto max-h-screen z-50">
      <div class="mb-4 h-px bg-white/50"></div>
      @include('dive-sites.partials.info', ['chartId' => 'swellChart-desktop'])
    </div>
  </div>

{{-- Backdrop --}}
<div
  x-show="isMobileView && selectedSite"
  x-transition.opacity
  class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm"
  @click="selectedSite = null"
></div>

{{-- Info Bottom Sheet for Mobile --}}
<div
  x-show="isMobileView"
  id="mobileInfoPanel"
  :class="selectedSite && isMobileView ? 'translate-y-0' : 'translate-y-full'"
  class="fixed bottom-0 left-0 right-0 z-50
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
    @include('dive-sites.partials.info', ['chartId' => 'swellChart-mobile'])
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


        initialDesktop: { center: [151.3553, -33.8568], zoom: 11 },
        initialMobile:  { center: [151.2900, -33.8100], zoom: 11 },

        hasInteracted: false, // so we don't recenter after user pans/zooms

        getInitialView() {
          // URL overrides everything if provided
          const url = new URLSearchParams(window.location.search);
          const lat  = parseFloat(url.get('lat'));
          const lng  = parseFloat(url.get('lng'));
          const zoom = parseFloat(url.get('zoom'));
          if (!isNaN(lat) && !isNaN(lng) && !isNaN(zoom)) {
            return { center: [lng, lat], zoom };
          }
          // Otherwise pick by device
          return this.isMobileView ? this.initialMobile : this.initialDesktop;
        },

        init() {

            this.isMobileView = window.innerWidth < 640;

            const view = this.getInitialView();

            this.map = new mapboxgl.Map({
              container: 'map',
              style: 'mapbox://styles/mapbox/streets-v11',
              center: view.center,
              zoom: view.zoom
            });

            this.applyMapPadding();

            this.map.on('dragstart', () => this.hasInteracted = true);
            this.map.on('zoomstart', () => this.hasInteracted = true);

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

                if (!this.hasInteracted && !this.selectedSite) {
                    this.map.flyTo({
                        center: [this.userLng, this.userLat],
                        zoom: 11,
                        speed: 0.8
                    });
                }
            });

            this.$watch('filterLevel', () => this.renderSites());
            this.$watch('filterType', () => this.renderSites());

            window.addEventListener('resize', () => {
              this.isMobileView = window.innerWidth < 640;
              this.applyMapPadding();
            });

            this.$watch('selectedSite', site => {

                if (this.isMobileView) {
                  if (site) {
                    document.body.classList.add('no-scroll');
                  } else {
                    document.body.classList.remove('no-scroll');
                  }
                }

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

                  // 🔤 Normalize to lowercase so 'Green' / 'YELLOW' still work
                  if (site && site.today_summary) {
                    ['morning','afternoon','night'].forEach(p => {
                      if (site.today_summary[p] != null) {
                        site.today_summary[p] = String(site.today_summary[p]).toLowerCase();
                      }
                    });
                  }

                setTimeout(() => {
                    console.log("Forecast data:", site.forecast);

                    const chartId = this.isMobileView ? 'swellChart-mobile' : 'swellChart-desktop';
                    const chartEl = document.getElementById(chartId);
                    if (!chartEl) return;

                    if (window.swellChart && typeof window.swellChart.destroy === 'function') {
                        window.swellChart.destroy();
                    }

                    const ctx = chartEl.getContext('2d');

                    chartEl.style.width = '100%';
                    chartEl.style.height = '100%';
                    const dpi = Math.min(window.devicePixelRatio || 1, 2);

                    // Build a 24h window anchored around "now" but clamped to data bounds
                    const all = site.forecast || [];
                    const allTimes = all.map(f => new Date(f.forecast_time + 'Z')); // treat as UTC
                    const now = new Date();
                    const nowHour = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours());

                    // find first forecast point at/after this hour
                    let nowIdx = allTimes.findIndex(t => t >= nowHour);
                    if (nowIdx === -1) nowIdx = allTimes.length - 1; // fallback to last point if all in past

                    const TOTAL = all.length;
                    const WINDOW = 24;     // aim for 24 hours
                    const BACK   = 2;      // show 2 hrs before "now" if possible

                    // clamp the window so we don't run off either end
                    let start = Math.max(0, Math.min(nowIdx - BACK, Math.max(0, TOTAL - WINDOW)));
                    let end   = Math.min(start + WINDOW, TOTAL);

                    const forecastSlice = all.slice(start, end);

                    // where is "now" inside this slice? (for the vertical line)
                    const nowPos = Math.max(0, Math.min(nowIdx - start, forecastSlice.length - 1));

                    // labels & series
                    const labels = forecastSlice.map(f =>
                      new Date(f.forecast_time + 'Z').toLocaleTimeString(undefined, { hour: 'numeric', hour12: true }).toUpperCase()
                    );
                    const swellData  = forecastSlice.map(f => f.wave_height ?? null);
                    const periodData = forecastSlice.map(f => f.wave_period ?? null);

                    // expose raw for plugins/tooltips
                    const forecastRaw = forecastSlice.map(f => ({
                      time: f.forecast_time,
                      waveDirection: f.wave_direction ?? null,
                      wavePeriod: f.wave_period ?? null,
                      waveHeight: f.wave_height ?? null
                    }));

                    const currentTimePlugin = {
                      id: 'currentTimeLineFixed',
                      beforeDatasetsDraw(chart) {
                        const { ctx, chartArea, scales } = chart;
                        const xAxis = scales.x;
                        if (!xAxis) return;

                        const x = xAxis.getPixelForValue(nowPos);
                        ctx.save();
                        ctx.beginPath();
                        ctx.moveTo(x, chartArea.top);
                        ctx.lineTo(x, chartArea.bottom);
                        ctx.lineWidth = 2;
                        ctx.strokeStyle = 'rgba(59,130,246,0.45)';
                        ctx.setLineDash([6, 4]);
                        ctx.stroke();
                        ctx.restore();
                      }
                    };

                    const hoverLineAndNightShadePlugin = {
                        id: 'hoverLineAndNightShade',
                        beforeDraw(chart) {
                            const { ctx, chartArea, scales, tooltip } = chart;
                            const x = tooltip?.caretX;

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

                    // helper: deg → compass
                    const degToCompass = (deg) => {
                      if (deg == null || isNaN(deg)) return '—';
                      const dirs = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];
                      return dirs[Math.round(((deg % 360) / 22.5)) % 16];
                    };

                    // gradient for height fill
                    const grad = ctx.createLinearGradient(0, 0, 0, chartEl.height);
                    grad.addColorStop(0, 'rgba(14,165,233,0.22)');
                    grad.addColorStop(1, 'rgba(14,165,233,0.04)');

                    // Modern direction arrows along bottom (no image)
                    const swellDirectionPlugin = {
                      id: 'swellDirectionArrowsModern',
                      afterDatasetsDraw(chart) {
                        const { ctx, chartArea, scales, data } = chart;
                        const x = scales.x;
                        const forecast = data.forecastRaw || [];
                        if (!forecast.length) return;

                        const step = Math.ceil(forecast.length / 16);
                        ctx.save();
                        ctx.globalAlpha = 0.9;
                        ctx.lineWidth = 1.5;
                        ctx.strokeStyle = 'rgba(14,165,233,0.9)';
                        ctx.fillStyle = 'rgba(14,165,233,0.9)';

                        forecast.forEach((pt, i) => {
                          if (i % step !== 0) return;
                          const px = x.getPixelForValue(i);
                          const py = chartArea.bottom - 10;
                          const angle = (((pt.waveDirection ?? 0) + 180) - 90) * Math.PI / 180;

                          ctx.save();
                          ctx.translate(px, py);
                          ctx.rotate(angle);
                          ctx.beginPath(); ctx.moveTo(-8, 0); ctx.lineTo(6, 0); ctx.stroke();
                          ctx.beginPath(); ctx.moveTo(6, 0); ctx.lineTo(2, -4); ctx.lineTo(2, 4); ctx.closePath(); ctx.fill();
                          ctx.restore();
                        });
                        ctx.restore();
                      }
                    };

                    // Destroy old chart if any
                    if (window.swellChart?.destroy) window.swellChart.destroy();

                    window.swellChart = new Chart(ctx, {
                      type: 'line',
                      data: {
                        labels,
                        datasets: [
                          {
                            label: 'Swell Height',
                            data: swellData,
                            borderColor: '#0ea5e9',
                            backgroundColor: grad,
                            fill: true,
                            borderWidth: 2,
                            tension: 0.35,
                            pointRadius: 1,
                            pointHoverRadius: 3,
                            yAxisID: 'y'
                          },
                          {
                            label: 'Period',
                            data: periodData,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.12)',
                            borderDash: [6, 4],
                            borderWidth: 2,
                            tension: 0.35,
                            pointRadius: 0,
                            pointHoverRadius: 3,
                            yAxisID: 'y1',
                            spanGaps: true
                          }
                        ],
                        forecastRaw
                      },
                      options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        devicePixelRatio: dpi,
                        interaction: { mode: 'index', intersect: false },
                        layout: { padding: { top: 8, right: 8, bottom: 24, left: 8 } },
                        plugins: {
                          legend: {
                            labels: {
                              usePointStyle: true,
                              pointStyle: 'line',
                              boxWidth: 10,
                              color: '#000000ff'
                            }
                          },
                          tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(2,6,23,0.92)',
                            borderColor: 'rgba(255,255,255,0.12)',
                            borderWidth: 1,
                            titleColor: '#e2e8f0',
                            bodyColor: '#cbd5e1',
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                              title: (items) => items[0]?.label ?? '',
                              afterTitle: (items) => {
                                const idx = items[0].dataIndex;
                                const raw = items[0].chart.data.forecastRaw?.[idx];
                                if (!raw) return '';
                                const dir = raw.waveDirection;
                                return `Dir: ${dir ?? '—'}° (${degToCompass(dir)})`;
                              },
                              label: (item) => {
                                const name = item.dataset.label;
                                const v = item.formattedValue;
                                if (name === 'Swell Height') return `Height: ${v} m`;
                                if (name === 'Period') return `Period: ${v} s`;
                                return `${name}: ${v}`;
                              }
                            }
                          }
                        },
                        scales: {
                          x: {
                            ticks: {
                              color: 'rgba(0, 0, 0, 0.8)',
                            },
                            grid: { color: 'rgba(255,255,255,0.08)', tickLength: 0 }
                          },
                          y: {
                            title: { display: true, text: 'Height (m)', color: '#000000ff' },
                            beginAtZero: true,
                            suggestedMax: Math.max(3, Math.ceil((Math.max(...(swellData.filter(n=>n!=null))) || 0) + 1)),
                            ticks: { color: 'rgba(0, 0, 0, 0.9)' },
                            grid: { color: 'rgba(255,255,255,0.1)' },
                            position: 'left'
                          },
                          y1: {
                            title: { display: true, text: 'Period (s)', color: '#000000ff' },
                            beginAtZero: true,
                            suggestedMax: Math.max(10, Math.ceil((Math.max(...(periodData.filter(n=>n!=null))) || 0) + 2)),
                            ticks: { color: 'rgba(0, 0, 0, 0.9)' },
                            grid: { drawOnChartArea: false, color: 'rgba(255,255,255,0.1)' },
                            position: 'right'
                          }
                        }
                      },
                      plugins: [currentTimePlugin, hoverLineAndNightShadePlugin, swellDirectionPlugin]
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
          this.selectedSite = null;   
          this.renderSites();

          // optional: clear the search input component
          const searchRoot = document.querySelector('[x-data^="siteSearch"]');
          const searchComponent = window.Alpine && Alpine.$data(searchRoot);
          if (searchComponent) {
            searchComponent.query = '';
            searchComponent.selectedId = null;
          }
        },

        applyMapPadding() {
          if (this.isMobileView) {
            this.map.setPadding({ left: 0, right: 0, top: 0, bottom: 0 });
          } else {
            this.map.setPadding({ left: 430, right: 0, top: 0, bottom: 0 }); // sidebar width
          }
          this.map.resize();
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

        timeAgo(dateString) {
          if (!dateString) return '—';

          // Try to parse safely
          let then = new Date(dateString);
          if (isNaN(then.getTime())) {
            // Fallback if format is missing timezone info
            then = new Date(dateString + 'Z');
          }

          const now = new Date();
          const diffMs = now - then;
          const diffSec = Math.floor(diffMs / 1000);

          // Handle negative/future timestamps gracefully
          if (diffSec < 0) return 'just now';

          const diffMin = Math.floor(diffSec / 60);
          const diffHr = Math.floor(diffMin / 60);
          const diffDay = Math.floor(diffHr / 24);

          if (diffSec < 60) return `${diffSec}s ago`;
          if (diffMin < 60) return `${diffMin}m ago`;
          if (diffHr < 24) return `${diffHr}h ago`;
          if (diffDay < 7) return `${diffDay}d ago`;

          // fallback for older timestamps
          return then.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
        },

        formatWind(speed, dir) {
            if (!speed) return '—';
            return (speed * 1.94384).toFixed(1) + ' kn from ' + this.compass(dir);
        }
    }
}

  function siteSearch() {
    return {
      service: null,
      sites: [],
      query: '',
      debouncedQuery: '',
      open: false,
      selectedId: null,
      focusedIndex: 0,
      loading: false,
      _t: null,

      async init() {
        this.service = new window.DiveSiteService();

        this.$watch('query', () => {
          clearTimeout(this._t);
          this._t = setTimeout(async () => {
            this.debouncedQuery = (this.query || '').trim();
            this.focusedIndex = 0;

            if (!this.debouncedQuery) {
              this.sites = [];
              return;
            }

            this.loading = true;

            // pull lat/lng from map if available
            const mapRoot = document.querySelector('[x-data^="diveSiteMap"]');
            const mapComponent = window.Alpine && Alpine.$data(mapRoot);
            const lat = mapComponent?.userLat || '';
            const lng = mapComponent?.userLng || '';

            // First: fetch local results
            const localData = await this.service.fetchSites({
              query: this.debouncedQuery,
              lat,
              lng,
              worldwide: false,
            });

            this.sites = localData.results || [];
            this.loading = false;

            // Then: in background, try worldwide search and merge results
            this.service.fetchSites({
              query: this.debouncedQuery,
              lat,
              lng,
              worldwide: true,
            }).then((globalData) => {
              if (!globalData?.results?.length) return;

            const localIds = new Set(this.sites.map(s => s.id));
            const newOnes = (globalData.results || []).filter(s => !localIds.has(s.id));

            // merge and then sort by distance if available
            const merged = [...this.sites, ...newOnes];

            // Sort logic: prioritize known distances first (local), ordered ascending
            merged.sort((a, b) => {
              const da = a.distance_km ?? Infinity;
              const db = b.distance_km ?? Infinity;
              return da - db;
            });

            this.sites = merged;
            });
          }, 250);
        });

        // Close on Escape
        this.$el.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') {
            this.open = false;
            this.query = '';
          }
        });
      },

      get filtered() {
        return this.sites;
      },

      move(direction) {
        if (!this.filtered.length) return;
        this.focusedIndex = (this.focusedIndex + direction + this.filtered.length) % this.filtered.length;
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

      async select(index) {
        const site = this.filtered[index];
        if (!site) return;

        this.query = site.name;
        this.selectedId = site.id;
        this.open = false;

        const root = document.querySelector('[x-data^="diveSiteMap"]');
        const mapComponent = window.Alpine && Alpine.$data(root);
        if (!mapComponent) return;

        // Try to find full site info in map's existing dataset
        let fullSite = mapComponent.sites.find(s => s.id == site.id);

        // If not found (global search result or partial), fetch the full record
        if (!fullSite) {
          try {
            const res = await fetch(`/api/dive-sites/${site.id}`);
            if (res.ok) {
              fullSite = await res.json();
            } else {
              console.warn('Could not fetch full site data for', site.id);
              fullSite = site;
            }
          } catch (err) {
            console.error('Failed to load site details:', err);
            fullSite = site;
          }
        }

        // Assign full site with forecast, conditions, etc.
        mapComponent.selectedSite = fullSite;
        mapComponent.showFilters = false;

        // Fly to it on map
        mapComponent.map.flyTo({ center: [fullSite.lng, fullSite.lat], zoom: 12 });

        // Re-render markers
        mapComponent.renderSites();
      },

      expandWorldwide() {
        this.loading = true;

        const mapRoot = document.querySelector('[x-data^="diveSiteMap"]');
        const mapComponent = window.Alpine && Alpine.$data(mapRoot);
        const lat = mapComponent?.userLat || '';
        const lng = mapComponent?.userLng || '';

        this.service.fetchSites({
          query: this.debouncedQuery,
          lat,
          lng,
          worldwide: true, // 🌍 override the local restriction
        }).then((data) => {
          this.sites = data.results || [];
          this.loading = false;
        });
      },

      highlight(label, q) {
        if (!q) return label;
        const safe = (s) => s.replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
        const nLabel = label.toLowerCase();
        const nq = q.toLowerCase();
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