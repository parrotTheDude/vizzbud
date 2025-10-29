@extends('layouts.vizzbud')

@section('title', 'Add New Dive Site | Admin | Vizzbud')
@section('meta_description', 'Add a new scuba dive site to the Vizzbud platform. Input name, region, coordinates, and site details from the admin dashboard.')

@push('head')
  {{-- 🚫 Prevent search engine indexing (admin area) --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical (internal consistency only) --}}
  <link rel="canonical" href="{{ route('admin.divesites.create') }}">

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
    "url": "{{ route('admin.divesites.create') }}",
    "description": "Administrative interface for adding new dive sites to the Vizzbud directory.",
    "creator": {
      "@type": "Organization",
      "name": "Vizzbud"
    }
  }
  </script>
@endpush

@section('content')
<section class="max-w-5xl mx-auto px-3 sm:px-6 py-8 sm:py-12 text-white space-y-10">

  {{-- Header --}}
  <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 text-center sm:text-left">
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
      Add New Dive Site
    </h1>

    <a href="{{ route('admin.divesites.index') }}"
      class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 rounded-full
             bg-white/10 border border-white/10 text-white/80 text-sm font-medium
             hover:bg-white/15 hover:text-white transition">
      ← Back to List
    </a>
  </header>

  {{-- Create Form --}}
  <form method="POST" action="{{ route('admin.divesites.store') }}"
        class="bg-white/5 border border-white/10 ring-1 ring-white/10 rounded-2xl
               p-4 sm:p-6 backdrop-blur-md shadow-[0_1px_8px_rgba(0,0,0,0.2)]">
    @csrf

    {{-- 🧾 Basic Info --}}
    <div>
      <h2 class="text-lg font-semibold mb-3 text-white/90">Basic Information</h2>
      <div>
        <label class="block text-sm mb-1 text-white/70">Name <span class="text-red-400">*</span></label>
        <input type="text" name="name" required value="{{ old('name') }}"
               class="w-full rounded-lg bg-white/10 border border-white/15 text-white px-3 sm:px-4 py-2.5 sm:py-3 text-[15px]
                      focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition placeholder-white/40 shadow-[0_1px_4px_rgba(0,0,0,0.2)]">
      </div>
    </div>

    {{-- 📍 Location --}}
    <div class="pt-4">
      <h2 class="text-lg font-semibold mb-3 text-white/90">Location</h2>
      <div class="space-y-4">

        {{-- Map --}}
        <div id="map"
             class="w-full h-64 sm:h-72 rounded-xl border border-white/10 ring-1 ring-white/10 overflow-hidden shadow-md"></div>

        {{-- Hidden coords --}}
        <input type="hidden" name="lat" id="lat" value="{{ old('lat', -33.8688) }}">
        <input type="hidden" name="lng" id="lng" value="{{ old('lng', 151.2093) }}">

        <p class="text-sm text-white/60">
          Tap or drag the map to reposition the dive site. Region and country auto-fill from location.
        </p>

        {{-- Region / State / Country --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">

          {{-- Region (Live Search) --}}
          <div x-data="regionSearch('{{ old('region_name') }}', '{{ old('region_id') }}')" class="relative">
            <label for="region_name" class="block text-sm mb-1 text-white/70">
              Region <span class="text-red-400">*</span>
            </label>

            <input type="text" name="region_name" id="region_name"
                   x-model="query"
                   required
                   placeholder="Start typing a region..."
                   @input.debounce.300ms="search()"
                   @focus="open = true"
                   @click.outside="open = false"
                   class="w-full rounded-lg bg-white/10 border border-white/15 text-white px-3 sm:px-4 py-2.5 sm:py-3 text-[15px]
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition placeholder-white/40">

            {{-- Dropdown --}}
            <ul x-show="open && results.length > 0"
                class="absolute z-50 mt-1 w-full rounded-lg bg-white/10 border border-white/15
                       backdrop-blur-md shadow-lg max-h-48 overflow-y-auto overscroll-contain">
              <template x-for="(r, i) in results" :key="i">
                <li @click="select(r)"
                    class="px-3 py-2 text-sm text-white/90 hover:bg-cyan-600 hover:text-white cursor-pointer">
                  <span x-text="r.name"></span>
                  <span class="text-white/50 text-xs ml-1"
                        x-text="r.state ? `(${r.state}, ${r.country})` : ''"></span>
                </li>
              </template>
            </ul>

            <input type="hidden" name="region_id" x-model="selectedId">
            <p x-show="!results.length && query.length > 2 && !selectedId"
               class="text-xs text-amber-300 mt-2">
              No existing region found — it will be created automatically.
            </p>
          </div>

          {{-- State --}}
          <div>
            <label for="state_name" class="block text-sm mb-1 text-white/70">
              State / Region <span class="text-red-400">*</span>
            </label>
            <input type="text" name="state_name" id="state_name"
                   value="{{ old('state_name') }}"
                   placeholder="Auto-filled from map"
                   class="w-full rounded-lg bg-white/10 border border-white/15 text-white px-3 sm:px-4 py-2.5 sm:py-3 text-[15px]
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition placeholder-white/40">
          </div>

          {{-- Country --}}
          <div>
            <label for="country_name" class="block text-sm mb-1 text-white/70">
              Country <span class="text-red-400">*</span>
            </label>
            <input type="text" name="country_name" id="country_name"
                   value="{{ old('country_name') }}"
                   placeholder="Auto-filled from map"
                   class="w-full rounded-lg bg-white/10 border border-white/15 text-white px-3 sm:px-4 py-2.5 sm:py-3 text-[15px]
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition placeholder-white/40">
          </div>
        </div>
      </div>
    </div>

    {{-- 🤿 Dive Details --}}
    <div class="pt-4">
      <h2 class="text-lg font-semibold mb-3 text-white/90">Dive Details</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">

        <div>
          <label class="block text-sm mb-1 text-white/70">Dive Type <span class="text-red-400">*</span></label>
          <select name="dive_type" required
                  class="w-full rounded-lg bg-white/10 border border-white/15 text-white px-3 sm:px-4 py-2.5 sm:py-3 text-[15px]
                         focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
            <option value="">— Select —</option>
            <option value="shore">Shore</option>
            <option value="boat">Boat</option>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-1 text-white/70">Suitability <span class="text-red-400">*</span></label>
          <select name="suitability" required
                  class="w-full rounded-lg bg-white/10 border border-white/15 text-white px-3 sm:px-4 py-2.5 sm:py-3 text-[15px]
                         focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
            <option value="">— Select —</option>
            <option value="Open Water">Open Water</option>
            <option value="Advanced">Advanced</option>
            <option value="Deep">Deep</option>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-1 text-white/70">Max Depth (m)</label>
          <input type="number" name="max_depth" required value="{{ old('max_depth') }}"
                 placeholder="e.g. 18"
                 min="0" max="100"
                 class="w-full rounded-lg bg-white/10 border border-white/15 text-white px-3 sm:px-4 py-2.5 sm:py-3 text-[15px]
                        focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition placeholder-white/40">
        </div>

        <div>
          <label class="block text-sm mb-1 text-white/70">Average Depth (m)</label>
          <input type="number" name="avg_depth" required value="{{ old('avg_depth') }}"
                 placeholder="e.g. 12"
                 min="0" max="100"
                 class="w-full rounded-lg bg-white/10 border border-white/15 text-white px-3 sm:px-4 py-2.5 sm:py-3 text-[15px]
                        focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition placeholder-white/40">
        </div>
      </div>
    </div>

    {{-- Save --}}
    <div class="pt-6 border-t border-white/10">
      <button type="submit"
              class="w-full sm:w-auto rounded-lg bg-cyan-600 hover:bg-cyan-500 px-6 sm:px-8 py-3 text-white font-semibold shadow-md transition">
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
  const stateInput = document.getElementById('state_name');
  const countryInput = document.getElementById('country_name');
  const lat = parseFloat(latInput.value);
  const lng = parseFloat(lngInput.value);

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
        const f = data.features?.[0];
        if (!f) return;
        const c = f.context || [];

        const state =
          c.find(x => x.id.startsWith('region'))?.text ||
          c.find(x => x.id.startsWith('province'))?.text ||
          c.find(x => x.id.startsWith('state'))?.text || '';
        const country = c.find(x => x.id.startsWith('country'))?.text || '';

        if (state) stateInput.value = state;
        if (country) countryInput.value = country;

        console.log(`📍Auto-filled → state=${state}, country=${country}`);
      })
      .catch(err => console.error('Geocoding failed:', err));
  }

  marker.on('dragend', () => {
    const pos = marker.getLngLat();
    updateLocation(pos.lat, pos.lng);
  });

  map.on('click', e => {
    marker.setLngLat(e.lngLat);
    updateLocation(e.lngLat.lat, e.lngLat.lng);
  });
});

// === ALPINE REGION SEARCH (same as edit) ===
document.addEventListener('alpine:init', () => {
  Alpine.data('regionSearch', (initialQuery = '', initialId = '') => ({
    query: initialQuery || '',
    results: [],
    open: false,
    selectedId: initialId || null,

    search() {
      if (this.query.length < 2) {
        this.results = [];
        this.selectedId = null;
        return;
      }
      fetch(`/dive-map/region-search?q=${encodeURIComponent(this.query)}`)
        .then(res => res.json())
        .then(data => (this.results = data))
        .catch(err => console.error('Region search failed:', err));
    },

    select(region) {
      this.query = region.name;
      this.selectedId = region.id;
      this.results = [];
      this.open = false;
    }
  }));
});
</script>
@endpush
@endsection