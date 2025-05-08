@extends('layouts.vizzbud')

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 py-12">

    {{-- Back and Edit Buttons --}}
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('logbook.index') }}"
        class="inline-block bg-slate-700 hover:bg-slate-600 text-cyan-300 font-semibold px-4 py-2 rounded text-sm transition">
            ← Back to Dive Log
        </a>

        @auth
        <a href="{{ route('logbook.edit', $log->id) }}"
        class="inline-block bg-cyan-600 hover:bg-cyan-500 text-white font-semibold px-4 py-2 rounded text-sm transition">
            ✏️ Edit Dive
        </a>
        @endauth
    </div>

    {{-- Title --}}
    <div class="mb-6 sm:mb-8">
        <h1 class="text-3xl font-bold text-white mb-1 text-center sm:text-left">
            Dive #{{ $diveNumber }} @ {{ $log->site->name ?? 'Unknown Site' }}
        </h1>
        <p class="text-slate-400 text-sm text-center sm:text-left">
            {{ \Carbon\Carbon::parse($log->dive_date)->format('M j, Y') }}
        </p>
    </div>

    {{-- Navigation --}}
    @if ($prevId || $nextId)
    <div class="flex flex-row justify-between items-center gap-2 mb-6">
        @if ($prevId)
            <a href="{{ route('logbook.show', $prevId) }}" class="text-sm text-cyan-400 hover:underline">
                &larr; Previous Dive
            </a>
        @else
            <span class="invisible">&larr; Previous Dive</span>
        @endif

        @if ($nextId)
            <a href="{{ route('logbook.show', $nextId) }}" class="text-sm text-cyan-400 hover:underline">
                Next Dive &rarr;
            </a>
        @else
            <span class="invisible">Next Dive &rarr;</span>
        @endif
    </div>
    @endif

    {{-- Depth & Duration --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        @if($log->depth)
            <x-log-stat label="Depth" :value="$log->depth . ' m'" />
        @endif
        @if($log->duration)
            <x-log-stat label="Duration" :value="$log->duration . ' min'" />
        @endif
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

    {{-- Core Stats --}}
    @if ($log->buddy || $log->rating || $log->temperature || $log->visibility)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @if($log->buddy)
            <x-log-stat label="Buddy" :value="$log->buddy" />
        @endif
        @if($log->rating)
            <x-log-stat label="Rating" :value="$log->rating . '★'" />
        @endif
        @if($log->temperature)
            <x-log-stat label="Water Temp" :value="$log->temperature . '°C'" />
        @endif
        @if($log->visibility)
            <x-log-stat label="Visibility" :value="$log->visibility . ' m'" />
        @endif
    </div>
    @endif

    {{-- Gear Info --}}
    @if ($log->air_start || $log->air_end || $log->suit_type || $log->tank_type || $log->weight_used)
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-10">
        @if($log->air_start)
            <x-log-stat label="Air Start" :value="intval($log->air_start) . ' bar'" />
        @endif
        @if($log->air_end)
            <x-log-stat label="Air End" :value="intval($log->air_end) . ' bar'" />
        @endif
        @if($log->suit_type)
            <x-log-stat label="Suit Type" :value="$log->suit_type" />
        @endif
        @if($log->tank_type)
            <x-log-stat label="Tank Type" :value="$log->tank_type" />
        @endif
        @if($log->weight_used)
            <x-log-stat label="Weight Used" :value="$log->weight_used . ' kg'" />
        @endif
    </div>
    @endif

    {{-- Notes --}}
    @if ($log->notes)
    <div class="bg-slate-800 rounded-xl p-6 mb-10 shadow text-slate-300">
        <h2 class="text-white font-semibold text-lg mb-2">📝 Notes</h2>
        <p class="whitespace-pre-line leading-relaxed">{{ $log->notes }}</p>
    </div>
    @endif

</section>
@endsection

@push('scripts')
<script>
mapboxgl.accessToken = @json(config('services.mapbox.token'));

function initMap(containerId, zoom = 10) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const map = new mapboxgl.Map({
        container: containerId,
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [{{ $log->site->lng ?? 151.2153 }}, {{ $log->site->lat ?? -33.8568 }}],
        zoom
    });

    @if($log->site)
    new mapboxgl.Marker()
        .setLngLat([{{ $log->site->lng }}, {{ $log->site->lat }}])
        .setPopup(new mapboxgl.Popup().setText("{{ $log->site->name }}"))
        .addTo(map);
    @endif
}

initMap('dive-site-map-mobile', 9);
initMap('dive-site-map-desktop', 10);
</script>
@endpush