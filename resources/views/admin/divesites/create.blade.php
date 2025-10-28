@extends('layouts.vizzbud')

@section('title', 'Add New Dive Site | Admin | Vizzbud')
@section('meta_description', 'Add a new scuba dive site to the Vizzbud platform. Input name, region, coordinates, and site details from the admin dashboard.')

@push('head')
  {{-- 🚫 Prevent search engine indexing (admin area) --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical (internal consistency only) --}}
  <link rel="canonical" href="{{ route('admin.dive-sites.create') }}">

  {{-- Admin theme + app metadata --}}
  <meta name="theme-color" content="#0f172a">
  <meta name="application-name" content="Vizzbud Admin">
  <meta name="color-scheme" content="dark">

  {{-- Optional internal-use structured data --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebApplication",
    "name": "Vizzbud Admin — Add Dive Site",
    "url": "{{ route('admin.dive-sites.create') }}",
    "description": "Administrative interface for adding new dive sites to the Vizzbud directory.",
    "creator": {
      "@type": "Organization",
      "name": "Vizzbud"
    }
  }
  </script>
@endpush

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
          Drag or click on the map to reposition the dive site. Region and country will auto-fill.
        </p>

        <div>
          <select name="region_id" id="region_id"
                  class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                        focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
            <option value="">— Select Region —</option>
            @foreach($regions as $region)
              <option value="{{ $region->id }}"
                      data-region="{{ strtolower($region->name) }}"
                      data-state="{{ strtolower($region->state->name) }}"
                      data-country="{{ strtolower($region->state->country->name) }}"
                      @selected(old('region_id') == $region->id)>
                {{ $region->name }} ({{ $region->state->name }}, {{ $region->state->country->name }})
              </option>
            @endforeach
          </select>

          <p id="regionSuggestion" class="text-xs text-amber-300 mt-2 hidden">
            Suggested region: <span class="font-semibold"></span>
          </p>
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

  function updateLocation(lat, lng) {
    latInput.value = lat.toFixed(6);
    lngInput.value = lng.toFixed(6);

    const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?access_token=${mapboxgl.accessToken}`;

    fetch(url)
      .then(res => res.json())
      .then(data => {
        const context = data.features[0]?.context || [];
        const place = data.features[0];

        const regionName =
            context.find(c => c.id.startsWith('region'))?.text ||
            place?.text || '';
        const stateName =
            context.find(c => c.id.startsWith('province'))?.text ||
            context.find(c => c.id.startsWith('state'))?.text || '';
        const countryName =
            context.find(c => c.id.startsWith('country'))?.text || '';

        const regionSelect = document.getElementById('region_id');
        const suggestionEl = document.getElementById('regionSuggestion');
        const options = Array.from(regionSelect.options);

        // Try exact match: region + state + country
        const match = options.find(opt =>
          opt.dataset.region === regionName.toLowerCase() &&
          opt.dataset.state === stateName.toLowerCase() &&
          opt.dataset.country === countryName.toLowerCase()
        );

        if (match) {
          regionSelect.value = match.value;
          suggestionEl.classList.add('hidden');
        } else {
          const partial = options.find(opt =>
            opt.dataset.region === regionName.toLowerCase()
          );

          if (partial) {
            regionSelect.value = partial.value;
            suggestionEl.classList.add('hidden');
          } else {
            suggestionEl.querySelector('span').textContent =
              `${regionName} (${stateName}, ${countryName})`;
            suggestionEl.classList.remove('hidden');
          }
        }
      })
      .catch(err => console.error('Geocoding failed:', err));
  }

  marker.on('dragend', () => {
    const pos = marker.getLngLat();
    updateLocation(pos.lat, pos.lng);
  });

  map.on('click', (e) => {
    marker.setLngLat(e.lngLat);
    updateLocation(e.lngLat.lat, e.lngLat.lng);
  });
});
</script>
@endpush
@endsection