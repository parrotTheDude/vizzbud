@extends('layouts.vizzbud')

@php
  $diveTitle = trim((string)($log->title ?? ''));
  $siteName  = $log->site->name ?? 'Unknown Site';

  $title = ($diveTitle !== '')
    ? "{$diveTitle} · Dive #{$diveNumber} @ {$siteName} | Vizzbud"
    : "Dive #{$diveNumber} @ {$siteName} | Vizzbud";

  $metaDescription = ($diveTitle !== '')
    ? "{$diveTitle} — Details for Dive #{$diveNumber} at {$siteName}: " .
      ($log->depth ?? 'unknown') . "m / " . ($log->duration ?? 'unknown') .
      " min. View your personal dive stats on Vizzbud."
    : "Details for Dive #{$diveNumber} at {$siteName}: " .
      ($log->depth ?? 'unknown') . "m / " . ($log->duration ?? 'unknown') .
      " min. View your personal dive stats on Vizzbud.";

  $hasSite = isset($log->site) && is_numeric($log->site->lng ?? null) && is_numeric($log->site->lat ?? null);
@endphp

@section('title', $title)
@section('meta_description', $metaDescription)

@push('head')
  <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" />
  <script defer src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>

  <style>
    /* Nudge controls up so the footer overlay doesn’t cover them */
    #dive-site-map-desktop .mapboxgl-ctrl-bottom-right,
    #dive-site-map-desktop .mapboxgl-ctrl-bottom-left {
        bottom: 3.25rem; /* match overlay height */
        right: .75rem;   /* slight inset looks nicer */
        left: .75rem;
    }
    #dive-site-map-mobile .mapboxgl-ctrl-bottom-right,
    #dive-site-map-mobile .mapboxgl-ctrl-bottom-left {
        bottom: 3.25rem;
        right: .5rem;
        left: .5rem;
    }
    </style>
@endpush

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-12">

  {{-- Top bar: back + edit --}}
  <div class="mb-6 flex items-center justify-between gap-3">
    <a href="{{ route('logbook.index') }}"
       class="group inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold
              text-white bg-white/10 border border-white/10 ring-1 ring-white/10 backdrop-blur-md
              hover:bg-white/15 transition">
      <span>Back to Log</span>
    </a>

    @auth
      <a href="{{ route('logbook.edit', $log->id) }}"
         class="group inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold
                text-white bg-gradient-to-r from-cyan-500/90 to-teal-400/90
                hover:from-cyan-400/90 hover:to-teal-300/90
                border border-white/10 ring-1 ring-white/10 backdrop-blur-md shadow-lg shadow-cyan-500/20
                hover:-translate-y-0.5 transition">
        <span>Edit Dive</span>
      </a>
    @endauth
  </div>

  {{-- Title + date --}}
  <div class="mb-6 sm:mb-8">
    <h1 class="text-3xl font-extrabold tracking-tight text-white text-center sm:text-left">
      Dive #{{ $diveNumber }} @ {{ $log->site->name ?? 'Unknown Site' }}
    </h1>
    @if(!empty($log->title))
      <p class="mt-1 text-lg font-semibold text-cyan-300 text-center sm:text-left">
        {{ $log->title }}
      </p>
    @endif

    <p class="mt-1 text-sm text-slate-400 text-center sm:text-left">
      {{ \Carbon\Carbon::parse($log->dive_date)->format('M j, Y') }}
    </p>
  </div>

  {{-- Prev / Next --}}
  @if ($prevId || $nextId)
  <div class="mb-6 flex items-center justify-between text-sm">
    <div>
      @if ($prevId)
        <a href="{{ route('logbook.show', $prevId) }}"
           class="inline-flex items-center gap-1 text-cyan-300 hover:text-cyan-200 transition">
          <span>Previous</span>
        </a>
      @else
        <span class="opacity-0 select-none">Previous</span>
      @endif
    </div>
    <div>
      @if ($nextId)
        <a href="{{ route('logbook.show', $nextId) }}"
           class="inline-flex items-center gap-1 text-cyan-300 hover:text-cyan-2 00 transition">
          <span>Next</span>
        </a>
      @else
        <span class="opacity-0 select-none">Next</span>
      @endif
    </div>
  </div>
  @endif

  {{-- Hero + quick stats --}}
  <div class="mb-8 grid grid-cols-1 gap-6">
    {{-- Map card (glassy with overlay) --}}
    <div class="relative overflow-hidden rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl">
      <div class="hidden sm:block h-80 w-full" id="dive-site-map-desktop"></div>
      <div class="sm:hidden h-[240px] w-full" id="dive-site-map-mobile"></div>

      <div class="absolute bottom-0 left-0 right-0 flex items-center justify-between
                  bg-slate-900/65 backdrop-blur-md border-t border-white/10 px-4 py-3">
        <div class="text-white font-semibold">
          Dive Site Location
        </div>
        @if($hasSite)
          <div class="text-xs font-mono text-slate-200">
            {{ number_format($log->site->lat, 5) }}, {{ number_format($log->site->lng, 5) }}
          </div>
        @else
          <div class="text-xs text-slate-300">No coordinates</div>
        @endif
      </div>
    </div>

    {{-- Quick pills: depth / duration / visibility --}}
    @if($log->depth || $log->duration || $log->visibility)
    <div class="grid grid-cols-3 gap-2 sm:gap-3">
        @if($log->depth)
        <span class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-full
                    px-4 py-2 text-sm font-semibold text-white
                    bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10">
            <span>{{ $log->depth }} m</span>
        </span>
        @endif
        @if($log->duration)
        <span class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-full
                    px-4 py-2 text-sm font-semibold text-white
                    bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10">
            <span>{{ $log->duration }} min</span>
        </span>
        @endif
        @if($log->visibility)
        <span class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-full
                    px-4 py-2 text-sm font-semibold text-white
                    bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10">
            <span>{{ $log->visibility }} m vis</span>
        </span>
        @endif
    </div>
    @endif
  </div>

  {{-- Buddy / temp / suit / tank / air / weight as tidy cards --}}
  @if ($log->buddy || $log->temperature || $log->suit_type || $log->tank_type || $log->air_start || $log->air_end || $log->weight_used)
    <div class="mb-10 grid grid-cols-1 sm:grid-cols-2 gap-6">
      {{-- Dive info --}}
      <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-5">
        <h3 class="text-white font-semibold mb-3">Dive Info</h3>
        <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
          @if($log->buddy)
            <div class="inline-flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 ring-1 ring-white/10">
              <span><span class="text-slate-400">Buddy:</span> {{ $log->buddy }}</span>
            </div>
          @endif
          @if($log->temperature)
            <div class="inline-flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 ring-1 ring-white/10">
              <span><span class="text-slate-400">Water:</span> {{ $log->temperature }}°C</span>
            </div>
          @endif
          @if($log->suit_type)
            <div class="inline-flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 ring-1 ring-white/10">
              <span><span class="text-slate-400">Suit:</span> {{ $log->suit_type }}</span>
            </div>
          @endif
          @if($log->tank_type)
            <div class="inline-flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 ring-1 ring-white/10">
              <span><span class="text-slate-400">Tank:</span> {{ $log->tank_type }}</span>
            </div>
          @endif
        </div>
      </div>

        {{-- Gas & Weight --}}
        <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-5">
        <h3 class="text-white font-semibold mb-3">Gas & Weight</h3>

        <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
            @if($log->air_start)
            <div class="inline-flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 ring-1 ring-white/10">
                <span><span class="text-slate-400">Start:</span> {{ intval($log->air_start) }} bar</span>
            </div>
            @endif
            @if($log->air_end)
            <div class="inline-flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 ring-1 ring-white/10">
                <span><span class="text-slate-400">End:</span> {{ intval($log->air_end) }} bar</span>
            </div>
            @endif
            @if($log->weight_used)
            <div class="inline-flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 ring-1 ring-white/10">
                <span><span class="text-slate-400">Weight:</span> {{ $log->weight_used }} kg</span>
            </div>
            @endif

            @if($log->rating)
            <div class="inline-flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 ring-1 ring-white/10">
                <span><span class="text-slate-400">Rating:</span> {{ $log->rating }}★</span>
            </div>
            @endif
        </div>
        </div>
    </div>
  @endif

  {{-- Notes --}}
  @if ($log->notes)
    <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-6 text-slate-200">
      <h3 class="text-white font-semibold mb-2">📝 Notes</h3>
      <p class="whitespace-pre-line leading-relaxed">{{ $log->notes }}</p>
    </div>
  @endif

