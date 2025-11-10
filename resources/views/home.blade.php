@extends('layouts.vizzbud')

@section('title', 'Real-Time Dive Conditions, Site Map & More | Vizzbud')
@section('meta_description', 'Explore live scuba dive site conditions and log your underwater adventures with Vizzbud. Plan your dives smarter with real-time updates.')

@section('head')
  {{-- Open Graph / Twitter meta tags --}}
  <meta property="og:type" content="website">
  <meta property="og:title" content="Vizzbud | Real-Time Dive Conditions, Logs & Stats">
  <meta property="og:description" content="Explore live scuba dive site conditions and log your underwater adventures with Vizzbud.">
  <meta property="og:image" content="{{ asset('og-image.webp') }}">
  <meta property="og:url" content="https://vizzbud.com/">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Vizzbud | Real-Time Dive Conditions, Logs & Stats">
  <meta name="twitter:description" content="Explore live scuba dive site conditions and log your underwater adventures with Vizzbud.">
  <meta name="twitter:image" content="{{ asset('og-image.webp') }}">

  {{-- Preload key visual assets for LCP --}}
  <link rel="preload" as="image" href="{{ asset('vizzbudLogo.webp') }}">
  @if(!empty($featured) && $featured->photos()->where('is_featured', true)->exists())
    <link rel="preload" as="image" href="{{ asset($featured->photos()->where('is_featured', true)->first()->image_path) }}" fetchpriority="high">
  @endif
@endsection

@section('content')

@php
  $status = optional($featured?->latestCondition)->status ?? null;
  $chip = match($status) {
      'green'  => 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30',
      'yellow' => 'bg-amber-500/15 text-amber-300 ring-amber-500/30',
      default  => 'bg-rose-500/15 text-rose-300 ring-rose-500/30',
  };
@endphp

<style>
  @keyframes fadeUp { 0% {opacity:0;transform:translateY(16px)} 100% {opacity:1;transform:translateY(0)} }
  @keyframes pulse { 0%,100%{opacity:.25} 50%{opacity:.35} }
  @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(6px)} }

  .animate-fadeUp { animation: fadeUp .8s ease-out forwards; }
  .animate-float { animation: float 3s ease-in-out infinite; }
  .animate-pulse { animation: pulse 12s ease-in-out infinite; }
</style>

