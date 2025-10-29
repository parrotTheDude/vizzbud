@extends('layouts.vizzbud')

@section('title', 'Edit Dive Site | Admin | Vizzbud')
@section('meta_description', 'Edit dive site details including region, depth, conditions, and visibility within the Vizzbud admin dashboard.')

@push('head')
  {{-- 🚫 Prevent indexing and public exposure --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical link (for internal consistency only) --}}
  @php $canonical = $site->getFullRouteParams()
      ? route('dive-sites.show', $site->getFullRouteParams())
      : route('admin.divesites.edit', $site->slug);
  @endphp

  <link rel="canonical" href="{{ $canonical }}">

  {{-- Theme / Application meta for admin environment --}}
  <meta name="theme-color" content="#0f172a">
  <meta name="application-name" content="Vizzbud Admin">
  <meta name="color-scheme" content="dark">

  {{-- Optional structured data (internal reference only) --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebApplication",
    "name": "Vizzbud Admin — Edit Dive Site",
    "url": "{{ route('admin.divesites.edit', $site->slug) }}",
    "description": "Admin dashboard interface for editing existing dive site records and managing data.",
    "creator": {
      "@type": "Organization",
      "name": "Vizzbud"
    }
  }
  </script>
@endpush

@section('content')
<section class="max-w-5xl mx-auto px-3 sm:px-6 py-8 sm:py-12 text-white">

  {{-- Header --}}
  <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-3 mb-8 sm:mb-10">
    <h1 class="text-xl sm:text-3xl font-extrabold tracking-tight text-white text-center sm:text-left">
      Edit Dive Site
    </h1>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
      {{-- Back button --}}
      <a href="{{ route('admin.divesites.index') }}"
         class="flex justify-center items-center gap-2 w-full sm:w-auto px-5 py-3 rounded-lg bg-white/10
                border border-white/10 text-white/80 text-sm font-medium hover:bg-white/15 hover:text-white
                transition shadow-sm sm:rounded-full">
        ← Back to List
      </a>

      {{-- View live site --}}
      @if($site->getFullRouteParams())
        <a href="{{ route('dive-sites.show', $site->getFullRouteParams()) }}" target="_blank"
          class="flex justify-center items-center gap-2 w-full sm:w-auto px-5 py-3 rounded-lg sm:rounded-full
                  bg-cyan-600 text-white text-sm font-medium hover:bg-cyan-500 transition shadow-md">
          View Live Site
        </a>
      @else
        <span class="flex justify-center items-center w-full sm:w-auto gap-2 px-5 py-3 rounded-lg sm:rounded-full
                     bg-white/10 text-white/70 text-sm font-medium border border-white/10">
          Incomplete Region Data
        </span>
      @endif
    </div>
  </header>

  {{-- Success notice --}}
  @if(session('success'))
    <div class="mb-6 sm:mb-8 rounded-lg bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 px-4 py-3 text-center sm:text-left">
      {{ session('success') }}
    </div>
  @endif

  {{-- Edit Form --}}
  <form method="POST"
        action="{{ route('admin.divesites.update', ['diveSite' => $site->slug]) }}"
        class=" pb-8 bg-white/5 border border-white/10 ring-1 ring-white/10 rounded-xl sm:rounded-2xl p-4 sm:p-6 backdrop-blur-md shadow-lg">
    @csrf
    @method('PUT')

      {{-- Basic Info --}}
      <div class="pb-4">
        <h2 class="text-lg sm:text-xl font-semibold mb-4 text-white/90">Basic Information</h2>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1 text-white/80">
              Name <span class="text-rose-400">*</span>
            </label>
            <input type="text" name="name" required
                  value="{{ old('name', $site->name) }}"
                  class="w-full rounded-lg bg-white/10 border border-white/10 text-white text-base px-4 py-3
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
          </div>
        </div>
      </div>

      {{-- Location --}}
      <div>
        <h2 class="text-lg sm:text-xl font-semibold mb-4 text-white/90">Location</h2>

        <div class="space-y-5">
          <div id="map" class="w-full h-72 sm:h-80 rounded-xl border border-white/10 ring-1 ring-white/10 overflow-hidden"></div>

          <input type="hidden" name="lat" id="lat" value="{{ old('lat', $site->lat) }}">
          <input type="hidden" name="lng" id="lng" value="{{ old('lng', $site->lng) }}">

          <p class="text-sm text-white/60 leading-relaxed">
            Drag or tap the map to reposition the dive site. State and country auto-fill from map position.
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Region --}}
            <div 
              x-data="regionSearch('{{ old('region_name', $site->region?->name) }}', '{{ old('region_id', $site->region_id) }}')" 
              class="relative"
            >
              <label for="region_name" class="block text-sm font-medium mb-1 text-white/80">
                Location <span class="text-rose-400">*</span>
              </label>
              <input type="text" name="region_name" id="region_name" required
                    x-model="query"
                    placeholder="Start typing a region..."
                    @input.debounce.300ms="search()" @focus="open = true" @click.outside="open = false"
                    class="w-full rounded-lg bg-white/10 border border-white/10 text-white text-base px-4 py-3
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition"
                    value="{{ old('region_name', $site->region?->name) }}">

              <ul x-show="open && results.length > 0"
                  class="absolute z-50 mt-1 w-full rounded-lg bg-white/10 border border-white/15
                        backdrop-blur-md shadow-lg max-h-48 overflow-y-auto">
                <template x-for="(r, i) in results" :key="i">
                  <li @click="select(r)"
                      class="px-4 py-2 text-sm text-white/90 hover:bg-cyan-600 hover:text-white cursor-pointer">
                    <span x-text="r.name"></span>
                    <span class="text-white/50 text-xs ml-1" x-text="r.state ? `(${r.state}, ${r.country})` : ''"></span>
                  </li>
                </template>
              </ul>

              <input type="hidden" name="region_id" x-model="selectedId">
            </div>

            {{-- State --}}
            <div>
              <label for="state_name" class="block text-sm font-medium mb-1 text-white/80">
                State / Region <span class="text-rose-400">*</span>
              </label>
              <input type="text" name="state_name" id="state_name" required
                    value="{{ old('state_name', optional($site->region?->state)->name) }}"
                    placeholder="Auto-filled from map"
                    class="w-full rounded-lg bg-white/10 border border-white/10 text-white text-base px-4 py-3
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
            </div>

            {{-- Country --}}
            <div>
              <label for="country_name" class="block text-sm font-medium mb-1 text-white/80">
                Country <span class="text-rose-400">*</span>
              </label>
              <input type="text" name="country_name" id="country_name" required
                    value="{{ old('country_name', optional($site->region?->state?->country)->name) }}"
                    placeholder="Auto-filled from map"
                    class="w-full rounded-lg bg-white/10 border border-white/10 text-white text-base px-4 py-3
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
            </div>
          </div>
        </div>
      </div>

      {{-- Details --}}
      <div class="pt-8">
        <h2 class="text-lg sm:text-xl font-semibold mb-4 text-white/90">Dive Details</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1 text-white/80">
              Dive Type <span class="text-rose-400">*</span>
            </label>
            <select name="dive_type" required
                    class="w-full rounded-lg bg-white/10 border border-white/10 text-white text-base px-4 py-3
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
              <option value="">— Select —</option>
              <option value="shore" @selected($site->dive_type === 'shore')>Shore</option>
              <option value="boat" @selected($site->dive_type === 'boat')>Boat</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1 text-white/80">
              Suitability <span class="text-rose-400">*</span>
            </label>
            <select name="suitability" required
                    class="w-full rounded-lg bg-white/10 border border-white/10 text-white text-base px-4 py-3
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
              <option value="">— Select —</option>
              <option value="Open Water" @selected($site->suitability === 'Open Water')>Open Water</option>
              <option value="Advanced" @selected($site->suitability === 'Advanced')>Advanced</option>
              <option value="Deep" @selected($site->suitability === 'Deep')>Deep</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1 text-white/80">
              Max Depth (m) <span class="text-rose-400">*</span>
            </label>
            <input type="number" name="max_depth" required
                  value="{{ old('max_depth', $site->max_depth) }}"
                  class="w-full rounded-lg bg-white/10 border border-white/10 text-white text-base px-4 py-3
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
          </div>

          <div>
            <label class="block text-sm font-medium mb-1 text-white/80">Avg Depth (m)</label>
            <input type="number" name="avg_depth"
                  value="{{ old('avg_depth', $site->avg_depth) }}"
                  class="w-full rounded-lg bg-white/10 border border-white/10 text-white text-base px-4 py-3
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
          </div>
        </div>
      </div>

      {{-- 📝 Description & Notes --}}
      <div class="pt-8">
        <h2 class="text-lg sm:text-xl font-semibold mb-4 text-white/90 border-b border-white/10 pb-2">
          Description & Notes
        </h2>

        <div class="space-y-4 sm:space-y-5">
          {{-- Description --}}
          <div>
            <label class="block text-sm sm:text-base font-medium mb-2 text-white/80">Description</label>
            <textarea name="description" 
                      class="w-full rounded-lg bg-white/10 border border-white/15 text-white text-[15px] px-4 py-3.5 
                            min-h-[120px] resize-y focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 
                            placeholder-white/40 transition"
                      placeholder="Describe the dive site environment, layout, entry style...">{{ old('description', $site->description) }}</textarea>
          </div>

          {{-- Entry & Parking Notes (Grouped for mobile flow) --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm sm:text-base font-medium mb-2 text-white/80">Entry Notes</label>
              <textarea name="entry_notes"
                        class="w-full rounded-lg bg-white/10 border border-white/15 text-white text-[15px] px-4 py-3.5 
                              min-h-[100px] resize-y focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 
                              placeholder-white/40 transition"
                        placeholder="e.g. Rocky entry, use caution at low tide">{{ old('entry_notes', $site->entry_notes) }}</textarea>
            </div>

            <div>
              <label class="block text-sm sm:text-base font-medium mb-2 text-white/80">Parking Notes</label>
              <textarea name="parking_notes"
                        class="w-full rounded-lg bg-white/10 border border-white/15 text-white text-[15px] px-4 py-3.5 
                              min-h-[100px] resize-y focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 
                              placeholder-white/40 transition"
                        placeholder="e.g. Metered parking near rock pool">{{ old('parking_notes', $site->parking_notes) }}</textarea>
            </div>
          </div>

          {{-- Hazards --}}
          <div>
            <label class="block text-sm sm:text-base font-medium mb-2 text-white/80">Hazards</label>
            <textarea name="hazards"
                      class="w-full rounded-lg bg-white/10 border border-white/15 text-white text-[15px] px-4 py-3.5 
                            min-h-[100px] resize-y focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 
                            placeholder-white/40 transition"
                      placeholder="e.g. Surge, shallow rocks, boat traffic">{{ old('hazards', $site->hazards) }}</textarea>
          </div>

          {{-- Marine Life --}}
          <div>
            <label class="block text-sm sm:text-base font-medium mb-2 text-white/80">Marine Life</label>
            <textarea name="marine_life"
                      class="w-full rounded-lg bg-white/10 border border-white/15 text-white text-[15px] px-4 py-3.5 
                            min-h-[100px] resize-y focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 
                            placeholder-white/40 transition"
                      placeholder="e.g. Wobbegongs, PJ sharks, sea grass meadows">{{ old('marine_life', $site->marine_life) }}</textarea>
          </div>

          {{-- Pro Tips --}}
          <div>
            <label class="block text-sm sm:text-base font-medium mb-2 text-white/80">Pro Tips</label>
            <textarea name="pro_tips"
                      class="w-full rounded-lg bg-white/10 border border-white/15 text-white text-[15px] px-4 py-3.5 
                            min-h-[100px] resize-y focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 
                            placeholder-white/40 transition"
                      placeholder="e.g. Swim west for the sponge wall">{{ old('pro_tips', $site->pro_tips) }}</textarea>
          </div>
        </div>
      </div>

      {{-- 🗺️ Dive Map --}}
      <div class="pt-8">
        <h2 class="text-lg sm:text-xl font-semibold mb-4 text-white/90 border-b border-white/10 pb-2">
          Dive Map
        </h2>

        <div class="space-y-5">
          {{-- Map Path --}}
          <div>
            <label class="block text-sm sm:text-base font-medium mb-2 text-white/80">Map Image Path</label>
            <input type="text" name="map_image_path"
                  value="{{ old('map_image_path', $site->map_image_path) }}"
                  placeholder="e.g. images/divesites/3-gordons-bay/gordons-bay-site-map.webp"
                  class="w-full rounded-lg bg-white/10 border border-white/15 text-white px-4 py-3.5 text-[15px]
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 placeholder-white/40 transition">
            <p class="text-xs text-white/50 mt-2 leading-relaxed">
              Use a relative path from <code>public/</code>.<br class="sm:hidden">
              Example: <code>images/divesites/3-gordons-bay/map.webp</code>
            </p>
          </div>

          {{-- Caption --}}
          <div>
            <label class="block text-sm sm:text-base font-medium mb-2 text-white/80">Map Caption</label>
            <input type="text" name="map_caption"
                  value="{{ old('map_caption', $site->map_caption) }}"
                  placeholder="e.g. Gordon’s Bay dive route with key points marked"
                  class="w-full rounded-lg bg-white/10 border border-white/15 text-white px-4 py-3.5 text-[15px]
                          focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 placeholder-white/40 transition">
          </div>

          {{-- Map Preview --}}
          @if($site->map_image_path)
            <div>
              <p class="text-sm text-white/70 mb-3 font-medium">Current Map Preview</p>
              <div class="rounded-xl overflow-hidden border border-white/15 shadow-md bg-white/5">
                <img src="{{ asset($site->map_image_path) }}" 
                    alt="Current dive map"
                    loading="lazy"
                    class="w-full max-h-96 object-contain bg-black/20">
              </div>
            </div>
          @endif
        </div>
      </div>

      {{-- 📋 Status --}}
      <div class="pt-8">
        <h2 class="text-lg sm:text-xl font-semibold mb-4 text-white/90 border-b border-white/10 pb-2">
          Status
        </h2>
        
        <div class="flex flex-col sm:flex-row flex-wrap gap-4 sm:gap-6">
          <label class="flex items-center gap-3 bg-white/5 border border-white/10 rounded-lg px-4 py-3 cursor-pointer hover:bg-white/10 transition">
            <input type="checkbox" name="is_active" value="1" @checked($site->is_active)
                  class="rounded-md border-white/20 bg-white/10 text-emerald-400 focus:ring-emerald-400">
            <span class="text-white/90 font-medium">Active</span>
          </label>

          <label class="flex items-center gap-3 bg-white/5 border border-white/10 rounded-lg px-4 py-3 cursor-pointer hover:bg-white/10 transition">
            <input type="checkbox" name="needs_review" value="1" @checked($site->needs_review)
                  class="rounded-md border-white/20 bg-white/10 text-amber-400 focus:ring-amber-400">
            <span class="text-white/90 font-medium">Needs Review</span>
          </label>
        </div>
      </div>

      {{-- 💾 Save --}}
      <div class="pt-8 border-t border-white/10 mt-10">
        <div class="flex flex-col sm:flex-row items-center sm:justify-between gap-4">
          <p class="text-sm text-white/60 sm:text-left text-center">
            Review your changes carefully before saving.
          </p>

          <button type="submit"
                  class="w-full sm:w-auto rounded-lg bg-cyan-600 hover:bg-cyan-500 px-8 py-3 text-white 
                        font-semibold shadow-lg transition focus:outline-none focus:ring-2 focus:ring-cyan-400">
            Save Changes
          </button>
        </div>
      </div>

      {{-- 🧷 Sticky mobile save bar --}}
      <div class="sm:hidden fixed bottom-0 left-0 w-full bg-cyan-700/95 backdrop-blur-md px-5 py-4 border-t border-cyan-500/30 shadow-xl z-50">
        <button type="submit"
                class="w-full rounded-lg bg-cyan-500 hover:bg-cyan-400 px-6 py-3 text-white font-semibold text-lg shadow-md transition">
          Save Changes
        </button>
      </div>

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
  const regionInput = document.getElementById('region_name');

  const lat = parseFloat(latInput.value) || -33.8688; // Sydney fallback
  const lng = parseFloat(lngInput.value) || 151.2093;

  // Initialize Map
  const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/satellite-streets-v12',
    center: [lng, lat],
    zoom: 10,
  });

  // Marker
  const marker = new mapboxgl.Marker({ draggable: true })
    .setLngLat([lng, lat])
    .addTo(map);

  // --- 🧭 Check if the site already has location data ---
  const hasExistingLocation =
    (stateInput.value && stateInput.value.trim() !== '') ||
    (countryInput.value && countryInput.value.trim() !== '') ||
    (regionInput.value && regionInput.value.trim() !== '');

  if (hasExistingLocation) {
    console.log('📍 Existing location data detected — skipping autofill.');
  } else {
    // No stored location data → perform initial autofill
    console.log('🌏 No saved location data — fetching from Mapbox.');
    updateLocation(lat, lng);
  }

  // --- 🖱 Enable auto-filling only after map interaction ---
  let allowAutoFill = false;

  marker.on('dragstart', () => {
    allowAutoFill = true;
  });

  marker.on('dragend', () => {
    if (!allowAutoFill) return;
    const pos = marker.getLngLat();
    updateLocation(pos.lat, pos.lng);
  });

  map.on('click', (e) => {
    allowAutoFill = true;
    marker.setLngLat(e.lngLat);
    updateLocation(e.lngLat.lat, e.lngLat.lng);
  });

  // === FUNCTIONS ===
  function updateLocation(lat, lng) {
    latInput.value = lat.toFixed(6);
    lngInput.value = lng.toFixed(6);

    const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?types=place,locality,region,country&access_token=${mapboxgl.accessToken}`;

    fetch(url)
      .then(res => res.json())
      .then(data => {
        if (!data.features?.length) {
          console.warn('No Mapbox geocode result found — trying nearest coastal location.');
          findNearestCoastal(lat, lng);
          return;
        }

        const f = data.features[0];
        const c = f.context || [];

        // Use Mapbox's region or province as our "state"
        const stateName =
          c.find(x => x.id.startsWith('region'))?.text ||
          c.find(x => x.id.startsWith('province'))?.text ||
          c.find(x => x.id.startsWith('state'))?.text ||
          f.text || '';

        const countryName = c.find(x => x.id.startsWith('country'))?.text || '';

        stateInput.value = stateName || '';
        countryInput.value = countryName || '';

        console.log(`📍 Auto-filled → state=${stateName}, country=${countryName}`);
      })
      .catch(err => {
        console.error('Mapbox geocode failed:', err);
        findNearestCoastal(lat, lng);
      });
  }

  function findNearestCoastal(lat, lng) {
  // Use reverse geocoding around the point, limited to region/country types
  const reverseUrl = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?types=place,locality,region,country&limit=5&access_token=${mapboxgl.accessToken}`;

  fetch(reverseUrl)
    .then(res => res.json())
    .then(data => {
      if (!data.features?.length) {
        console.warn('No reverse geocode result — trying coastal bias.');
        return tryCoastalBias(lat, lng);
      }

      // Find the closest feature *in or near Australia / Oceania* if possible
      const aussieFeature = data.features.find(f =>
        f.place_name?.toLowerCase().includes('australia') ||
        f.context?.some(c => c.text?.toLowerCase().includes('australia'))
      ) || data.features[0];

      const c = aussieFeature.context || [];

      const state =
        c.find(x => x.id.startsWith('region'))?.text ||
        c.find(x => x.id.startsWith('province'))?.text ||
        c.find(x => x.id.startsWith('state'))?.text ||
        aussieFeature.text || '';

      const country =
        c.find(x => x.id.startsWith('country'))?.text ||
        (aussieFeature.place_name?.includes('Australia') ? 'Australia' : '');

      stateInput.value = state || '';
      countryInput.value = country || '';

      console.log(`🌏 Coastal reverse → state=${state}, country=${country}`);
    })
    .catch(err => {
      console.error('Reverse geocode failed:', err);
      tryCoastalBias(lat, lng);
    });
}

  /**
   * Try to bias toward a known Australian coastal locality if reverse geocode fails.
   */
  function tryCoastalBias(lat, lng) {
    // Bias search to Australia using bbox roughly covering AU/NZ
    const biasUrl = `https://api.mapbox.com/geocoding/v5/mapbox.places/coast.json?proximity=${lng},${lat}&bbox=110,-45,155,-10&limit=1&types=place,locality&access_token=${mapboxgl.accessToken}`;

    fetch(biasUrl)
      .then(res => res.json())
      .then(data => {
        if (!data.features?.length) {
          console.warn('No coastal bias results found.');
          return;
        }

        const f = data.features[0];
        const c = f.context || [];

        const state =
          c.find(x => x.id.startsWith('region'))?.text ||
          c.find(x => x.id.startsWith('state'))?.text ||
          f.text || '';

        const country =
          c.find(x => x.id.startsWith('country'))?.text || 'Australia';

        stateInput.value = state || '';
        countryInput.value = country || '';

        console.log(`🇦🇺 Coastal bias fallback → state=${state}, country=${country}`);
      })
      .catch(err => console.error('Coastal bias lookup failed:', err));
  }
});

// === ALPINE REGION SEARCH ===
document.addEventListener('alpine:init', () => {
  Alpine.data('regionSearch', (initialQuery = '', initialId = '') => ({
    query: initialQuery || '',
    selectedId: initialId || null,
    results: [],
    open: false,

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