</section>
@endsection

@push('scripts')
<script>
window.addEventListener('load', () => {
  // Wait for Mapbox (deferred) to be available
  if (typeof mapboxgl === 'undefined') {
    let tries = 10;
    const t = setInterval(() => {
      if (typeof mapboxgl !== 'undefined') { clearInterval(t); initMaps(); }
      else if (--tries <= 0) { clearInterval(t); console.error('❌ Mapbox failed to load.'); }
    }, 150);
  } else {
    initMaps();
  }

  function initMaps() {
    mapboxgl.accessToken = @json(config('services.mapbox.token'));
    const hasSite = @json($hasSite);

    const desktop = document.getElementById('dive-site-map-desktop');
    const mobile  = document.getElementById('dive-site-map-mobile');

    if (desktop) createMap(desktop, 11);
    if (mobile)  createMap(mobile, 10);

    // Resize when switching breakpoints to avoid gray tiles
    const mq = window.matchMedia('(min-width: 640px)');
    const onChange = () => setTimeout(() => {
      desktop && desktop._map && desktop._map.resize();
      mobile  && mobile._map  && mobile._map.resize();
    }, 60);
    mq.addEventListener ? mq.addEventListener('change', onChange) : mq.addListener(onChange);

    function createMap(container, zoom) {
      const map = new mapboxgl.Map({
        container,
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [{{ $log->site->lng ?? 151.2153 }}, {{ $log->site->lat ?? -33.8568 }}],
        zoom
      });
      container._map = map;

      if (!hasSite) return;

      map.on('load', () => {
        const marker = new mapboxgl.Marker({ element: buildMarker(), anchor: 'center' })
          .setLngLat([{{ $log->site->lng ?? 151.2153 }}, {{ $log->site->lat ?? -33.8568 }}])
          .setPopup(new mapboxgl.Popup({ offset: 16, closeButton: false })
            .setHTML(`<div class="text-sm font-semibold text-slate-800">{{ e($log->site->name ?? 'Dive Site') }}</div>`))
          .addTo(map);
      });
    }

    // Electric/glassy marker
    function buildMarker() {
      const el = document.createElement('div');
      el.style.width = '14px';
      el.style.height = '14px';
      el.style.borderRadius = '9999px';
      el.style.background = '#0e7490'; // cyan-700 inner
      el.style.border = '2px solid rgba(0,255,255,1)';
      el.style.boxShadow = '0 0 0 3px rgba(0,255,255,0.65), 0 0 10px 2px rgba(0,255,255,0.35)';
      return el;
    }
  }
});
</script>
@endpush