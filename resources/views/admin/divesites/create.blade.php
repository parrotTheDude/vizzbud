@extends('layouts.vizzbud')

@section('title', 'Add New Dive Site | Admin')

@section('content')
<section class="max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-12 text-white">

  {{-- Header --}}
  <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-8">
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
      Add New Dive Site
    </h1>

    <a href="{{ route('admin.divesites.index') }}"
       class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10
              border border-white/10 ring-1 ring-white/10 text-white/80 text-sm font-medium
              hover:bg-white/15 hover:text-white transition">
      ← Back to List
    </a>
  </header>

  {{-- Create Form --}}
  <form method="POST" action="{{ route('admin.divesites.store') }}"
        class="space-y-8 bg-white/5 border border-white/10 ring-1 ring-white/10 rounded-2xl p-6 backdrop-blur-md">
    @csrf

    {{-- Basic Info --}}
    <div>
      <h2 class="text-lg font-semibold mb-4 text-white/90">Basic Information</h2>
      <div>
        <label class="block text-sm mb-1 text-white/70">Name</label>
        <input type="text" name="name" required value="{{ old('name') }}"
               class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                      focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
      </div>
    </div>

    {{-- Location --}}
    <div>
      <h2 class="text-lg font-semibold mb-4 text-white/90">Location</h2>
      <div class="space-y-4">
        <div id="map"
             class="w-full h-72 rounded-xl border border-white/10 ring-1 ring-white/10 overflow-hidden"></div>

        {{-- Hidden fields --}}
        <input type="hidden" required name="lat" id="lat" value="{{ old('lat', -33.8688) }}">
        <input type="hidden" required name="lng" id="lng" value="{{ old('lng', 151.2093) }}">

        <p class="text-sm text-white/60">
          Drag the marker or click the map to select a location.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1 text-white/70">Region</label>
            <input type="text" name="region" id="region" required value="{{ old('region') }}"
                   class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
          </div>

          <div>
            <label class="block text-sm mb-1 text-white/70">Country</label>
            <input type="text" name="country" id="country" required value="{{ old('country') }}"
                   class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
          </div>
        </div>
      </div>
    </div>

    {{-- Dive Details --}}
    <div>
      <h2 class="text-lg font-semibold mb-4 text-white/90">Dive Details</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm mb-1 text-white/70">Dive Type</label>
          <select name="dive_type" required
                  class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                         focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
            <option value="">— Select —</option>
            <option value="shore">Shore</option>
            <option value="boat">Boat</option>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-1 text-white/70">Suitability</label>
          <select name="suitability" required
                  class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                         focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
            <option value="">— Select —</option>
            <option value="Open Water">Open Water</option>
            <option value="Advanced">Advanced</option>
            <option value="Deep">Deep</option>
          </select>
        </div>
      </div>
    </div>

    {{-- Save --}}
    <div class="pt-6 border-t border-white/10">
      <button type="submit"
              class="rounded-lg bg-cyan-600 hover:bg-cyan-500 px-6 py-3 text-white font-semibold shadow-md transition">
        Create Dive Site
      </button>
    </div>
  </form>
</section>

@push('scripts')
<script src="https://api.mapbox.com/mapbox-gl-js/v3.2.0/mapbox-gl.js"></script>
<link href="https://api.mapbox.com/mapbox-gl-js/v3.2.0/mapbox-gl.css" rel="stylesheet" />

<script>
document.addEventListener('DOMContentLoaded', () => {
  mapboxgl.accessToken = '{{ $mapboxToken }}';

  const latInput = document.getElementById('lat');
  const lngInput = document.getElementById('lng');
  const regionInput = document.getElementById('region');
  const countryInput = document.getElementById('country');

  const lat = parseFloat(latInput.value) || -33.8688;
  const lng = parseFloat(lngInput.value) || 151.2093;

  const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/satellite-streets-v12',
    center: [lng, lat],
    zoom: 10
  });

  const marker = new mapboxgl.Marker({ draggable: true })
    .setLngLat([lng, lat])
    .addTo(map);

  // 🔹 Shared function to update hidden fields and fetch reverse geocode
  function updateLocation(lat, lng, doFetch = true) {
    latInput.value = lat.toFixed(6);
    lngInput.value = lng.toFixed(6);

    if (!doFetch) return;

    const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?access_token=${mapboxgl.accessToken}`;
    fetch(url)
      .then(res => res.json())
      .then(data => {
        if (!data.features || !data.features.length) return;

        const contexts = [
          ...(data.features[0].context || []),
          data.features[0]
        ];

        const region = contexts.find(f =>
          f.id.startsWith('region') ||
          f.id.startsWith('province') ||
          f.id.startsWith('state')
        )?.text;

        const country = contexts.find(f => f.id.startsWith('country'))?.text;

        if (region && !regionInput.value) regionInput.value = region;
        if (country && !countryInput.value) countryInput.value = country;
      })
      .catch(err => console.error('Geocoding failed', err));
  }

  // 🔹 Run once on map load to prefill region/country
  map.on('load', () => {
    updateLocation(lat, lng, true);
  });

  // 🔹 Update when marker is dragged
  marker.on('dragend', () => {
    const pos = marker.getLngLat();
    updateLocation(pos.lat, pos.lng, true);
  });

  // 🔹 Update when clicking map
  map.on('click', (e) => {
    marker.setLngLat(e.lngLat);
    updateLocation(e.lngLat.lat, e.lngLat.lng, true);
  });
});
</script>
@endpush
@endsection