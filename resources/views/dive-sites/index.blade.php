@extends('layouts.vizzbud')

@section('content')
<div class="relative h-[calc(100vh-64px)] w-full">
    <div id="map" class="w-full h-full"></div>
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

// Convert dive sites into GeoJSON
function buildGeojsonFeatures(list) {
    return list.map(site => ({
        type: 'Feature',
        properties: {
            name: site.name,
            description: site.description,
            waveHeight: site.conditions?.waveHeight?.noaa ?? null,
            wavePeriod: site.conditions?.wavePeriod?.noaa ?? null,
            waveDirection: site.conditions?.waveDirection?.noaa ?? null,
            waterTemp: site.conditions?.waterTemperature?.noaa ?? null,
            updatedAt: site.retrieved_at ?? null,
            maxDepth: site.max_depth ?? null,
            avgDepth: site.avg_depth ?? null,
            diveType: site.dive_type ?? null,
            suitability: site.suitability ?? null,
        },
        geometry: {
            type: 'Point',
            coordinates: [site.lng, site.lat]
        }
    }));
}

// Haversine formula for distance
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
    const directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
    return directions[Math.round(deg / 45) % 8];
}

function addSiteMarkers(features) {
    map.addSource('dive-sites', {
        type: 'geojson',
        data: {
            type: 'FeatureCollection',
            features: features
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
    const props = e.features[0].properties;
    console.log('Dive site clicked:', props);
        const html = `
            <div class="text-slate-800 text-sm leading-snug font-medium">
                <strong class="text-sky-500">${props.name}</strong><br>
                <p class="mb-1">${props.description}</p>
                <hr class="my-2 border-gray-300">
                📏 <strong>Depth:</strong> ${props.avgDepth ?? '?'}m avg / ${props.maxDepth ?? '?'}m max<br>
                🚶 <strong>Entry:</strong> ${props.diveType ? props.diveType.charAt(0).toUpperCase() + props.diveType.slice(1) : 'Unknown'}<br>
                🎓 <strong>Level:</strong> ${props.suitability ?? 'N/A'}<br>
                <hr class="my-2 border-gray-300">
                🌬️ <strong>Wind Speed:</strong> 
                ${typeof props.windSpeed === 'number' && !isNaN(props.windSpeed)
                    ? (props.windSpeed * 1.94384).toFixed(1) + ' kn'
                    : 'N/A'}<br>
                🌡️ <strong>Water Temp:</strong> ${props.waterTemp ?? 'N/A'} °C<br>
                🌊 <strong>Wave Height:</strong> ${props.waveHeight ?? 'N/A'} m<br>
                ⏱️ <strong>Set Time:</strong> ${typeof props.wavePeriod === 'number' ? props.wavePeriod.toFixed(1) : 'N/A'} s<br>
                🧭 <strong>Direction:</strong> ${props.waveDirection ? degreesToCompass(props.waveDirection) : 'N/A'}<br>
                <hr class="my-2 border-gray-300">
                📅 <em class="text-xs text-slate-600">Updated: ${props.updatedAt ? new Date(props.updatedAt).toLocaleString() : 'N/A'}</em>
            </div>
        `;

        new mapboxgl.Popup()
            .setLngLat(e.lngLat)
            .setHTML(html)
            .addTo(map);
    });

    map.on('mouseenter', 'dive-site-points', () => {
        map.getCanvas().style.cursor = 'pointer';
    });

    map.on('mouseleave', 'dive-site-points', () => {
        map.getCanvas().style.cursor = '';
    });
}

// ✅ Load sites right away
const initialFeatures = buildGeojsonFeatures(sites);
map.on('load', () => {
    addSiteMarkers(initialFeatures);
});

// ✅ Then add user location after
navigator.geolocation.getCurrentPosition(position => {
    const userLat = position.coords.latitude;
    const userLng = position.coords.longitude;

    const userDistance = getDistance(userLat, userLng, -33.8568, 151.2153);
    if (userDistance > 5) {
        map.flyTo({ center: [userLng, userLat], zoom: 11 });
    }

    new mapboxgl.Marker({ color: '#0ea5e9' })
        .setLngLat([userLng, userLat])
        .setPopup(new mapboxgl.Popup().setText("You're here"))
        .addTo(map);
});
</script>
@endpush