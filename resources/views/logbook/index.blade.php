@extends('layouts.vizzbud')

@section('title', 'Dive Log | Vizzbud')
@section('meta_description', 'Track your scuba dives by site, depth, and duration. View stats, charts, and search your personal dive history with Vizzbud.')

{{-- 🌍 Open Graph / Twitter --}}
@section('og_title', 'Dive Log | Vizzbud')
@section('og_description', 'Track dives, explore stats, and visualize your scuba history with Vizzbud.')
@section('og_image', asset('images/divesites/default.webp'))
@section('twitter_title', 'Dive Log | Vizzbud')
@section('twitter_description', 'Log your scuba dives and explore your personal dive data with Vizzbud.')
@section('twitter_image', asset('images/divesites/default.webp'))

{{-- Canonical + Structured Data --}}
@push('head')
  <link rel="canonical" href="{{ url('/logbook') }}">

  {{-- Structured Data (WebPage + Breadcrumbs) --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "Dive Log",
    "description": "Track and analyze your scuba dives by site, depth, and duration using Vizzbud’s personal dive log system.",
    "url": "https://vizzbud.com/logbook",
    "breadcrumb": {
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "Home",
          "item": "https://vizzbud.com/"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "Dive Log",
          "item": "https://vizzbud.com/logbook"
        }
      ]
    },
    "publisher": {
      "@type": "Organization",
      "name": "Vizzbud",
      "url": "https://vizzbud.com",
      "logo": {
        "@type": "ImageObject",
        "url": "https://vizzbud.com/android-chrome-512x512.png"
      }
    }
  }
  </script>
@endpush

{{-- Mapbox dependencies --}}
@push('head')
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet">
  <script defer src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>
@endpush

