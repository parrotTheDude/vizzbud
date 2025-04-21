@extends('layouts.vizzbud')

@section('content')
<section class="max-w-6xl mx-auto px-6 py-12">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-8">
        <h1 class="text-3xl font-bold text-white">📘 Dive Log</h1>
        @auth
            <a href="{{ route('logbook.create') }}" class="bg-cyan-500 hover:bg-cyan-600 text-white font-semibold px-4 py-2 rounded transition">
                ➕ Log a Dive
            </a>
        @endauth
    </div>

    {{-- Last 3 Dives --}}
    @auth
@if ($recentDives->isNotEmpty())
    <div class="grid md:grid-cols-3 gap-4 mb-12">
        @foreach ($recentDives as $dive)
            <a href="{{ route('logbook.show', $dive->id) }}" class="block bg-slate-800 p-6 rounded-xl shadow space-y-2 hover:bg-slate-700 transition">
                <div class="text-xl font-semibold text-cyan-400">
                    {{ $dive->site->name ?? 'Unknown Site' }}
                </div>
                <div class="text-slate-300 text-sm">
                    Depth: <strong>{{ $dive->depth ?? '—' }}m</strong><br>
                    Duration: <strong>{{ $dive->duration ?? '—' }} min</strong><br>
                    Date: {{ \Carbon\Carbon::parse($dive->dive_date)->format('M j, Y') }}
                </div>
            </a>
        @endforeach
    </div>
@endif
@endauth

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
        <x-log-stat label="Sites Visited" :value="$uniqueSitesVisited" />
    </div>
    @endauth

    {{-- Dive Activity Chart --}}
    <div class="bg-slate-800 rounded-xl p-6 mb-12 shadow" x-data="{ selectedYear: '{{ $selectedYear }}' }" x-init="$watch('selectedYear', value => fetchChart(value))">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-4">
            <h2 class="text-white font-semibold text-lg">📅 Dive Activity</h2>
            <select x-model="selectedYear" class="bg-slate-900 text-white border border-slate-600 px-3 py-1 rounded text-sm">
                @foreach ($availableYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div id="chartContainer">
            @include('logbook._chart')
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-400 mt-4">
            <span>No dives</span>
            <div class="w-4 h-4 bg-slate-700 rounded-sm"></div>
            <div class="w-4 h-4 bg-cyan-200 rounded-sm"></div>
            <div class="w-4 h-4 bg-cyan-400 rounded-sm"></div>
            <div class="w-4 h-4 bg-cyan-500 rounded-sm"></div>
            <span>More dives</span>
        </div>
    </div>

    {{-- Dive Site Map --}}
    <div class="bg-slate-800 rounded-xl p-6 mb-12 shadow">
        <h2 class="text-white font-semibold text-lg mb-4">🗺️ Your Dive Sites</h2>
        <div id="personal-dive-map" class="h-80 w-full rounded-md"></div>
    </div>

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
            return this.logs.filter(log => {
                const site = log.site?.name?.toLowerCase() || '';
                const notes = log.notes?.toLowerCase() || '';
                const depth = log.depth?.toString() || '';
                const duration = log.duration?.toString() || '';
                const query = this.search.toLowerCase();
                return site.includes(query)
                    || notes.includes(query)
                    || depth.includes(query)
                    || duration.includes(query);
            });
        },
        get totalDives() {
            return this.filteredLogs.length;
        }
    }"
    class="mt-12"
>
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h2 class="text-xl font-semibold text-white">🗂️ All Dives</h2>
        <input type="text" x-model="search" placeholder="Search dives..."
            class="bg-slate-900 text-white border border-slate-700 px-3 py-1 rounded text-sm focus:outline-none" />
    </div>

    <template x-if="filteredLogs.length > 0">
        <div class="overflow-x-auto bg-slate-800 rounded-xl shadow">
            <table class="min-w-full text-left text-sm text-slate-200">
                <thead class="bg-slate-900 text-slate-400">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Site</th>
                        <th class="px-4 py-3">Depth</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Date</th>
                    </tr>
                </thead>
                <tbody>
                <template x-for="(log, index) in filteredLogs" :key="log.id">
                    <tr 
                        class="group border-b border-slate-700 hover:bg-slate-700/40 cursor-pointer transition"
                        @click="window.location.href = `/logbook/${log.id}`"
                    >
                        <td class="px-4 py-3" x-text="log.dive_number"></td>
                        <td class="px-4 py-3 font-medium flex items-center justify-between">
                            <span x-text="log.site?.name || '—'"></span>
                            <span class="opacity-0 group-hover:opacity-100 text-cyan-400 transition-opacity">&rarr;</span>
                        </td>
                        <td class="px-4 py-3" x-text="log.depth ? `${log.depth} m` : '—'"></td>
                        <td class="px-4 py-3" x-text="log.duration ? `${log.duration} min` : '—'"></td>
                        <td class="px-4 py-3" x-text="new Date(log.dive_date).toLocaleDateString()"></td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </template>

    <template x-if="filteredLogs.length === 0">
        <div class="text-slate-400 mt-6">No dives found matching your search.</div>
    </template>
</div>
@endauth

    {{-- Guest Message --}}
    @guest
    <div class="bg-slate-800 rounded-xl p-8 text-center shadow mt-8">
        <h2 class="text-2xl font-semibold text-white mb-4">🔒 Keep Track of Your Dives</h2>
        <p class="text-slate-300 mb-6">
            Sign up for a free account to start logging your personal dives. Your logs will be saved, and you’ll be able to add details, photos, and more!
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