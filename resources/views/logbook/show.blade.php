@extends('layouts.vizzbud')

@php
  $diveTitle = trim((string)($log->title ?? ''));
  $siteName  = $log->site->name ?? 'Unknown Site';

  $title = ($diveTitle !== '')
    ? "{$diveTitle} · Dive #{$diveNumber} @ {$siteName} | Vizzbud"
    : "Dive #{$diveNumber} @ {$siteName} | Vizzbud";

  $metaDescription = ($diveTitle !== '')
    ? "{$diveTitle} — Dive #{$diveNumber} at {$siteName}: " .
      ($log->depth ?? 'unknown') . "m / " . ($log->duration ?? 'unknown') .
      " min. View your personal dive stats on Vizzbud."
    : "Dive #{$diveNumber} at {$siteName}: " .
      ($log->depth ?? 'unknown') . "m / " . ($log->duration ?? 'unknown') .
      " min. View your personal dive stats on Vizzbud.";

  $hasSite = isset($log->site) && is_numeric($log->site->lng ?? null) && is_numeric($log->site->lat ?? null);
@endphp

@section('title', $title)
@section('meta_description', $metaDescription)

{{-- 🚫 Noindex for personal logs --}}
@section('noindex')
  <meta name="robots" content="noindex, nofollow">
@endsection

{{-- 🌍 Open Graph / Twitter (for sharing) --}}
@section('og_title', $title)
@section('og_description', $metaDescription)
@section('og_image', asset('images/divesites/default.webp'))
@section('twitter_title', $title)
@section('twitter_description', $metaDescription)
@section('twitter_image', asset('images/divesites/default.webp'))

@push('head')
  {{-- Canonical to the main logbook --}}
  <link rel="canonical" href="{{ url('/logbook') }}">

  {{-- Structured Data (for personal activity tracking, not SEO ranking) --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "ExerciseAction",
    "name": "Scuba Dive #{{ $diveNumber }}",
    "description": "{{ Str::limit(strip_tags($metaDescription), 160) }}",
    "agent": {
      "@type": "Person",
      "name": "{{ auth()->user()->name ?? 'Vizzbud Diver' }}"
    },
    "location": {
      "@type": "Place",
      "name": "{{ $siteName }}",
      @if($hasSite)
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": {{ $log->site->lat }},
        "longitude": {{ $log->site->lng }}
      }
      @endif
    },
    "startTime": "{{ optional($log->date)->toIso8601String() }}",
    "endTime": "{{ optional($log->date)->copy()->addMinutes($log->duration ?? 0)->toIso8601String() }}"
  }
  </script>

  {{-- Mapbox CSS & small UI tweak for footer overlap --}}
  <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" />
  <script defer src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>

  <style>
    #dive-site-map-desktop .mapboxgl-ctrl-bottom-right,
    #dive-site-map-desktop .mapboxgl-ctrl-bottom-left,
    #dive-site-map-mobile .mapboxgl-ctrl-bottom-right,
    #dive-site-map-mobile .mapboxgl-ctrl-bottom-left {
        bottom: 3.25rem;
        right: .75rem;
        left: .75rem;
    }
  </style>
