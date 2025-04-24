@extends('layouts.vizzbud')

@section('content')
<div class="relative h-[calc(100vh-64px)] w-full" x-data="{ filtersOpen: false }">
    {{-- Top Search Bar --}}
    <div class="absolute top-4 left-4 right-4 sm:left-6 sm:right-auto sm:max-w-sm z-20"
         x-data="diveSiteSelect({ sites: @js($sites), map: map })">
        <input
            type="text"
            x-model="query"
            @focus="open = true"
            @click.away="open = false"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="select(focusedIndex)"
            placeholder="Search dive sites..."
            class="w-full rounded p-2 text-black shadow"
        >
        <ul x-show="open && filtered.length"
            class="absolute z-20 bg-white text-black rounded shadow w-full mt-1 max-h-60 overflow-y-auto border border-gray-300"
            x-transition>
            <template x-for="(site, index) in filtered" :key="site.id">
                <li
                    :class="{
                        'bg-cyan-100': index === focusedIndex,
                        'px-4 py-2 cursor-pointer': true
                    }"
                    @click="select(index)"
                    @mouseover="focusedIndex = index"
                    x-text="site.name"
                ></li>
            </template>
        </ul>
    </div>

    {{-- Map --}}
    <div id="map" class="w-full h-full"></div>

    {{-- Toggle Filters Button --}}
    <button @click="filtersOpen = !filtersOpen"
        class="absolute bottom-4 left-4 z-20 bg-white text-slate-800 p-2 rounded-full shadow hover:bg-slate-100 focus:outline-none">
        ⚙️
    </button>

    {{-- Recenter Button --}}
    <button @click="map.flyTo({ center: [userLng, userLat], zoom: 11 })"
        class="absolute bottom-4 right-4 z-20 bg-white text-slate-800 p-2 rounded-full shadow hover:bg-slate-100 focus:outline-none">
        🎯
    </button>

    {{-- Bottom Drawer Filters --}}
    <div x-show="filtersOpen" x-transition
        class="absolute bottom-16 left-4 right-4 sm:left-auto sm:right-auto sm:w-[340px] bg-white text-black rounded-xl shadow-lg p-4 space-y-4 text-sm z-20">

        {{-- Filter: Suitability --}}
        <select id="filterSuitability" class="w-full border border-slate-300 px-3 py-2 rounded">
            <option value="">All Levels</option>
            <option value="Open Water">Open Water</option>
            <option value="Advanced">Advanced</option>
            <option value="Deep">Deep</option>
        </select>

        {{-- Filter: Dive Type --}}
        <select id="filterType" class="w-full border border-slate-300 px-3 py-2 rounded">
            <option value="">All Types</option>
            <option value="shore">Shore</option>
            <option value="boat">Boat</option>
        </select>

        {{-- Condition Legend --}}
        <div class="text-xs text-slate-700">
            <strong>Wave Conditions</strong>
            <div class="flex items-center gap-2 mt-1">
                <div class="w-3 h-3 bg-[#00ff88] rounded-full"></div> Good
                <div class="w-3 h-3 bg-[#ffcc00] rounded-full"></div> Caution
                <div class="w-3 h-3 bg-[#ff4444] rounded-full"></div> Poor
            </div>
        </div>
    </div>
</div>
@endsection

@push('head')
<script src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>
@endpush

@push('scripts')
<script>
mapboxgl.accessToken = '{{ env('MAPBOX_TOKEN') }}';

const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v11',
    center: [151.2153, -33.8568],
    zoom: 10
});

const sites = @json($sites);