{{-- =========================
     HERO + LOCAL SITES FLOW (Enhanced UX)
========================= --}}
<section class="relative text-center overflow-hidden pt-6 sm:pt-12 pb-10">
  {{-- 🌊 Layered background animation --}}
  <div class="absolute inset-0">
    <img
      src="{{ asset('images/main/bg-waves.webp') }}"
      alt=""
      class="pointer-events-none select-none absolute inset-0 w-full h-full object-cover opacity-25 animate-[pulse_12s_ease-in-out_infinite]"
      loading="eager"
      decoding="async"
    />
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(2,6,23,0.3),rgba(2,6,23,0.9))]"></div>
    <div class="pointer-events-none absolute inset-x-0 -top-24 h-48 bg-gradient-to-b from-cyan-500/15 to-transparent blur-2xl"></div>
  </div>

  <div class="max-w-5xl mx-auto px-6 relative z-10 pt-8 sm:pt-20">
    {{-- 🧭 Hero Text --}}
    <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-3 sm:mb-4 opacity-0 translate-y-4 animate-[fadeUp_0.8s_ease-out_0.1s_forwards]">
      Find Your Next Dive
    </h1>
    <p class="text-white/70 text-base sm:text-lg mb-6 sm:mb-10 leading-snug opacity-0 translate-y-3 animate-[fadeUp_0.8s_ease-out_0.25s_forwards]">
      Search sites, see live conditions, or explore nearby dives.
    </p>

    {{-- 🔍 Search --}}
    <div x-data="siteSearch()" x-init="init()" class="relative max-w-2xl mx-auto z-[9999] mt-2 sm:mt-0">
      <div class="relative">

        <input
          type="text"
          placeholder="Search for a dive site..."
          x-model="query"
          @focus="open = true"
          @click.away="open = false"
          @keydown.arrow-down.prevent="move(1)"
          @keydown.arrow-up.prevent="move(-1)"
          @keydown.enter.prevent="select(focusedIndex)"
          class="w-full rounded-full bg-white text-slate-900 text-base sm:text-lg p-3.5 sm:p-5 px-6 sm:px-8
                border border-cyan-200 shadow-[0_0_20px_rgba(56,189,248,0.25)]
                focus:outline-none focus:ring-4 focus:ring-cyan-300/60 focus:border-cyan-300
                transition-all duration-300 placeholder-slate-400"
        />

        {{-- Dropdown --}}
        <ul
          x-show="open"
          x-transition.opacity
          class="absolute left-0 right-0 top-full mt-3 max-h-80 overflow-y-auto rounded-2xl
                border border-cyan-300/30 bg-[#0b1829]/95
                divide-y divide-white/10 shadow-[0_12px_40px_rgba(0,0,0,0.7)]
                text-left z-[99999] backdrop-blur-2xl overflow-hidden"
        >
          {{-- ✅ Results --}}
          <template x-if="filtered.length">
            <template x-for="(site, index) in filtered" :key="site.id">
              <li
                @click="select(index)"
                @mouseover="focusedIndex = index"
                :class="[
                  'px-5 py-3 sm:py-4 cursor-pointer transition-colors duration-150',
                  index === focusedIndex ? 'bg-cyan-400/15' : 'hover:bg-cyan-400/10'
                ]"
              >
                <div class="font-semibold text-white text-base sm:text-lg leading-tight"
                    x-html="highlight(site.name, query)"></div>
                <div class="text-xs sm:text-sm text-cyan-100/70 mt-0.5"
                    x-text="site.region ? site.region + (site.country ? ', ' + site.country : '') : ''"></div>
              </li>
            </template>
          </template>

          {{-- ❌ No Results --}}
          <template x-if="!filtered.length && !loading && query.length >= 3">
            <li class="px-6 py-5 text-center text-cyan-100/70 text-sm sm:text-base">
              No dive sites found
            </li>
          </template>
        </ul>

        {{-- Loader --}}
        <div x-show="loading" class="absolute right-5 top-4 z-[100000]">
          <svg class="animate-spin h-6 w-6 text-cyan-500" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10"
                    stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
        </div>
      </div>
    </div>

    {{-- 🌊 Glassy Action Section --}}
    <div class="mt-6 sm:mt-8 max-w-5xl mx-auto text-center space-y-6 sm:space-y-8">

      {{-- Two Action Buttons --}}
      <div class="grid grid-cols-2 gap-3 sm:gap-6 w-[95%] sm:w-[85%] mx-auto">
        <a href="{{ route('dive-map.index') }}"
          class="group relative overflow-hidden rounded-2xl p-5 sm:p-8
                  bg-gradient-to-br from-cyan-400/15 via-cyan-600/10 to-slate-900/40
                  backdrop-blur-2xl border border-cyan-300/30
                  hover:from-cyan-400/20 hover:via-cyan-600/15 hover:to-slate-800/40
                  hover:border-cyan-300/50
                  transition-all duration-300 shadow-[inset_0_0_20px_rgba(255,255,255,0.05),0_0_25px_rgba(0,0,0,0.4)]
                  flex flex-col items-center justify-center text-center">
          <div class="p-4 rounded-2xl bg-cyan-500/20 ring-1 ring-cyan-400/40 group-hover:bg-cyan-500/30 transition mb-4">
            <img src="{{ asset('icons/globe.svg') }}" class="w-9 h-9 sm:w-10 sm:h-10 opacity-90 group-hover:scale-110 transition-transform duration-300">
          </div>
          <h3 class="text-white font-semibold text-base sm:text-lg mb-1 tracking-tight">See the Map</h3>
          <p class="hidden sm:block text-white/75 text-xs sm:text-sm mb-1 max-w-[15rem]">Explore local sites and forecasts.</p>
          <span class="text-cyan-300 text-xs sm:text-sm font-medium underline-offset-4 group-hover:underline">Open Map</span>
        </a>

        <a href="{{ route('logbook.index') }}"
          class="group relative overflow-hidden rounded-2xl p-5 sm:p-8
                  bg-gradient-to-br from-indigo-400/15 via-indigo-600/10 to-slate-900/40
                  backdrop-blur-2xl border border-indigo-300/30
                  hover:from-indigo-400/20 hover:via-indigo-600/15 hover:to-slate-800/40
                  hover:border-indigo-300/50
                  transition-all duration-300 shadow-[inset_0_0_20px_rgba(255,255,255,0.05),0_0_25px_rgba(0,0,0,0.4)]
                  flex flex-col items-center justify-center text-center">
          <div class="p-4 rounded-2xl bg-indigo-500/20 ring-1 ring-indigo-400/40 group-hover:bg-indigo-500/30 transition mb-4">
            <img src="{{ asset('icons/diverLogbook.svg') }}" class="w-9 h-9 sm:w-10 sm:h-10 opacity-90 group-hover:scale-110 transition-transform duration-300">
          </div>
          <h3 class="text-white font-semibold text-base sm:text-lg mb-1 tracking-tight">Log Your Dives</h3>
          <p class="hidden sm:block text-white/75 text-xs sm:text-sm mb-1 max-w-[15rem]">Track depth, time, and site stats.</p>
          <span class="text-indigo-300 text-xs sm:text-sm font-medium underline-offset-4 group-hover:underline">Open Logbook</span>
        </a>
      </div>

      {{-- 🌊 Glassy Center CTA --}}
      @guest
      <div class="mt-8 sm:mt-10 relative w-[95%] sm:w-[85%] mx-auto rounded-2xl overflow-hidden
                  bg-gradient-to-br from-cyan-500/10 via-slate-900/40 to-indigo-700/10
                  backdrop-blur-2xl border border-white/10 ring-1 ring-white/10
                  shadow-[inset_0_0_20px_rgba(255,255,255,0.05),0_0_25px_rgba(0,0,0,0.4)]
                  p-6 sm:p-10 text-center">

        {{-- Light accent glow --}}
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(56,189,248,0.15),transparent_70%)] pointer-events-none"></div>

        <div class="relative z-10">
          <h3 class="text-white text-base sm:text-2xl font-semibold mb-3 tracking-tight">
            Create a free account
          </h3>
          <p class="text-white/70 text-xs sm:text-base mb-6 max-w-xl mx-auto leading-relaxed">
            Join free to log dives, track stats, and explore a personalised experience.
          </p>

          <a href="{{ route('register') }}"
            class="inline-flex items-center justify-center gap-1.5 sm:gap-2 rounded-full 
                    px-4 py-2.5 sm:px-7 sm:py-3.5 font-semibold text-white text-sm sm:text-base
                    bg-gradient-to-r from-cyan-400/80 to-teal-400/80 hover:from-cyan-300 hover:to-teal-300
                    border border-white/10 backdrop-blur-xl
                    shadow-[0_0_25px_rgba(56,189,248,0.3)]
                    transition-all duration-300 hover:-translate-y-0.5">
            Create Free Account
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 sm:w-5 sm:h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3" />
            </svg>
          </a>
        </div>
      </div>
      @endguest
    </div>

    <div
  x-data="localSites()"
  x-init="init()"
  class="relative w-[96%] sm:max-w-6xl mx-auto px-2 sm:px-6 mt-8 sm:mt-12 text-center z-20"
