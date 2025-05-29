@extends('layouts.vizzbud')

@section('title', $diveSite->name . ' | Dive Site Info')
@section('meta_description', Str::limit(strip_tags($diveSite->description), 160))

@php use App\Helpers\CompassHelper; @endphp

@push('head')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet" />
@endpush

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 py-12">

    {{-- Title --}}
    <div class="mb-6 sm:mb-8">
        <h1 class="text-3xl font-bold text-white mb-1 text-center sm:text-left">
            {{ $diveSite->name }}
        </h1>
        <p class="text-slate-400 text-sm text-center sm:text-left">
            {{ $diveSite->region }}, {{ $diveSite->country }}
        </p>
    </div>

    {{-- Map --}}
    <div class="mb-8">
        <div class="sm:hidden w-full h-[250px] rounded-md overflow-hidden">
            <div id="dive-site-map-mobile" class="w-full h-full"></div>
        </div>

        <div class="hidden sm:block bg-slate-800 rounded-xl p-6 shadow">
            <h2 class="text-white font-semibold text-lg mb-4">🗺️ Dive Site Location</h2>
            <div id="dive-site-map-desktop" class="h-80 w-full rounded-md"></div>
        </div>
    </div>

    {{-- Description --}}
    <div class="bg-slate-800 rounded-xl p-6 mb-8 shadow text-slate-300">
        <h2 class="text-white font-semibold text-lg mb-2">📍 Description</h2>
        <p class="whitespace-pre-line leading-relaxed">{{ $diveSite->description }}</p>
    </div>

    {{-- Dive Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
        <x-log-stat label="Avg Depth" :value="$diveSite->avg_depth . ' m'" />
        <x-log-stat label="Max Depth" :value="$diveSite->max_depth . ' m'" />
        <x-log-stat label="Entry Type" :value="ucfirst($diveSite->dive_type)" />
        <x-log-stat label="Level" :value="$diveSite->suitability" />
    </div>

    {{-- Current Conditions --}}
    @if($diveSite->latestCondition)
    <div class="bg-slate-800 rounded-xl p-6 mb-10 shadow text-slate-300">
        <h2 class="text-white font-semibold text-lg mb-4">🌊 Current Conditions</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 text-sm">
            <x-log-stat label="Wave Height" :value="$diveSite->latestCondition->wave_height . ' m'" />
            <x-log-stat label="Wave Period" :value="$diveSite->latestCondition->wave_period . ' s'" />
            <x-log-stat label="Wind" :value="number_format($diveSite->latestCondition->wind_speed * 1.94384, 1) . ' kn from ' . CompassHelper::fromDegrees($diveSite->latestCondition->wind_direction)" />
            <x-log-stat label="Water Temp" :value="$diveSite->latestCondition->water_temperature . ' °C'" />
            <x-log-stat label="Air Temp" :value="$diveSite->latestCondition->air_temperature . ' °C'" />
        </div>
        <p class="text-xs text-slate-500 text-right mt-2">
            Last updated: {{ $diveSite->latestCondition->retrieved_at->diffForHumans() }}
        </p>
    </div>
    @endif

</section>
@endsection

@push('scripts')
<script src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>
<script>
mapboxgl.accessToken = @json(config('services.mapbox.token'));

function initMap(containerId, zoom = 13) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const map = new mapboxgl.Map({
        container: containerId,
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [{{ $diveSite->lng }}, {{ $diveSite->lat }}],
        zoom,
        interactive: false
    });

    new mapboxgl.Marker()
        .setLngLat([{{ $diveSite->lng }}, {{ $diveSite->lat }}])
        .addTo(map);
}

initMap('dive-site-map-mobile', 12);
initMap('dive-site-map-desktop', 13);
</script>
@endpush