@extends('layouts.vizzbud')

@section('title', 'Dive Site Search Test')

@section('content')
<section class="max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-12 min-h-screen"
         x-data="diveSearchTest()" x-init="init()">

  <h1 class="text-2xl font-extrabold mb-6 text-white">Dive Site Search Test</h1>

  {{-- Search Input --}}
  <div class="relative w-full sm:max-w-md">
    <input type="text"
           x-model="query"
           @focus="open = true"
           @click.away="open = false"
           @keydown.arrow-down.prevent="move(1)"
           @keydown.arrow-up.prevent="move(-1)"
           @keydown.enter.prevent="select(focusedIndex)"
           placeholder="Search dive sites (e.g. Brisbane, Norman Reef, Thailand)…"
           class="w-full rounded-full px-5 py-3 pr-12 text-white placeholder-white/70 
                  bg-white/10 backdrop-blur-md border border-white/20
                  focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition"/>

    {{-- Clear button --}}
    <button x-show="query.length"
            type="button"
            @click="query=''; results=[]; open=false;"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white text-xl transition">×</button>

    {{-- Dropdown --}}
    <ul x-show="open && results.length"
        data-search-list
        class="absolute z-50 bg-white/10 backdrop-blur-md border border-white/20 
               rounded-xl mt-2 w-full text-white shadow-lg max-h-80 overflow-y-auto"
        x-transition>
      <template x-for="(site, index) in results" :key="site.id">
        <li :data-item="index"
            @click="select(index)"
            @mouseover="focusedIndex = index"
            :class="{'bg-white/20': index === focusedIndex}"
            class="px-4 py-2 cursor-pointer transition">
          <div class="font-semibold" x-html="highlight(site.name, debouncedQuery)"></div>
          <div class="text-white/70 text-sm" x-text="`${site.region || ''}${site.region && site.country ? ', ' : ''}${site.country || ''}`"></div>
          <template x-if="site.distance_km">
            <div class="text-xs text-cyan-300 mt-1" x-text="`${site.distance_km.toFixed(1)} km away`"></div>
          </template>
        </li>
      </template>
    </ul>

    {{-- No Results --}}
    <div x-show="open && !results.length && debouncedQuery.length"
         class="absolute z-50 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl mt-2 w-full p-3 text-white/70 text-sm">
      No results for “<span x-text="debouncedQuery"></span>”
    </div>
  </div>

  {{-- Context + Toggle --}}
    <div class="mt-4 flex items-center gap-3 text-sm text-white/70">
    <label for="worldwide-toggle" class="flex items-center gap-2 cursor-pointer select-none">
        <span x-text="worldwide ? '🌏 Showing worldwide results' : '🌍 Showing local results'"></span>
        <div class="relative">
        <input type="checkbox" id="worldwide-toggle" x-model="worldwide" @change="toggleWorldwide"
                class="sr-only peer" />
        <div class="w-11 h-6 bg-white/20 rounded-full peer peer-checked:bg-cyan-500 transition-all"></div>
        <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-all peer-checked:translate-x-5"></div>
        </div>
    </label>
    </div>
</section>

<section>

</section>

@push('scripts')
<script>
function diveSearchTest() {
  return {
    query: '',
    debouncedQuery: '',
    lat: null,
    lng: null,
    country: null,
    worldwide: JSON.parse(localStorage.getItem('vizzbud_worldwide') || 'false'),
    results: [],
    loading: false,
    open: false,
    focusedIndex: 0,
    _t: null,
    _apiTimer: null,

    async init() {
      // debounce typing
      this.$watch('query', () => {
        clearTimeout(this._t);
        this._t = setTimeout(() => {
          this.debouncedQuery = (this.query || '').trim();
          this.handleTyping();
        }, 200);
      });

      window.addEventListener('keydown', e => {
        if (e.key === 'Escape') this.open = false;
      });

      // preload country via IP (only if local mode)
      if (!this.country && !this.worldwide) {
        try {
          const res = await fetch('https://ipapi.co/json/');
          const data = await res.json();
          if (data?.country_name) this.country = data.country_name;
        } catch (e) {
          console.warn('Could not detect country via IP', e);
        }
      }

      this.search();
    },

    normalize(s) {
      return (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    },

    score(name, q) {
      const n = this.normalize(name);
      const query = this.normalize(q);
      if (!n || !query) return 0;
      if (n === query) return 100;
      if (n.startsWith(query)) return 85;
      if (n.includes(query)) return 70;
      if (n.split(' ').some(w => w.startsWith(query))) return 65;
      let matches = 0, i = 0;
      for (const c of n) { if (query[i] === c) { matches++; i++; if (i >= query.length) break; } }
      const ratio = matches / query.length;
      return Math.min(80, ratio * 70);
    },

    async search() {
      clearTimeout(this._apiTimer);
      this._apiTimer = setTimeout(async () => {
        if (!this.debouncedQuery && !this.country) return;
        this.loading = true;
        try {
          const params = new URLSearchParams({
            query: this.debouncedQuery,
            lat: this.lat || '',
            lng: this.lng || '',
            country: this.country || '',
            worldwide: this.worldwide,
          });
          const res = await fetch(`/api/dive-sites/search?${params.toString()}`);
          const data = await res.json();
          this.results = (data.results || []).slice(0, 50);
          if (!this.worldwide && data.country) this.country = data.country;
        } catch (e) {
          console.error(e);
        } finally {
          this.loading = false;
          this.open = true;
        }
      }, 250);
    },

    handleTyping() {
      this.search();
    },

    useLocation() {
      if (!navigator.geolocation) return alert('Geolocation not supported.');
      navigator.geolocation.getCurrentPosition(pos => {
        this.lat = pos.coords.latitude;
        this.lng = pos.coords.longitude;
        this.search();
      });
    },

    toggleWorldwide() {
      localStorage.setItem('vizzbud_worldwide', JSON.stringify(this.worldwide));
      this.search();
    },

    move(dir) {
      if (!this.results.length) return;
      this.focusedIndex = (this.focusedIndex + dir + this.results.length) % this.results.length;
      this.scrollIntoView();
    },

    scrollIntoView() {
      this.$nextTick(() => {
        const list = this.$el.querySelector('[data-search-list]');
        const item = list?.querySelector(`[data-item="${this.focusedIndex}"]`);
        if (!item || !list) return;
        const top = item.offsetTop, bottom = top + item.offsetHeight;
        if (top < list.scrollTop) list.scrollTop = top;
        else if (bottom > list.scrollTop + list.clientHeight) list.scrollTop = bottom - list.clientHeight;
      });
    },

    select(index) {
      const site = this.results[index];
      if (!site) return;
      this.query = site.name;
      this.open = false;
      alert(`Selected: ${site.name} (${site.region || ''}, ${site.country || ''})`);
    },

    highlight(label, q) {
      if (!q) return label;
      const safe = (s) => s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
      const nLabel = this.normalize(label);
      const nq = this.normalize(q);
      const i = nLabel.indexOf(nq);
      if (i === -1) return safe(label);
      return (
        safe(label.slice(0, i)) +
        '<mark class="bg-cyan-300/40 text-inherit rounded px-0.5">' +
        safe(label.slice(i, i + q.length)) +
        '</mark>' +
        safe(label.slice(i + q.length))
      );
    },
  };
}

document.addEventListener('alpine:init', () => Alpine.data('diveSearchTest', diveSearchTest));
</script>
@endpush
@endsection