@section('content')
<section class="max-w-6xl mx-auto px-4 sm:px-6 py-12">

    @if(session('verified'))
      <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 4000)"
        x-show="show"
        x-transition
        class="mb-6 text-center font-semibold
               rounded-xl bg-emerald-500/15 text-emerald-200
               ring-1 ring-emerald-400/30 border border-white/10
               backdrop-blur-md shadow-lg px-4 py-3">
        Your email has been successfully verified!
      </div>
    @endif

    {{-- Header: title + CTA --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="inline-flex items-center justify-center gap-2 text-center sm:justify-start
                 text-3xl font-extrabold tracking-tight text-white">
        @include('components.icon', ['name' => 'notebook'])
        <span>Dive Log</span>
      </h1>

      @auth
      <a href="{{ route('logbook.create') }}"
        aria-label="Log a new dive"
        class="group inline-flex w-full items-center justify-center gap-2
                rounded-full px-6 py-3 text-base sm:text-lg font-semibold text-white
                bg-gradient-to-r from-cyan-500/90 to-teal-400/90
                hover:from-cyan-400/90 hover:to-teal-300/90
                border border-white/20 ring-1 ring-white/10
                backdrop-blur-md shadow-lg shadow-cyan-500/20
                hover:shadow-cyan-400/30 hover:-translate-y-0.5
                active:translate-y-0 active:shadow-cyan-500/10
                transition-all duration-300 ease-out sm:w-auto">
        <span class="tracking-tight">Log a Dive</span>
      </a>
      @endauth
    </div>

    {{-- Stats Grid as Pills --}}
    @auth
    <div class="mb-12">

      {{-- Mobile (2 per row) --}}
      <div class="grid grid-cols-2 gap-2 sm:hidden">
        @foreach ([
            ['Total Dives', $totalDives],
            ['Dive Time', $totalHours . 'h ' . $remainingMinutes . 'm'],
            ['Deepest', $deepestDive . ' m', $deepestDiveId ?? null],
            ['Longest', $longestDive . ' min', $longestDiveId ?? null],
            ['Avg Depth', $averageDepth . ' m'],
            ['Avg Duration', $averageDuration . ' min'],
            ['Top Site', $siteName],
            ['Sites', $uniqueSitesVisited],
        ] as $item)
          @php
            $label = $item[0];
            $value = $item[1];
            $linkId = $item[2] ?? null;
            $isClickable = in_array($label, ['Deepest', 'Longest']) && $linkId;
          @endphp

          @if($isClickable)
            <a href="{{ route('logbook.show', $linkId) }}"
              class="inline-flex flex-col items-center justify-center 
                    rounded-full px-3 py-2 
                    bg-white/10 backdrop-blur-md 
                    ring-1 ring-white/15 border border-white/10 
                    text-slate-200 text-xs sm:text-sm
                    hover:bg-cyan-500/20 transition cursor-pointer">
              <span class="font-semibold">{{ $value }}</span>
              <span class="text-[0.65rem] uppercase tracking-wide text-slate-400">{{ $label }}</span>
            </a>
          @else
            <span class="inline-flex flex-col items-center justify-center 
                        rounded-full px-3 py-2 
                        bg-white/10 backdrop-blur-md 
                        ring-1 ring-white/15 border border-white/10 
                        text-slate-200 text-xs sm:text-sm">
              <span class="font-semibold">{{ $value }}</span>
              <span class="text-[0.65rem] uppercase tracking-wide text-slate-400">{{ $label }}</span>
            </span>
          @endif
        @endforeach
      </div>

      {{-- Desktop (grid of pills, 4 per row) --}}
      <div class="hidden sm:grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['Total Dives', $totalDives],
            ['Dive Time', $totalHours . 'h ' . $remainingMinutes . 'm'],
            ['Deepest', $deepestDive . ' m', $deepestDiveId ?? null],
            ['Longest', $longestDive . ' min', $longestDiveId ?? null],
            ['Avg Depth', $averageDepth . ' m'],
            ['Avg Duration', $averageDuration . ' min'],
            ['Top Site', $siteName],
            ['Sites', $uniqueSitesVisited],
        ] as $item)
          @php
            $label = $item[0];
            $value = $item[1];
            $linkId = $item[2] ?? null;
            $isClickable = in_array($label, ['Deepest', 'Longest']) && $linkId;
          @endphp

          @if($isClickable)
            <a href="{{ route('logbook.show', $linkId) }}"
              class="inline-flex flex-col items-center justify-center 
                    rounded-full px-4 py-3 
                    bg-white/10 backdrop-blur-md 
                    ring-1 ring-white/15 border border-white/10 
                    text-slate-200 text-sm
                    hover:bg-cyan-500/20 transition cursor-pointer">
              <span class="font-bold text-white">{{ $value }}</span>
              <span class="text-[0.7rem] uppercase tracking-wide text-slate-400">{{ $label }}</span>
            </a>
          @else
            <span class="inline-flex flex-col items-center justify-center 
                        rounded-full px-4 py-3 
                        bg-white/10 backdrop-blur-md 
                        ring-1 ring-white/15 border border-white/10 
                        text-slate-200 text-sm">
              <span class="font-bold text-white">{{ $value }}</span>
              <span class="text-[0.7rem] uppercase tracking-wide text-slate-400">{{ $label }}</span>
            </span>
          @endif
        @endforeach
      </div>
    </div>
    @endauth

    @auth
    <div class="mb-12">
        {{-- Mobile Map --}}
        <div class="sm:hidden w-full h-[240px] relative rounded-2xl overflow-hidden shadow-lg mb-6 
                    border border-slate-700 backdrop-blur-md bg-slate-800/60">
          <div id="personal-dive-map-mobile"
              class="absolute inset-0 z-0 h-full w-full"></div>
          <div class="absolute top-3 left-4 z-10 
                      bg-slate-900/60 backdrop-blur-md 
                      text-white text-sm font-semibold px-3 py-1 
                      rounded-full border border-white/10 ring-1 ring-white/10 
                      shadow-md inline-flex items-center gap-2">
            @include('components.icon', ['name' => 'map'])
            <span>Your Dive Sites</span>
          </div>
        </div>

        {{-- Desktop Map --}}
        <div class="hidden sm:block relative rounded-2xl overflow-hidden shadow-lg 
                    border border-slate-700 backdrop-blur-md bg-slate-800/60">
          {{-- Glassy overlay header (now above the canvas) --}}
          <div class="absolute inset-x-0 top-0 z-20
                      flex justify-between items-center px-6 py-3
                      bg-slate-900/60 backdrop-blur-md 
                      border-b border-white/10 ring-1 ring-white/10">
            <h2 class="text-white font-semibold text-lg inline-flex items-center gap-2">
              @include('components.icon', ['name' => 'map'])
              <span>Your Dive Sites</span>
            </h2>
            <span class="text-sm text-slate-300">{{ count($siteCoords) }} visited</span>
          </div>

          {{-- Add top space so the map doesn't sit under the header --}}
          <div>
            <div id="personal-dive-map-desktop" class="h-[360px] w-full"></div>
          </div>
        </div>
    </div>
    @endauth

    {{-- Dive Activity Chart --}}
    @auth
    <div 
        class="bg-slate-800 rounded-xl p-4 sm:p-6 mb-12 shadow text-white text-sm sm:text-base"
        x-data="{ selectedYear: '{{ $selectedYear }}' }" 
        x-init="$watch('selectedYear', value => fetchChart(value))"
    >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4">
            <h2 class="font-semibold text-lg sm:text-xl inline-flex items-center gap-2">
                @include('components.icon', ['name' => 'calendar'])
                <span>Dive Activity</span>
            </h2>
            <select 
                x-model="selectedYear" 
                class="bg-slate-900 text-white border border-slate-600 px-3 py-2 rounded text-sm w-full sm:w-auto"
            >
                @foreach ($availableYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>

        {{-- Chart Container (mobile scroll, desktop fluid) --}}
        <div id="chartContainer"
            class="relative -mx-4 sm:mx-0 px-4 sm:px-0
                    overflow-x-auto sm:overflow-visible
                    scroll-smooth overscroll-x-contain touch-pan-x">
          <div class="inline-block align-top
                      min-w-[700px] sm:min-w-0 w-full">
            @include('logbook._chart')
          </div>
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-2 text-xs sm:text-sm text-slate-400 mt-4">
            <span>No dives</span>
            <div class="w-4 h-4 bg-slate-700 rounded-sm"></div>
            <div class="w-4 h-4 bg-cyan-200 rounded-sm"></div>
            <div class="w-4 h-4 bg-cyan-400 rounded-sm"></div>
            <div class="w-4 h-4 bg-cyan-500 rounded-sm"></div>
            <span>More dives</span>
        </div>
    </div>
    @endauth

{{-- All Dives Table --}}
@auth
<div 
  x-data="{
    rawLogs: @js($logs->values()),
    search: '',
    get logs() {
      return this.rawLogs.slice().sort((a, b) => new Date(b.dive_date) - new Date(a.dive_date));
    },
    get filteredLogs() {
      const q = this.search.toLowerCase().trim();
      if (!q) return this.logs;
      return this.logs.filter(log => {
        const site = log.site?.name?.toLowerCase() || '';
        const title = log.title?.toLowerCase() || '';
        const notes = log.notes?.toLowerCase() || '';
        const depth = (log.depth ?? '').toString();
        const duration = (log.duration ?? '').toString();
        return site.includes(q) || title.includes(q) || notes.includes(q) || depth.includes(q) || duration.includes(q);
      });
    }
  }"
  class="mt-12"
>
  {{-- Header + Search --}}
  <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="inline-flex items-center gap-2 text-xl font-semibold text-white">
      @include('components.icon', ['name' => 'diving'])
      <span>Your Dives</span>
      <span class="text-xs font-normal text-slate-400" x-text="`· ${filteredLogs.length} result${filteredLogs.length === 1 ? '' : 's'}`"></span>
    </h2>

    <label class="relative block sm:w-72">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
      </span>
      <input
        x-model="search"
        type="text"
        placeholder="Search dives…"
        class="w-full rounded-full bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10
               pl-10 pr-4 py-2 text-sm text-white placeholder:text-slate-400
               focus:outline-none focus:ring-cyan-300/50 focus:border-cyan-300/30 transition"
      />
    </label>
  </div>

  {{-- Results --}}
  <template x-if="filteredLogs.length > 0">
    <div>
      {{-- Desktop / Tablet: glassy table --}}
      <div class="hidden sm:block rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="sticky top-0 z-10 bg-slate-900/60 backdrop-blur supports-[backdrop-filter]:backdrop-blur-md text-slate-300">
              <tr class="[&>th]:px-4 [&>th]:py-3 [&>th]:font-semibold [&>th]:text-left">
                <th class="w-12">#</th>
                <th>Title</th>
                <th>Site</th>
                <th>Depth</th>
                <th>Duration</th>
                <th>Date</th>
                <th class="w-8"></th>
              </tr>
            </thead>
            <tbody class="text-slate-200">
              <template x-for="(log, idx) in filteredLogs" :key="log.id">
                <tr
                  class="group border-b border-white/10 hover:bg-white/5 focus-within:bg-white/5 transition"
                  @click="window.location.href = `/logbook/${log.id}`"
                >
                  <td class="px-4 py-3 tabular-nums text-slate-400" x-text="log.dive_number ?? idx + 1"></td>

                  <td class="px-4 py-3 font-semibold">
                    <a :href="`/logbook/${log.id}`"
                       class="outline-none rounded-md focus-visible:ring-2 focus-visible:ring-cyan-300">
                      <span x-text="log.title || '—'"></span>
                    </a>
                  </td>

                  <td class="px-4 py-3 font-medium">
                    <span class="inline-flex items-center gap-2">
                      <span class="truncate" x-text="log.site?.name || '—'"></span>
                    </span>
                  </td>

                  <td class="px-4 py-3">
                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                  bg-white/10 ring-1 ring-white/15 border border-white/10">
                      <span class="ml-1 tabular-nums" x-text="log.depth ? `${log.depth} m` : '—'"></span>
                    </span>
                  </td>

                  <td class="px-4 py-3">
                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                  bg-white/10 ring-1 ring-white/15 border border-white/10">
                      <span class="ml-1 tabular-nums" x-text="log.duration ? `${log.duration} min` : '—'"></span>
                    </span>
                  </td>

                  <td class="px-4 py-3" x-text="new Date(log.dive_date).toLocaleDateString()"></td>

                  <td class="px-2 py-3">
                    <a :href="`/logbook/${log.id}`"
                       class="flex h-8 w-8 items-center justify-center rounded-md text-cyan-300/70
                              group-hover:text-cyan-300 outline-none focus-visible:ring-2 focus-visible:ring-cyan-300">
                    </a>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      {{-- Mobile: cards --}}
      <div class="sm:hidden space-y-4">
        <template x-for="log in filteredLogs" :key="log.id">
          <button
            type="button"
            @click="window.location.href = `/logbook/${log.id}`"
            class="w-full text-left rounded-2xl border border-white/10 ring-1 ring-white/10
                   bg-white/10 backdrop-blur-xl shadow-xl p-4 hover:bg-white/5 transition"
          >
            <div class="flex items-center justify-between">
              <h3 class="text-base font-semibold text-white truncate" x-text="log.title || '—'"></h3>
              <span class="text-xs text-slate-400" x-text="new Date(log.dive_date).toLocaleDateString()"></span>
            </div>

            <div class="mt-1 text-sm text-slate-300 truncate" x-text="log.site?.name || '—'"></div>

            <div class="mt-3 flex items-center gap-2">
              <span class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-medium
                            bg-white/10 ring-1 ring-white/15 border border-white/10">
                <span class="ml-1 tabular-nums" x-text="log.depth ? `${log.depth} m` : '—'"></span>
              </span>
              <span class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-medium
                            bg-white/10 ring-1 ring-white/15 border border-white/10">
                <span class="ml-1 tabular-nums" x-text="log.duration ? `${log.duration} min` : '—'"></span>
              </span>
            </div>
          </button>
        </template>
      </div>
    </div>
  </template>

  {{-- Empty state --}}
  <template x-if="filteredLogs.length === 0">
    <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-8 text-center">
      <p class="text-slate-300">No dives found for this search.</p>
    </div>
  </template>
</div>
@endauth

    {{-- Guest Message --}}
    @guest
    <div class="mt-12 rounded-2xl border border-white/10 bg-slate-900/60 
                ring-1 ring-white/10 backdrop-blur-md shadow-xl p-8 text-center">
        
        <h2 class="mb-4 text-2xl font-bold text-white flex items-center justify-center gap-2">
            <span>Keep Track of Your Dives</span>
        </h2>

        <p class="mb-6 text-slate-300 leading-relaxed">
            Sign up for a free account to start logging your personal dives. 
            Your logs will be saved, and you’ll be able to add details, photos, and more!
        </p>

        <a href="{{ route('register') }}"
        class="inline-flex items-center justify-center gap-2
                rounded-full px-6 py-3 font-semibold text-white
                bg-gradient-to-r from-cyan-500 to-teal-400
                hover:from-cyan-400 hover:to-teal-300
                ring-1 ring-white/10 border border-white/10
                backdrop-blur-md shadow-lg shadow-cyan-500/10
                transition-all duration-200">
            <span>Sign Up Now</span>
        </a>
    </div>
    @endguest
</section>
@endsection

@push('scripts')
<script>
window.addEventListener('load', () => {
  // Wait for Mapbox if loaded async
  if (typeof mapboxgl === 'undefined') {
    let retries = 10;
    const interval = setInterval(() => {
      if (typeof mapboxgl !== 'undefined') { clearInterval(interval); init(); }
      else if (--retries <= 0) { clearInterval(interval); console.error('❌ Mapbox failed to load.'); }
    }, 150);
  } else {
    init();
  }

  function init() {
    mapboxgl.accessToken = @json(config('services.mapbox.token'));
    const SITES = {!! json_encode($siteCoords) !!};

    // --- visibility helpers + on-demand map creation ---
    function isVisible(el) {
      if (!el) return false;
      const style = getComputedStyle(el);
      return style.display !== 'none' && el.offsetWidth > 0 && el.offsetHeight > 0;
    }

    let mapMobile = null;
    let mapDesktop = null;

    const elMobile  = document.getElementById('personal-dive-map-mobile');
    const elDesktop = document.getElementById('personal-dive-map-desktop');

    // Create only maps whose containers are currently visible
    if (isVisible(elMobile))  mapMobile  = createMap('personal-dive-map-mobile', 9);
    if (isVisible(elDesktop)) mapDesktop = createMap('personal-dive-map-desktop', 8);

    // Keep tiles crisp on container size changes
    const ro = new ResizeObserver(() => {
      requestAnimationFrame(() => {
        mapMobile  && mapMobile.resize();
        mapDesktop && mapDesktop.resize();
      });
    });
    elMobile  && ro.observe(elMobile);
    elDesktop && ro.observe(elDesktop);

    // When layout/breakpoint/orientation changes, create missing maps and resize
    function ensureMaps() {
      if (!mapMobile  && isVisible(elMobile))  mapMobile  = createMap('personal-dive-map-mobile', 9);
      if (!mapDesktop && isVisible(elDesktop)) mapDesktop = createMap('personal-dive-map-desktop', 8);

      setTimeout(() => {
        mapMobile  && mapMobile.resize();
        mapDesktop && mapDesktop.resize();
      }, 50);
    }

    const mq = window.matchMedia('(min-width: 640px)');
    mq.addEventListener ? mq.addEventListener('change', ensureMaps) : mq.addListener(ensureMaps);
    window.addEventListener('orientationchange', ensureMaps);
    window.addEventListener('resize', ensureMaps, { passive: true });

    function createMap(containerId, zoom = 8) {
      const el = document.getElementById(containerId);
      if (!el) return null;

      const map = new mapboxgl.Map({
        container: el,
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [151.2153, -33.8568],
        zoom
      });

      map.on('load', () => addSites(map));
      return map;
    }

    function addSites(map) {
      const bounds = new mapboxgl.LngLatBounds();
      let added = 0, skipped = 0;

      SITES.forEach(site => {
        // Accept multiple key shapes and coerce to Number
        const lat = Number(site.lat ?? site.latitude);
        const lng = Number(site.lng ?? site.longitude ?? site.lon);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
          skipped++;
          return;
        }

        const safeName = escapeHtml(site.name ?? 'Dive Site');

        const popup = new mapboxgl.Popup({ offset: 16, closeButton: false })
          .setHTML(`<div class="text-sm font-semibold text-slate-800">${safeName}</div>`);

        new mapboxgl.Marker({ element: buildElectricMarker(), anchor: 'center' })
          .setLngLat([lng, lat])
          .setPopup(popup)
          .addTo(map);

        bounds.extend([lng, lat]);
        added++;
      });

      // Fit or fallback
      if (added > 0) {
        map.fitBounds(bounds, { padding: 50, maxZoom: 10 });
      } else {
        map.setCenter([151.2153, -33.8568]);
        map.setZoom(6.5);
      }

      // Quick debug summary in console
      console.log(`Sites processed → added: ${added}, skipped: ${skipped}`, SITES);
    }

    // Electric marker element (inner nav color + glow ring)
    function buildElectricMarker() {
      const el = document.createElement('div');
      el.style.width = '14px';
      el.style.height = '14px';
      el.style.borderRadius = '9999px';
      el.style.background = '#0e7490'; // cyan-700 inner
      el.style.border = '2px solid rgba(0,255,255,1)';
      el.style.boxShadow = '0 0 0 3px rgba(0,255,255,0.7), 0 0 10px 2px rgba(0,255,255,0.35)';
      return el;
    }

    function escapeHtml(str) {
      return String(str).replace(/[&<>"']/g, m => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
      }[m]));
    }
  }
});
</script>
@endpush