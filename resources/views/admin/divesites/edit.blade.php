@extends('layouts.vizzbud')

@section('title', 'Edit Dive Site | Admin')

@section('content')
<section class="max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-12 text-white">

  {{-- Header --}}
  <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-8">
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
      Edit Dive Site
    </h1>

    <div class="flex flex-wrap gap-2">
      <a href="{{ route('admin.divesites.index') }}"
         class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10
                border border-white/10 ring-1 ring-white/10 text-white/80 text-sm font-medium
                hover:bg-white/15 hover:text-white transition">
        ← Back to List
      </a>

      @if($site->slug)
      <a href="{{ route('dive-sites.show', $site->slug) }}" target="_blank"
         class="flex items-center gap-2 px-4 py-2 rounded-full bg-cyan-600 text-white text-sm font-medium
                hover:bg-cyan-500 transition shadow-md">
        View Live Site
      </a>
      @endif
    </div>
  </header>

    @if(session('success'))
    <div class="mb-6 rounded-lg bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 px-4 py-3">
        {{ session('success') }}
    </div>
    @endif

    {{-- Edit Form --}}
    <form method="POST"
        action="{{ route('admin.divesites.update', ['diveSite' => $site->slug]) }}"
        class="space-y-8 bg-white/5 border border-white/10 ring-1 ring-white/10 rounded-2xl p-6 backdrop-blur-md">
    @csrf
    @method('PUT')

    {{-- Basic Info --}}
    <div>
    <h2 class="text-lg font-semibold mb-4 text-white/90">Basic Information</h2>

    <div class="grid grid-cols-1 gap-4">
        <div>
        <label class="block text-sm mb-1 text-white/70">Name</label>
        <input type="text" name="name" value="{{ old('name', $site->name) }}"
                class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                        focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
        </div>
    </div>
    </div>

    {{-- Location --}}
    <div>
    <h2 class="text-lg font-semibold mb-4 text-white/90">Location</h2>

    <div class="space-y-4">
        <div id="map"
            class="w-full h-80 rounded-xl border border-white/10 ring-1 ring-white/10 overflow-hidden"></div>

        {{-- Hidden fields --}}
        <input type="hidden" name="lat" id="lat" value="{{ old('lat', $site->lat) }}">
        <input type="hidden" name="lng" id="lng" value="{{ old('lng', $site->lng) }}">

        <p class="text-sm text-white/60">
        Drag or click on the map to reposition the dive site. Region and country will auto-fill.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1 text-white/70">Region</label>
            <input type="text" name="region" id="region" value="{{ old('region', $site->region) }}"
                class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                        focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
        </div>

        <div>
            <label class="block text-sm mb-1 text-white/70">Country</label>
            <input type="text" name="country" id="country" value="{{ old('country', $site->country) }}"
                class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                        focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
        </div>
        </div>
    </div>
    </div>

    {{-- Details --}}
    <div>
      <h2 class="text-lg font-semibold mb-4 text-white/90">Dive Details</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm mb-1 text-white/70">Dive Type</label>
          <select name="dive_type"
                  class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                         focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
            <option value="">— Select —</option>
            <option value="shore" @selected($site->dive_type === 'shore')>Shore</option>
            <option value="boat" @selected($site->dive_type === 'boat')>Boat</option>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-1 text-white/70">Suitability</label>
          <select name="suitability"
                  class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                         focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
            <option value="">— Select —</option>
            <option value="Open Water" @selected($site->suitability === 'Open Water')>Open Water</option>
            <option value="Advanced" @selected($site->suitability === 'Advanced')>Advanced</option>
            <option value="Deep" @selected($site->suitability === 'Deep')>Deep</option>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-1 text-white/70">Max Depth (m)</label>
          <input type="number" name="max_depth" value="{{ old('max_depth', $site->max_depth) }}"
                 class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                        focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
        </div>

        <div>
          <label class="block text-sm mb-1 text-white/70">Avg Depth (m)</label>
          <input type="number" name="avg_depth" value="{{ old('avg_depth', $site->avg_depth) }}"
                 class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                        focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
        </div>
      </div>
    </div>

    {{-- 📝 Description & Notes --}}
    <div>
      <h2 class="text-lg font-semibold mb-4 text-white/90">Description & Notes</h2>

      <div class="space-y-6">
        {{-- Description --}}
        <div>
          <label class="block text-base mb-2 text-white/80">Description</label>
          <textarea name="description" rows="5"
            class="w-full rounded-xl bg-white/10 border border-white/15 text-white px-4 py-3 text-[15px]
                  focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition
                  placeholder-white/30">{{ old('description', $site->description) }}</textarea>
        </div>

        {{-- Hazards --}}
        <div>
          <label class="block text-base mb-2 text-white/80">Hazards</label>
          <textarea name="hazards" rows="4"
            class="w-full rounded-xl bg-white/10 border border-white/15 text-white px-4 py-3 text-[15px]
                  focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition
                  placeholder-white/30">{{ old('hazards', $site->hazards) }}</textarea>
        </div>

        {{-- Pro Tips --}}
        <div>
          <label class="block text-base mb-2 text-white/80">Pro Tips</label>
          <textarea name="pro_tips" rows="4"
            class="w-full rounded-xl bg-white/10 border border-white/15 text-white px-4 py-3 text-[15px]
                  focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition
                  placeholder-white/30">{{ old('pro_tips', $site->pro_tips) }}</textarea>
        </div>

        {{-- Entry Notes --}}
        <div>
          <label class="block text-base mb-2 text-white/80">Entry Notes</label>
          <textarea name="entry_notes" rows="4"
            class="w-full rounded-xl bg-white/10 border border-white/15 text-white px-4 py-3 text-[15px]
                  focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition
                  placeholder-white/30">{{ old('entry_notes', $site->entry_notes) }}</textarea>
        </div>

        {{-- Parking Notes --}}
        <div>
          <label class="block text-base mb-2 text-white/80">Parking Notes</label>
          <textarea name="parking_notes" rows="4"
            class="w-full rounded-xl bg-white/10 border border-white/15 text-white px-4 py-3 text-[15px]
                  focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition
                  placeholder-white/30">{{ old('parking_notes', $site->parking_notes) }}</textarea>
        </div>

        {{-- Marine Life --}}
        <div>
          <label class="block text-base mb-2 text-white/80">Marine Life</label>
          <textarea name="marine_life" rows="4"
            class="w-full rounded-xl bg-white/10 border border-white/15 text-white px-4 py-3 text-[15px]
                  focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition
                  placeholder-white/30">{{ old('marine_life', $site->marine_life) }}</textarea>
        </div>
      </div>
    </div>

    {{-- 🗺️ Dive Map --}}
    <div>
      <h2 class="text-lg font-semibold mb-4 text-white/90">Dive Map</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-sm mb-1 text-white/70">Map Image Path</label>
          <input type="text" name="map_image_path" value="{{ old('map_image_path', $site->map_image_path) }}"
                placeholder="e.g. images/divesites/3-gordons-bay/gordons-bay-site-map.webp"
                class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                        focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
          <p class="text-xs text-white/50 mt-1">
            Use a relative path from <code>public/</code>. Example: <code>images/divesites/3-gordons-bay/map.webp</code>
          </p>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-sm mb-1 text-white/70">Map Caption</label>
          <input type="text" name="map_caption" value="{{ old('map_caption', $site->map_caption) }}"
                placeholder="e.g. Gordon’s Bay dive route with key points marked"
                class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                        focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
        </div>

        @if($site->map_image_path)
          <div class="sm:col-span-2">
            <p class="text-sm text-white/70 mb-2">Current Map Preview</p>
            <img src="{{ asset($site->map_image_path) }}" 
                alt="Current dive map"
                class="w-full max-h-96 object-contain rounded-xl border border-white/10 shadow-md">
          </div>
        @endif
      </div>
    </div>

    {{-- Status --}}
    <div>
      <h2 class="text-lg font-semibold mb-4 text-white/90">Status</h2>
      <div class="flex flex-wrap gap-6">
        <label class="flex items-center gap-2">
          <input type="checkbox" name="is_active" value="1" @checked($site->is_active)
                 class="rounded border-white/20 bg-white/10 text-emerald-400 focus:ring-emerald-400">
          <span>Active</span>
        </label>

        <label class="flex items-center gap-2">
          <input type="checkbox" name="needs_review" value="1" @checked($site->needs_review)
                 class="rounded border-white/20 bg-white/10 text-amber-400 focus:ring-amber-400">
          <span>Needs Review</span>
        </label>
      </div>
    </div>

    {{-- Save --}}
    <div class="pt-6 border-t border-white/10">
      <button type="submit"
              class="rounded-lg bg-cyan-600 hover:bg-cyan-500 px-6 py-3 text-white font-semibold shadow-md transition">
        Save Changes
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

    const lat = parseFloat(latInput.value) || -33.8688; // default Sydney
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

        // 🔄 Reverse geocode region & country
        const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?access_token=${mapboxgl.accessToken}`;

        fetch(url)
          .then(res => res.json())
          .then(data => {
              const context = data.features[0]?.context || [];
              const region = context.find(c => c.id.startsWith('region'))?.text || '';
              const country = context.find(c => c.id.startsWith('country'))?.text || '';

              if (region) regionInput.value = region;
              if (country) countryInput.value = country;
          })
          .catch(err => console.error('Geocoding failed', err));
    }

    // Drag marker
    marker.on('dragend', () => {
        const pos = marker.getLngLat();
        updateLocation(pos.lat, pos.lng);
    });

    // Click to move
    map.on('click', (e) => {
        marker.setLngLat(e.lngLat);
        updateLocation(e.lngLat.lat, e.lngLat.lng);
    });
});
</script>
@endpush
@endsection