>
  {{-- 🧭 Request Permission Box --}}
  <template x-if="!granted && !loading">
    <div class="relative rounded-3xl overflow-hidden p-8 sm:p-10 
                bg-gradient-to-br from-cyan-500/10 via-slate-900/50 to-indigo-700/10
                backdrop-blur-2xl border border-white/10 ring-1 ring-white/10
                shadow-[inset_0_0_25px_rgba(255,255,255,0.05),0_0_40px_rgba(0,0,0,0.45)]
                transition-all duration-300 hover:shadow-[0_0_45px_rgba(56,189,248,0.25)]">

      {{-- Light overlay glow --}}
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(56,189,248,0.15),transparent_70%)] pointer-events-none"></div>

      <div class="relative z-10">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-white mb-3 tracking-tight">
          See Dive Sites Near You
        </h2>
        <p class="text-white/70 text-sm sm:text-base max-w-xl mx-auto mb-8 leading-relaxed">
          Allow location access to discover the top dive sites around you — complete with live
          conditions, distance, and quick links.
        </p>

        <button
          type="button"
          @click="requestLocation()"
          class="inline-flex items-center justify-center gap-2 rounded-full 
                 px-6 sm:px-8 py-3 sm:py-3.5 font-semibold text-white text-sm sm:text-base
                 bg-gradient-to-r from-cyan-500 to-teal-400 hover:from-cyan-400 hover:to-teal-300
                 shadow-[0_0_20px_rgba(56,189,248,0.3)] hover:shadow-[0_0_25px_rgba(56,189,248,0.4)]
                 border border-white/10 backdrop-blur-xl
                 transition-all duration-300 hover:-translate-y-0.5"
          x-text="loading ? 'Getting location…' : 'Enable Location Access'">
        </button>
      </div>
    </div>
  </template>

  {{-- 🌍 Nearby Sites List --}}
  <template x-if="granted && sites.length">
    <div class="relative mt-10 sm:mt-12 rounded-3xl overflow-hidden p-6 sm:p-8
                bg-gradient-to-br from-white/10 via-slate-900/50 to-slate-800/40
                backdrop-blur-2xl border border-white/10 ring-1 ring-white/10
                shadow-[inset_0_0_20px_rgba(255,255,255,0.05),0_0_35px_rgba(0,0,0,0.4)] transition-all duration-300">

      {{-- Accent gradient glow --}}
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(56,189,248,0.1),transparent_80%)] pointer-events-none"></div>

      <div class="relative z-10">
        <h2 class="text-xl sm:text-2xl font-bold text-white mb-8 tracking-tight">
          Nearby Dive Sites
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8 text-slate-200 max-w-5xl mx-auto">
          <template x-for="site in sites" :key="site.id">
            <a :href="`/dive-sites/${site.route.country}/${site.route.state}/${site.route.region}/${site.route.diveSite}`"
              class="group block rounded-2xl overflow-hidden border border-white/10 
                     bg-gradient-to-br from-slate-800/40 to-slate-900/60
                     hover:from-slate-700/40 hover:to-slate-800/60
                     backdrop-blur-xl shadow-[0_4px_25px_rgba(0,0,0,0.4)]
                     hover:shadow-[0_4px_30px_rgba(56,189,248,0.2)]
                     transition-all duration-300">

              <div class="relative w-full h-44 sm:h-48 overflow-hidden">
                <img :src="site.thumb" alt="" 
                     class="absolute inset-0 w-full h-full object-cover 
                            transition-transform duration-500 group-hover:scale-105">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/40 to-transparent"></div>
                <div class="absolute bottom-3 left-3 right-3 text-white text-left">
                  <h3 class="text-base sm:text-lg font-semibold leading-tight line-clamp-1" x-text="site.name"></h3>
                  <p class="text-xs sm:text-sm text-white/70 mt-1">
                    <span x-text="site.region"></span><span x-show="site.state">, </span>
                    <span x-text="site.state"></span><span x-show="site.country">, </span>
                    <span x-text="site.country"></span>
                    <span class="opacity-50 mx-1">•</span>
                    <span x-text="(site.distance_km || 0).toFixed(1) + ' km away'"></span>
                  </p>
                </div>
              </div>
            </a>
          </template>
        </div>
      </div>
    </div>
  </template>
