@extends('layouts.vizzbud')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    {{-- Title --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">
            Dive #{{ $diveNumber }} @ {{ $log->site->name ?? 'Unknown Site' }}
        </h1>
        <p class="text-slate-400 text-sm">
            {{ \Carbon\Carbon::parse($log->dive_date)->format('M j, Y') }}
        </p>
    </div>
    @if ($prevId || $nextId)
    <div class="flex justify-between items-center mb-6">
        @if ($prevId)
            <a href="{{ route('logbook.show', $prevId) }}"
               class="text-sm text-cyan-400 hover:underline">&larr; Previous Dive</a>
        @else
            <span></span>
        @endif

        @if ($nextId)
            <a href="{{ route('logbook.show', $nextId) }}"
               class="text-sm text-cyan-400 hover:underline">Next Dive &rarr;</a>
        @else
            <span></span>
        @endif
    </div>
@endif

    {{-- Depth + Duration --}}
    <div class="grid md:grid-cols-2 gap-4 mb-8">
        <x-log-stat label="Depth" :value="($log->depth ?? '—') . ' m'" />
        <x-log-stat label="Duration" :value="($log->duration ?? '—') . ' min'" />
    </div>

    {{-- Extra Dive Stats --}}
<div class="grid md:grid-cols-4 gap-4 mb-6">
    <x-log-stat label="Buddy" :value="$log->buddy ?? '—'" />
    <x-log-stat label="Rating" :value="$log->rating ? $log->rating . '★' : '—'" />
    <x-log-stat label="Water Temp" :value="$log->temperature ? $log->temperature . '°C' : '—'" />
    <x-log-stat label="Visibility" :value="$log->visibility ? $log->visibility . ' m' : '—'" />
</div>

<div class="grid md:grid-cols-5 gap-4 mb-12">
    <x-log-stat label="Air Start" :value="$log->air_start ? $log->air_start . ' bar' : '—'" />
    <x-log-stat label="Air End" :value="$log->air_end ? $log->air_end . ' bar' : '—'" />
    <x-log-stat label="Suit Type" :value="$log->suit_type ?? '—'" />
    <x-log-stat label="Tank Type" :value="$log->tank_type ?? '—'" />
    <x-log-stat label="Weight Used" :value="$log->weight_used ?? '—'" />
</div>

    {{-- Notes --}}
    @if ($log->notes)
        <div class="bg-slate-800 rounded-xl p-6 mb-12 shadow text-slate-300">
            <h2 class="text-white font-semibold text-lg mb-2">📝 Notes</h2>
            <p class="whitespace-pre-line">{{ $log->notes }}</p>
        </div>
    @endif

    {{-- Map --}}
    <div class="bg-slate-800 rounded-xl p-6 shadow">
        <h2 class="text-white font-semibold text-lg mb-4">🗺️ Dive Site Location</h2>
        <div id="dive-site-map" class="h-80 w-full rounded-md"></div>
    </div>
</section>
@endsection

@push('scripts')
<script>
mapboxgl.accessToken = '{{ env('MAPBOX_TOKEN') }}';

const map = new mapboxgl.Map({
    container: 'dive-site-map',
    style: 'mapbox://styles/mapbox/streets-v11',
    center: [{{ $log->site->lng ?? 151.2153 }}, {{ $log->site->lat ?? -33.8568 }}],
    zoom: 10
});

@if($log->site)
new mapboxgl.Marker()
    .setLngLat([{{ $log->site->lng }}, {{ $log->site->lat }}])
    .setPopup(new mapboxgl.Popup().setText("{{ $log->site->name }}"))
    .addTo(map);
@endif
</script>
@endpush