// Build GeoJSON
function buildGeojsonFeatures(list) {
    return list.map(site => ({
        type: 'Feature',
        properties: {
            id: site.id,
            name: site.name,
            description: site.description,
            suitability: site.suitability,
            diveType: site.dive_type,
            waveHeight: site.conditions?.waveHeight?.noaa ?? null,
            wavePeriod: site.conditions?.wavePeriod?.noaa ?? null,
            waveDirection: site.conditions?.waveDirection?.noaa ?? null,
            waterTemp: site.conditions?.waterTemperature?.noaa ?? null,
            windSpeed: site.conditions?.windSpeed?.noaa ?? null,         
            windDirection: site.conditions?.windDirection?.noaa ?? null, 
            updatedAt: site.retrieved_at ?? null,
            maxDepth: site.max_depth,
            avgDepth: site.avg_depth
        },
        geometry: {
            type: 'Point',
            coordinates: [site.lng, site.lat]
        }
    }));
}

// Haversine
function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function degreesToCompass(deg) {
    const directions = ['N','NE','E','SE','S','SW','W','NW'];
    return directions[Math.round(deg / 45) % 8];
}

function formatUtcToLocal(utcString) {
    if (!utcString) return 'N/A';

    const utcDate = new Date(utcString + 'Z'); // 'Z' means UTC explicitly

    if (isNaN(utcDate.getTime())) return 'Invalid date';

    return utcDate.toLocaleString(undefined, {
        timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

// Add markers
let geoData = buildGeojsonFeatures(sites);

function renderSites(filtered) {
    if (map.getSource('dive-sites')) {
        map.getSource('dive-sites').setData({
            type: 'FeatureCollection',
            features: filtered
        });
    } else {
        map.addSource('dive-sites', {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: filtered
            }
        });

        map.addLayer({
            id: 'dive-site-points',
            type: 'circle',
            source: 'dive-sites',
            paint: {
                'circle-radius': 8,
                'circle-color': [
                    'case',
                    ['<', ['get', 'waveHeight'], 1], '#00ff88',
                    ['<', ['get', 'waveHeight'], 2], '#ffcc00',
                    '#ff4444'
                ],
                'circle-stroke-width': 2,
                'circle-stroke-color': '#ffffff'
            }
        });

        map.on('click', 'dive-site-points', (e) => {
            const p = e.features[0].properties;

            const html = `
                <div class="text-slate-800 text-sm">
                    <strong class="text-sky-500">${p.name}</strong><br>
                    ${p.description || ''}
                    <hr class="my-2">
                    📏 <strong>Depth:</strong> ${p.avgDepth ?? '?'}m avg / ${p.maxDepth ?? '?'}m max<br>
                    🚶 <strong>Entry:</strong> ${p.diveType ?? '—'}<br>
                    🎓 <strong>Level:</strong> ${p.suitability ?? '—'}<br>
                    🌊 <strong>Wave:</strong> ${p.waveHeight ?? 'N/A'} m @ ${p.wavePeriod ?? '?'} s<br>
                    🧭 <strong>Direction:</strong> ${p.waveDirection ? degreesToCompass(p.waveDirection) : '—'}<br>
                    🌡️ <strong>Water Temp:</strong> ${p.waterTemp ?? '—'}°C<br>
                    🌬️ <strong>Wind:</strong> ${typeof p.windSpeed === 'number'
                        ? (p.windSpeed * 1.94384).toFixed(1) + ' kn'
                        : 'N/A'} from ${p.windDirection ? degreesToCompass(p.windDirection) : '—'}<br>
                    📅 <small>Updated: ${p.updatedAt ? formatUtcToLocal(p.updatedAt) : '—'}</small>
                </div>
            `;

            new mapboxgl.Popup().setLngLat(e.lngLat).setHTML(html).addTo(map);
        });
    }
}

// Load & watch filters
map.on('load', () => {
    renderSites(geoData);

    const $search = document.getElementById('siteSearch');
    const $level = document.getElementById('filterSuitability');
    const $type = document.getElementById('filterType');

    function filterSites() {
        const query = $search.value.toLowerCase();
        const level = $level.value;
        const type = $type.value;

        const filtered = geoData.filter(f =>
            f.properties.name.toLowerCase().includes(query) &&
            (level === '' || f.properties.suitability === level) &&
            (type === '' || f.properties.diveType === type)
        );

        renderSites(filtered);
    }

    [$search, $level, $type].forEach(input => input.addEventListener('input', filterSites));
});

// Auto-center to user
navigator.geolocation.getCurrentPosition(position => {
    const userLat = position.coords.latitude;
    const userLng = position.coords.longitude;

    new mapboxgl.Marker({ color: '#0ea5e9' })
        .setLngLat([userLng, userLat])
        .setPopup(new mapboxgl.Popup().setText("You're here"))
        .addTo(map);
});

function diveSiteSelect({ sites, map }) {
    return {
        sites,
        query: '',
        open: false,
        selectedId: null,
        focusedIndex: 0,

        get filtered() {
            const q = this.query.toLowerCase();
            return this.sites.filter(site => site.name.toLowerCase().includes(q));
        },

        move(direction) {
            if (!this.filtered.length) return;
            this.focusedIndex = (this.focusedIndex + direction + this.filtered.length) % this.filtered.length;
        },

        select(index) {
            const site = this.filtered[index];
            if (site) {
                this.query = site.name;
                this.selectedId = site.id;
                this.open = false;

                map.flyTo({ center: [site.lng, site.lat], zoom: 12 });

                // Build full popup like the click event
                const props = {
                    name: site.name,
                    description: site.description,
                    suitability: site.suitability,
                    diveType: site.dive_type,
                    waveHeight: site.conditions?.waveHeight?.noaa ?? null,
                    wavePeriod: site.conditions?.wavePeriod?.noaa ?? null,
                    waveDirection: site.conditions?.waveDirection?.noaa ?? null,
                    waterTemp: site.conditions?.waterTemperature?.noaa ?? null,
                    updatedAt: site.retrieved_at ?? null,
                    maxDepth: site.max_depth,
                    avgDepth: site.avg_depth
                };

                const html = `
                    <div class="text-slate-800 text-sm leading-snug font-medium">
                        <strong class="text-sky-500">${props.name}</strong><br>
                        <p class="mb-1">${props.description ?? ''}</p>
                        <hr class="my-2 border-gray-300">
                        📏 <strong>Depth:</strong> ${props.avgDepth ?? '?'}m avg / ${props.maxDepth ?? '?'}m max<br>
                        🚶 <strong>Entry:</strong> ${props.diveType ? props.diveType.charAt(0).toUpperCase() + props.diveType.slice(1) : 'Unknown'}<br>
                        🎓 <strong>Level:</strong> ${props.suitability ?? 'N/A'}<br>
                        <hr class="my-2 border-gray-300">
                        🌡️ <strong>Water Temp:</strong> ${props.waterTemp ?? 'N/A'} °C<br>
                        🌊 <strong>Wave Height:</strong> ${props.waveHeight ?? 'N/A'} m<br>
                        ⏱️ <strong>Set Time:</strong> ${typeof props.wavePeriod === 'number' ? props.wavePeriod.toFixed(1) : 'N/A'} s<br>
                        🧭 <strong>Direction:</strong> ${props.waveDirection ? degreesToCompass(props.waveDirection) : 'N/A'}<br>
                        <hr class="my-2 border-gray-300">
                        📅 <em class="text-xs text-slate-600">Updated: ${formatUtcToLocal(props.updatedAt)}</em>
                    </div>
                `;

                new mapboxgl.Popup()
                    .setLngLat([site.lng, site.lat])
                    .setHTML(html)
                    .addTo(map);
            }
        }
    };
}

let userLat = null;
let userLng = null;

navigator.geolocation.getCurrentPosition(position => {
    userLat = position.coords.latitude;
    userLng = position.coords.longitude;

    new mapboxgl.Marker({ color: '#0ea5e9' })
        .setLngLat([userLng, userLat])
        .setPopup(new mapboxgl.Popup().setText("You're here"))
        .addTo(map);
});
</script>
@endpush