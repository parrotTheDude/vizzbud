@extends('layouts.vizzbud')

@section('title', $diveSite->name . ' | Dive Site Info')
@section('meta_description', Str::limit(strip_tags($diveSite->description), 160))

@php
  use App\Helpers\CompassHelper;

  $status = strtolower(optional($diveSite->latestCondition)->status ?? '');
  $chipClasses = match($status) {
      'green'  => 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/30',
      'yellow' => 'bg-amber-500/15 text-amber-300 ring-amber-400/30',
      'red'    => 'bg-rose-500/15 text-rose-300 ring-rose-400/30',
      default  => 'bg-slate-500/15 text-slate-300 ring-slate-400/30',
  };
@endphp

@push('head')
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet" />
@endpush

@section('content')
<section class="max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-12">

  {{-- Header --}}
  <header class="mb-6 sm:mb-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="text-center sm:text-left">
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $diveSite->name }}</h1>
        <p class="text-slate-400 text-sm mt-1">{{ $diveSite->region }}, {{ $diveSite->country }}</p>
      </div>

      <div class="flex items-center justify-center sm:justify-end gap-2">
        <span class="text-xs text-white/60">Dive Status:</span>
        <span class="rounded-full px-3 py-1 text-xs font-semibold tabular-nums ring-1 {{ $chipClasses }}">
          {{ strtoupper($diveSite->latestCondition->status ?? 'N/A') }}
        </span>
      </div>
    </div>

{{-- Compact pill stats under the chip --}}
<div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2 max-w-sm sm:max-w-none mx-auto sm:mx-0">
  <span class="flex items-center justify-center gap-1.5 
               rounded-full px-3 py-1 text-[11px] font-medium
               bg-white/10 backdrop-blur-md ring-1 ring-white/15 border border-white/10 text-slate-200">
    <img src="/icons/{{ $diveSite->dive_type === 'boat' ? 'boat' : 'beach' }}.svg" 
         class="w-3.5 h-3.5 invert" alt="Type">
    {{ ucfirst($diveSite->dive_type) }}
  </span>

  <span class="flex items-center justify-center gap-1.5 
               rounded-full px-3 py-1 text-[11px] font-medium
               bg-white/10 backdrop-blur-md ring-1 ring-white/15 border border-white/10 text-slate-200">
    <img src="/icons/diver.svg" class="w-3.5 h-3.5 invert" alt="Level">
    {{ $diveSite->suitability }}
  </span>

  <span class="flex items-center justify-center gap-1.5 
               rounded-full px-3 py-1 text-[11px] font-medium
               bg-white/10 backdrop-blur-md ring-1 ring-white/15 border border-white/10 text-slate-200">
    <img src="/icons/pool-depth.svg" class="w-3.5 h-3.5 invert" alt="Avg Depth">
    Avg {{ number_format($diveSite->avg_depth, 0) }} m
  </span>

  <span class="flex items-center justify-center gap-1.5 
               rounded-full px-3 py-1 text-[11px] font-medium
               bg-white/10 backdrop-blur-md ring-1 ring-white/15 border border-white/10 text-slate-200">
    <img src="/icons/under-water.svg" class="w-3.5 h-3.5 invert" alt="Max Depth">
    Max {{ number_format($diveSite->max_depth, 0) }} m
  </span>
</div>
  </header>