</div>
    </div>
  </section>

{{-- =========================
     FINAL CTA SECTION
========================= --}}
<section class="relative text-center mt-8 sm:mt-12 mb-20 px-6">
  <div class="max-w-5xl mx-auto rounded-3xl border border-white/15 
              bg-gradient-to-br from-cyan-600/10 via-slate-900/40 to-indigo-700/10 
              backdrop-blur-2xl ring-1 ring-white/10 shadow-[0_0_40px_rgba(0,0,0,0.4)] 
              p-10 sm:p-14 overflow-hidden relative">

    {{-- Background glow --}}
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(56,189,248,0.15),transparent_70%)] pointer-events-none"></div>

    <div class="relative z-10">
      <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4 tracking-tight">
        New to Vizzbud?
      </h2>
      <p class="text-white/70 max-w-2xl mx-auto mb-10 text-base sm:text-lg leading-relaxed">
        See how Vizzbud helps divers plan smarter, track every dive, and explore new sites with live conditions and personal stats — 
        all in one platform built by divers, for divers.
      </p>

      {{-- Buttons --}}
      <div class="flex flex-col sm:flex-row justify-center gap-4 sm:gap-6">
        <a href="{{ route('how_it_works') }}"
           class="inline-flex items-center justify-center gap-2 rounded-full px-8 py-3.5 font-semibold text-white
                  bg-white/10 hover:bg-white/20 border border-white/10
                  backdrop-blur-md shadow-md transition-all duration-300 hover:-translate-y-0.5">
          Learn How It Works
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3" />
          </svg>
        </a>

        <a href="{{ route('register') }}"
           class="inline-flex items-center justify-center gap-2 rounded-full px-8 py-3.5 font-semibold text-white
                  bg-gradient-to-r from-cyan-500 to-teal-400
                  hover:from-cyan-400 hover:to-teal-300
                  shadow-lg shadow-cyan-500/30 hover:shadow-cyan-400/40
                  transition-all duration-300 hover:-translate-y-0.5">
          Create a Free Account
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
        </a>
      </div>

      {{-- Footer note --}}
      <p class="mt-10 text-sm text-white/60">
        Built by divers • Powered by <strong>Open-Meteo</strong> and <strong>Mapbox</strong>
      </p>
    </div>
  </div>
