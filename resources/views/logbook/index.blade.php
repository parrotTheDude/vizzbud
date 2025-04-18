@extends('layouts.vizzbud')

@section('content')
<section class="max-w-6xl mx-auto px-6 py-12">
    {{-- Header + Log Button --}}
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-8">
        <h1 class="text-3xl font-bold text-white">📘 Dive Log</h1>

        @auth
            <a href="{{ route('logbook.create') }}"
               class="bg-cyan-500 hover:bg-cyan-600 text-white font-semibold px-4 py-2 rounded transition">
                ➕ Log a Dive
            </a>
        @endauth
    </div>

    {{-- Stats Grid --}}
    @auth
    <div class="grid md:grid-cols-4 gap-4 mb-12">
        <x-log-stat label="Total Dives" :value="$totalDives" />
        <x-log-stat label="Total Dive Time" :value="$totalHours . 'h ' . $remainingMinutes . 'm'" />
        <x-log-stat label="Deepest Dive" :value="$deepestDive . ' m'" />
        <x-log-stat label="Longest Dive" :value="$longestDive . ' min'" />
        <x-log-stat label="Avg Depth" :value="$averageDepth . ' m'" />
        <x-log-stat label="Avg Duration" :value="$averageDuration . ' min'" />
        <x-log-stat label="Most Dived Site" :value="$siteName" />
    </div>
    @endauth
    
    {{-- Dive Activity --}}
<div 
    class="bg-slate-800 rounded-xl p-6 mb-12 shadow"
    x-data="{ selectedYear: '{{ $selectedYear }}' }" 
    x-init="$watch('selectedYear', value => fetchChart(value))"
>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-4">
        <h2 class="text-white font-semibold text-lg">📅 Dive Activity</h2>

        {{-- Year Dropdown (AJAX via Alpine) --}}
        <select x-model="selectedYear"
                class="bg-slate-900 text-white border border-slate-600 px-3 py-1 rounded text-sm">
            @foreach ($availableYears as $year)
                <option value="{{ $year }}">{{ $year }}</option>
            @endforeach
        </select>
    </div>

    {{-- Dive Activity Chart Container --}}
    <div id="chartContainer">
        @include('logbook._chart')
    </div>

    {{-- Legend --}}
    <div class="flex items-center gap-2 text-xs text-slate-400 mt-4">
        <span>No dives</span>
        <div class="w-4 h-4 bg-slate-700 rounded-sm"></div>
        <div class="w-4 h-4 bg-cyan-200 rounded-sm"></div>
        <div class="w-4 h-4 bg-cyan-400 rounded-sm"></div>
        <div class="w-4 h-4 bg-cyan-500 rounded-sm"></div>
        <span>More dives</span>
    </div>
</div>

    {{-- Dive Sites Map --}}
    <div class="bg-slate-800 rounded-xl p-6 mb-12 shadow">
        <h2 class="text-white font-semibold text-lg mb-4">🗺️ Your Dive Sites</h2>
        <div id="personal-dive-map" class="h-80 w-full rounded-md"></div>
    </div>

    {{-- Dive Table --}}
    @auth
        @if (session('success'))
            <div class="bg-green-600 text-white px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if ($logs->isEmpty())
            <p class="text-slate-400">No dives logged yet.</p>
        @else
            <div class="overflow-x-auto bg-slate-800 rounded-xl shadow">
                <table class="min-w-full text-left text-sm text-slate-200">
                    <thead class="bg-slate-900 text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3">Depth</th>
                            <th class="px-4 py-3">Duration</th>
                            <th class="px-4 py-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr class="border-b border-slate-700 hover:bg-slate-700/40">
                                <td class="px-4 py-3">{{ \Carbon\Carbon::parse($log->dive_date)->format('M j, Y H:i') }}</td>
                                <td class="px-4 py-3 font-medium">{{ $log->site->name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $log->depth ? $log->depth . ' m' : '—' }}</td>
                                <td class="px-4 py-3">{{ $log->duration ? $log->duration . ' min' : '—' }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ Str::limit($log->notes, 40) ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endauth

    {{-- Guest CTA --}}
    @guest
        <div class="bg-slate-800 rounded-xl p-8 text-center shadow mt-8">
            <h2 class="text-2xl font-semibold text-white mb-4">🔒 Keep Track of Your Dives</h2>
            <p class="text-slate-300 mb-6">
                Sign up for a free account to start logging your personal dives.
                Your logs will be saved, and you’ll be able to add details, photos, and more!
            </p>
            <a href="{{ route('register') }}" class="bg-cyan-500 hover:bg-cyan-600 text-white px-6 py-3 rounded-full font-semibold transition">
                🐠 Sign Up Now
            </a>
        </div>
    @endguest
</section>
@endsection

@push('scripts')
<script>
mapboxgl.accessToken = '{{ env('MAPBOX_TOKEN') }}';

const map = new mapboxgl.Map({
    container: 'personal-dive-map',
    style: 'mapbox://styles/mapbox/streets-v11',
    center: [151.2153, -33.8568],
    zoom: 8
});

const sites = {!! json_encode($siteCoords) !!};

sites.forEach(site => {
    new mapboxgl.Marker()
        .setLngLat([site.lng, site.lat])
        .setPopup(new mapboxgl.Popup().setText(site.name))
        .addTo(map);
});

if (sites.length > 0) {
    const bounds = new mapboxgl.LngLatBounds();
    sites.forEach(site => bounds.extend([site.lng, site.lat]));
    map.fitBounds(bounds, { padding: 50 });
}

function fetchChart(year) {
    fetch(`/logbook/chart?year=${year}`)
        .then(res => res.text())
        .then(html => {
            document.getElementById('chartContainer').innerHTML = html;
        });
}
</script>
@endpush