@endpush

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-12">

  <div class="flex justify-between items-center mb-8 text-sm">
    <div class="flex gap-2">
      <a href="{{ route('logbook.index') }}"
        class="px-4 py-2 rounded-full font-semibold text-white bg-white/10 border border-white/10 ring-1 ring-white/10 
                hover:bg-white/15 transition backdrop-blur-md">
        Back
      </a>

      @auth
        <a href="{{ route('logbook.edit', $log->id) }}"
          class="px-4 py-2 rounded-full font-semibold text-white
                  bg-gradient-to-r from-cyan-500/90 to-teal-400/90 hover:from-cyan-400 hover:to-teal-300
                  border border-white/10 ring-1 ring-white/10 shadow-md shadow-cyan-500/30 transition">
          Edit
        </a>
      @endauth
    </div>

    <div class="flex gap-3">
      @if($prevId)
        <a href="{{ route('logbook.show', $prevId) }}" class="text-cyan-300 hover:text-cyan-200 transition">← Prev</a>
      @else
        <span class="opacity-30 select-none">← Prev</span>
      @endif
      @if($nextId)
        <a href="{{ route('logbook.show', $nextId) }}" class="text-cyan-300 hover:text-cyan-200 transition">Next →</a>
      @else
        <span class="opacity-30 select-none">Next →</span>
      @endif
    </div>
  </div>

  {{-- Dive summary header --}}
  <div class="mb-8 rounded-2xl bg-gradient-to-br from-slate-800/60 to-slate-900/60 
              border border-white/10 ring-1 ring-white/10 backdrop-blur-xl shadow-xl p-6 text-center sm:text-left">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex-1">
        @if($log->title)
          <h1 class="text-3xl font-extrabold text-white">{{ $log->title }}</h1>
          <p class="text-cyan-300 font-semibold mt-1 tracking-wide">
            {{ $log->site->name ?? 'Unknown Site' }} — Dive #{{ $diveNumber }}
          </p>
        @else
          <h1 class="text-3xl font-extrabold text-white">
            {{ $log->site->name ?? 'Unknown Site' }} — Dive #{{ $diveNumber }}
          </h1>
        @endif

        <p class="text-sm text-slate-400 mt-2 tracking-wide">
          {{ \Carbon\Carbon::parse($log->dive_date)->format('l, F j, Y') }}
        </p>
      </div>

      <div class="flex flex-wrap justify-center sm:justify-end gap-2">
        @foreach (['depth' => 'm', 'duration' => 'min', 'visibility' => 'm vis'] as $key => $unit)
          @if($log->$key)
            <span class="px-3 py-1.5 text-sm font-semibold text-white bg-white/10 border border-white/10 rounded-full backdrop-blur-md">
              {{ $log->$key }} {{ $unit }}
            </span>
          @endif
        @endforeach
      </div>
    </div>
  </div>

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
  </div>

  {{-- Dive Info + Gas / Weight --}}
  @if ($log->buddy || $log->temperature || $log->suit_type || $log->tank_type || $log->air_start || $log->air_end || $log->weight_used || $log->rating)
    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
      
      {{-- DIVE INFO CARD --}}
      <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-gradient-to-br from-slate-800/60 to-slate-900/60 
                  backdrop-blur-xl shadow-xl p-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(0,255,255,0.05),_transparent_60%)] pointer-events-none"></div>

        <h3 class="text-lg font-semibold text-cyan-300 mb-4">
          Dive Info
        </h3>

        <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
          @if($log->buddy)
            <div class="flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 backdrop-blur-md">
              <span><span class="text-slate-400">Buddy:</span> {{ $log->buddy }}</span>
            </div>
          @endif

          @if($log->temperature)
            <div class="flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 backdrop-blur-md">
              <span><span class="text-slate-400">Water:</span> {{ $log->temperature }}°C</span>
            </div>
          @endif

          @if($log->suit_type)
            <div class="flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 backdrop-blur-md">
              <span><span class="text-slate-400">Suit:</span> {{ $log->suit_type }}</span>
            </div>
          @endif

          @if($log->tank_type)
            <div class="flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 backdrop-blur-md">
              <span><span class="text-slate-400">Tank:</span> {{ $log->tank_type }}</span>
            </div>
          @endif
        </div>
      </div>

      {{-- GAS + WEIGHT CARD --}}
      <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-gradient-to-br from-slate-800/60 to-slate-900/60 
                  backdrop-blur-xl shadow-xl p-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_left,_rgba(0,255,255,0.05),_transparent_60%)] pointer-events-none"></div>

        <h3 class="text-lg font-semibold text-cyan-300 mb-4">
          Gas & Weight
        </h3>

        <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
          @if($log->air_start)
            <div class="flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 backdrop-blur-md">
              <span><span class="text-slate-400">Start:</span> {{ intval($log->air_start) }} bar</span>
            </div>
          @endif

          @if($log->air_end)
            <div class="flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 backdrop-blur-md">
              <span><span class="text-slate-400">End:</span> {{ intval($log->air_end) }} bar</span>
            </div>
          @endif

          @if($log->weight_used)
            <div class="flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 backdrop-blur-md">
              <span><span class="text-slate-400">Weight:</span> {{ $log->weight_used }} kg</span>
            </div>
          @endif

          @if($log->rating)
            <div class="flex items-center gap-2 rounded-xl px-3 py-2 bg-white/5 border border-white/10 backdrop-blur-md">
              <span><span class="text-slate-400">Rating:</span> {{ $log->rating }}★</span>
            </div>
          @endif
        </div>
      </div>
    </div>
  @endif

  {{-- Notes --}}
  @if ($log->notes)
    <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 
                bg-gradient-to-br from-slate-800/60 to-slate-900/60 
                backdrop-blur-xl shadow-xl p-6 relative overflow-hidden text-slate-200">
                
      {{-- Radial cyan highlight for visual depth --}}
      <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,_rgba(0,255,255,0.05),_transparent_60%)] pointer-events-none"></div>

      <h3 class="text-lg font-semibold text-cyan-300 mb-3">
        Notes
      </h3>

      <p class="whitespace-pre-line leading-relaxed text-slate-100/90">
        {{ $log->notes }}
      </p>
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