</section>

{{-- =========================
     Scripts
========================= --}}
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('siteSearch', siteSearch);
  Alpine.data('localSites', localSites);
});

function siteSearch() {
  return {
    query: '',
    debouncedQuery: '',
    open: false,
    loading: false,
    sites: [],
    focusedIndex: 0,
    _t: null,

    init() {
      this.$watch('query', () => {
        clearTimeout(this._t);
        this._t = setTimeout(() => this.fetchSites(), 300);
      });
    },

    async fetchSites() {
      this.debouncedQuery = (this.query || '').trim();
      if (this.debouncedQuery.length < 3) {
        this.sites = [];
        this.loading = false;
        return;
      }
      this.loading = true;
      try {
        const res = await fetch(`/api/dive-sites/search?query=${encodeURIComponent(this.debouncedQuery)}`);
        const data = await res.json();
        this.sites = data.results ?? [];
      } catch {
        this.sites = [];
      }
      this.loading = false;
      this.open = true;
    },

    get filtered() { return this.sites; },
    move(dir) {
      if (!this.filtered.length) return;
      this.focusedIndex = (this.focusedIndex + dir + this.filtered.length) % this.filtered.length;
    },
    select(i) {
      const s = this.filtered[i];
      if (!s?.route) return;
      const { country, state, region, diveSite } = s.route;
      window.location.href = `/dive-sites/${country}/${state}/${region}/${diveSite}`;
    },
    highlight(label, q) {
      if (!q) return label;
      const i = label.toLowerCase().indexOf(q.toLowerCase());
      if (i === -1) return label;
      return `${label.slice(0,i)}<mark class='bg-cyan-300/40 text-white rounded px-0.5'>${label.slice(i,i+q.length)}</mark>${label.slice(i+q.length)}`;
    },
  };
}

function localSites() {
  return {
    granted: false,
    loading: false,
    sites: [],
    lastCoords: null,
    hasRequested: false,

    async init() {
      // Check if user previously granted or requested location
      const cached = localStorage.getItem('last_coords');
      const rememberedGrant = localStorage.getItem('location_granted');
      this.hasRequested = localStorage.getItem('location_requested') === 'true';

      if (cached) {
        try { this.lastCoords = JSON.parse(cached); } catch {}
      }

      // If already granted before → skip prompt
      if (rememberedGrant === 'true') {
        this.granted = true;
        if (this.lastCoords) await this.fetchNearby(this.lastCoords.lat, this.lastCoords.lng);
        else await this.requestLocation(false);
        return;
      }

      // Otherwise check live browser state
      await this.checkPermissionState();
    },

    async checkPermissionState() {
      if (!navigator.geolocation) return;

      if (navigator.permissions) {
        const perm = await navigator.permissions.query({ name: 'geolocation' });
        await this.handlePermissionState(perm.state);
        perm.onchange = async () => await this.handlePermissionState(perm.state);
      }
    },

    async handlePermissionState(state) {
      if (state === 'granted') {
        this.granted = true;
        localStorage.setItem('location_granted', 'true');
        if (this.lastCoords) await this.fetchNearby(this.lastCoords.lat, this.lastCoords.lng);
        else await this.requestLocation(false);
      } else {
        this.granted = false;
      }
    },

    async requestLocation(showLoading = true) {
      if (showLoading) this.loading = true;
      this.hasRequested = true;
      localStorage.setItem('location_requested', 'true');

      navigator.geolocation.getCurrentPosition(
        async pos => {
          this.granted = true;
          localStorage.setItem('location_granted', 'true');
          await this.handleCoords(pos.coords);
        },
        err => {
          this.loading = false;
          this.granted = false;
          localStorage.removeItem('location_granted');
          console.warn('Location denied:', err.message);
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    },

    async handleCoords(coords) {
      const { latitude: lat, longitude: lng } = coords;
      this.lastCoords = { lat, lng };
      localStorage.setItem('last_coords', JSON.stringify({ lat, lng }));
      await this.fetchNearby(lat, lng);
    },

    async fetchNearby(lat, lng) {
      this.loading = true;
      try {
        const res = await fetch(`/api/dive-sites/nearby?lat=${lat}&lng=${lng}`);
        const data = await res.json();
        this.sites = (data.results || []).slice(0, 3);
      } catch {
        this.sites = [];
      } finally {
        this.loading = false;
      }
    },
  };
}
</script>
@endsection