{{-- Hero Map Card --}}
<div class="mb-8 grid grid-cols-1 lg:grid-cols-5 gap-6">
  <div class="relative lg:col-span-3 rounded-2xl overflow-hidden
              bg-white/12 backdrop-blur-xl border border-white/20 ring-1 ring-white/10 shadow-xl">

    {{-- Map --}}
    <div id="dive-site-map-desktop" class="hidden sm:block h-80 w-full"></div>
    <div id="dive-site-map-mobile"  class="sm:hidden h-[240px] w-full"></div>

    {{-- Overlay footer (glassy, tinted, above mapbox UI) --}}
    <div class="absolute bottom-0 left-0 right-0 z-20
                px-5 py-3 
                bg-cyan-900/50 backdrop-blur-md
                border-t border-cyan-400/20
                flex items-center justify-between">
      <h2 class="text-white font-semibold text-base sm:text-lg">Dive Site Location</h2>
      <div class="text-xs font-mono text-slate-200">
        {{ number_format($diveSite->lat, 5) }}, {{ number_format($diveSite->lng, 5) }}
      </div>
    </div>
  </div>

    {{-- Quick Summary Card --}}
    <div class="lg:col-span-2 rounded-2xl p-5 bg-white/10 backdrop-blur-xl border border-white/15 ring-1 ring-white/10 shadow-xl">
      <h3 class="text-white font-semibold mb-3">Summary</h3>
      <p class="text-sm text-slate-300 leading-relaxed line-clamp-6">{{ $diveSite->description ?: 'No description yet.' }}</p>
      <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
        <a href="{{ route('dive-sites.index', ['lat' => $diveSite->lat, 'lng' => $diveSite->lng, 'zoom' => 12]) }}"
           class="rounded-xl px-3 py-2 bg-cyan-500/90 hover:bg-cyan-500 text-white font-semibold text-center shadow">
          View on Map
        </a>
        <a href="https://www.google.com/maps?q={{ $diveSite->lat }},{{ $diveSite->lng }}" target="_blank"
           class="rounded-xl px-3 py-2 bg-white/15 hover:bg-white/20 text-slate-100 font-semibold text-center border border-white/20 shadow">
          Directions
        </a>
      </div>
    </div>
  </div>

    {{-- Current Conditions (full-width pill grid) --}}
    @if($diveSite->latestCondition)
    @php $c = $diveSite->latestCondition; @endphp
    <section class="rounded-2xl p-6 mb-10 
                    bg-white/12 backdrop-blur-xl border border-white/20 ring-1 ring-white/10 shadow-xl">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
        <h2 class="text-white font-semibold text-lg">Current Conditions</h2>
        <span class="text-xs text-slate-400">Updated {{ $c->retrieved_at->diffForHumans() }}</span>
        </div>

        {{-- Grid pills --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-sm">
        {{-- Swell --}}
        <div class="flex items-center justify-between px-3 py-2 rounded-full 
                    bg-white/10 border border-white/15 text-slate-200">
            <span class="flex items-center gap-1.5">
            <img src="/icons/wave.svg" class="w-4 h-4 invert" alt="Swell">
            Swell
            </span>
            <strong>{{ $c->wave_height ? number_format($c->wave_height, 1) : '–' }} m</strong>
        </div>

        {{-- Period --}}
        <div class="flex items-center justify-between px-3 py-2 rounded-full 
                    bg-white/10 border border-white/15 text-slate-200">
            <span class="flex items-center gap-1.5">
            <img src="/icons/tools-and-utensils.svg" class="w-4 h-4 invert" alt="Period">
            Period
            </span>
            <strong>{{ $c->wave_period ? number_format($c->wave_period, 0) : '–' }} s</strong>
        </div>

        {{-- Swell Dir --}}
        <div class="flex items-center justify-between px-3 py-2 rounded-full 
                    bg-white/10 border border-white/15 text-slate-200">
            <span class="flex items-center gap-1.5">
            <img src="/icons/compass.svg" class="w-4 h-4 invert" alt="Direction">
            Dir
            </span>
            <span class="flex items-center gap-1.5 font-semibold">
            {{ $c->wave_direction !== null ? CompassHelper::fromDegrees($c->wave_direction) : '—' }}
            <img src="/icons/arrow.svg" class="w-3 h-3 invert"
                style="transform: rotate({{ (($c->wave_direction ?? 0) + 90) }}deg)" alt="arrow">
            </span>
        </div>

        {{-- Wind --}}
        <div class="flex items-center justify-between px-3 py-2 rounded-full 
                    bg-white/10 border border-white/15 text-slate-200">
            <span class="flex items-center gap-1.5">
            <img src="/icons/wind.svg" class="w-4 h-4 invert" alt="Wind">
            Wind
            </span>
            <span class="font-semibold">
            {{ $c->wind_speed ? number_format($c->wind_speed * 1.94384, 0) : '–' }} kn
            </span>
        </div>

        {{-- Water --}}
        <div class="flex items-center justify-between px-3 py-2 rounded-full 
                    bg-white/10 border border-white/15 text-slate-200">
            <span class="flex items-center gap-1.5">
            <img src="/icons/temperature.svg" class="w-4 h-4 invert" alt="Water">
            Water
            </span>
            <strong>{{ $c->water_temperature ? number_format($c->water_temperature, 1) : '–' }} °C</strong>
        </div>

        {{-- Air --}}
        <div class="flex items-center justify-between px-3 py-2 rounded-full 
                    bg-white/10 border border-white/15 text-slate-200">
            <span class="flex items-center gap-1.5">
            <img src="/icons/temperature.svg" class="w-4 h-4 invert" alt="Air">
            Air
            </span>
            <strong>{{ $c->air_temperature ? number_format($c->air_temperature, 1) : '–' }} °C</strong>
        </div>
        </div>
    </section>
    @endif

  {{-- Rich Description / Notes --}}
  <section class="rounded-2xl p-6 mb-6 bg-white/8 backdrop-blur-xl border border-white/15 ring-1 ring-white/10 shadow-xl">
    <h3 class="text-white font-semibold text-lg mb-2">About this site</h3>
    <p class="text-slate-300 leading-relaxed whitespace-pre-line">
      {{ $diveSite->description ?: 'No description provided yet.' }}
    </p>
  </section>

</section>
@endsection

@push('scripts')
<script src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>
<script>
  mapboxgl.accessToken = @json(config('services.mapbox.token'));

  function statusColor(status) {
    switch ((status || '').toLowerCase()) {
      case 'green':  return 'rgba(0, 255, 55, 1)'; // electric green
      case 'yellow': return 'rgba(251, 255, 0, 1)'; // neon yellow
      case 'red':    return 'rgba(255, 0, 0, 1)';   // electric red
      default:       return 'rgba(156, 163, 175, 0.9)'; // slate
    }
  }

  function buildMarkerEl(status) {
    const ring = statusColor(status);
    const el = document.createElement('div');
    el.style.width = '16px';
    el.style.height = '16px';
    el.style.borderRadius = '9999px';
    el.style.background = '#0e7490'; // nav cyan-700
    el.style.boxShadow = `0 0 0 3px ${ring}, 0 0 8px 2px ${ring.replace('1)', '0.45)')}`;
    el.style.border = `2px solid ${ring}`;
    return el;
  }

  function initMap(containerId, zoom) {
    const el = document.getElementById(containerId);
    if (!el) return;

    const map = new mapboxgl.Map({
      container: containerId,
      style: 'mapbox://styles/mapbox/streets-v11',
      center: [{{ $diveSite->lng }}, {{ $diveSite->lat }}],
      zoom,
      interactive: false
    });

    const markerEl = buildMarkerEl(@json(optional($diveSite->latestCondition)->status));
    new mapboxgl.Marker({ element: markerEl, anchor: 'center' })
      .setLngLat([{{ $diveSite->lng }}, {{ $diveSite->lat }}])
      .addTo(map);
  }

  initMap('dive-site-map-mobile', 12);
  initMap('dive-site-map-desktop', 13);
</script>
@endpush