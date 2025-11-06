@extends('layouts.vizzbud')

@section('title', 'Log a New Dive | Vizzbud')
@section('meta_description', 'Record a new scuba dive including site, depth, duration, gear, and conditions. Keep your dive history organized with Vizzbud.')

{{-- 🚫 Noindex for private user content --}}
@section('noindex')
  <meta name="robots" content="noindex, nofollow">
@endsection

{{-- 🌍 Open Graph / Twitter --}}
@section('og_title', 'Log a New Dive | Vizzbud')
@section('og_description', 'Privately record your scuba dive details — site, time, depth, and conditions — in your Vizzbud dive log.')
@section('og_image', asset('images/divesites/default.webp'))
@section('twitter_title', 'Log a New Dive | Vizzbud')
@section('twitter_description', 'Record a new scuba dive in your private Vizzbud logbook.')
@section('twitter_image', asset('images/divesites/default.webp'))

@push('head')
  {{-- Canonical reference back to main logbook index --}}
  <link rel="canonical" href="{{ url('/logbook') }}">

  {{-- Optional structured data (helps maintain context consistency) --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "Log a New Dive",
    "description": "Create a new scuba dive log entry including site, depth, time, and equipment details using Vizzbud.",
    "url": "{{ url()->current() }}",
    "isPartOf": {
      "@type": "WebSite",
      "name": "Vizzbud",
      "url": "https://vizzbud.com"
    }
  }
  </script>
@endpush

@section('content')
@php $returnTo = request('return', route('logbook.index')); @endphp
<section class="max-w-2xl mx-auto px-4 sm:px-6 py-10 sm:py-12">

  {{-- Back --}}
  <div class="mb-6">
    <a href="{{ $returnTo }}"
       class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold
              text-white bg-white/10 border border-white/10 ring-1 ring-white/10 backdrop-blur-md
              hover:bg-white/15 transition">
      <span>Back to Dive Log</span>
    </a>
  </div>

  {{-- Header + Stepper --}}
  <header class="mb-5 flex items-center justify-between">
    <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight">Log a New Dive</h1>
    <div class="hidden sm:flex items-center gap-2 text-xs">
      <span x-show="step===1" class="rounded-full px-2.5 py-1 bg-cyan-500/15 text-cyan-200 ring-1 ring-cyan-400/30 border border-white/10">Step 1</span>
      <span x-show="step===2" class="rounded-full px-2.5 py-1 bg-cyan-500/15 text-cyan-200 ring-1 ring-cyan-400/30 border border-white/10">Step 2</span>
    </div>
  </header>

  <form method="POST" action="{{ route('logbook.store') }}"
        x-data="createDive()"
        x-init="init()"
        class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-5 sm:p-6 space-y-6"
        @submit.prevent="step === 1 ? (canGoStep2() ? (step=2, focusStep2()) : (siteError = !selectedId)) : $el.submit()">

    @csrf
    <input type="hidden" name="_return" value="{{ $returnTo }}"/>

    {{-- STEP 1 --}}
    <div x-show="step === 1" x-transition>
      {{-- Dive Site (combobox) --}}
      <label class="block">
        <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">
            Dive Site <span class="text-rose-300">*</span>
        </span>

        <div class="relative isolate z-[1]" @keydown.escape="open=false" @click.outside="open=false">
            <input
            x-ref="search"
            type="text"
            x-model="query"
            @focus="open=true"
            @input="debounceFilter"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="select(focusedIndex)"
            placeholder="Search dive sites…"
            :class="['w-full rounded-xl px-4 py-2.5 text-white placeholder-white/50',
                    'bg-white/10 backdrop-blur-md border ring-1',
                    siteError ? 'border-rose-400/50 ring-rose-400/30' : 'border-white/10 ring-white/10']"
            aria-autocomplete="list" :aria-expanded="open" aria-haspopup="listbox"
            />

            <input type="hidden" name="dive_site_id" :value="selectedId" />

            <ul
              x-show="open"
              x-transition
              role="listbox"
              class="absolute z-50 mt-2 w-full max-h-64 overflow-y-auto rounded-xl
                    bg-slate-900/90 backdrop-blur-xl border border-white/10 ring-1 ring-white/10 shadow-2xl"
            >
              <!-- Results -->
              <template x-if="sites.length">
                <template x-for="(site, i) in sites" :key="site.id">
                  <li
                    :class="[
                      'px-4 py-2 cursor-pointer text-sm border-b border-white/5 last:border-0 transition-colors',
                      i===focusedIndex ? 'bg-white/10' : 'hover:bg-white/5'
                    ]"
                    @click="select(i)"
                    @mouseover="focusedIndex=i"
                  >
                    <div class="flex flex-col">
                      <span class="font-medium text-white" x-html="highlight(site.name, query)"></span>
                      <span class="text-xs text-white/60" x-text="formatLocation(site)"></span>
                    </div>
                  </li>
                </template>
              </template>

              <!-- No results message -->
              <template x-if="!loading && query.length >= 3 && !sites.length">
                <li class="px-4 py-3 text-sm text-white/70 text-center">
                  No dive sites found for "<span x-text="query"></span>"
                </li>
              </template>

              <!-- Loading state (optional) -->
              <template x-if="loading">
                <li class="px-4 py-3 text-sm text-white/70 text-center animate-pulse">
                  Searching…
                </li>
              </template>
            </ul>
        </div>
        </label>

      {{-- Title --}}
      <label class="block">
        <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Dive Title</span>
        <input type="text" name="title" x-model="title" @input="autoTitle=false"
               placeholder="e.g. Bare Island Fun Dive"
               class="w-full rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"/>
      </label>

      {{-- Date & Time --}}
      <label class="block">
        <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Dive Date &amp; Time</span>
        <input type="datetime-local" name="dive_date" x-model="diveDate" required
               class="w-full rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"/>
      </label>

      {{-- Depth / Duration / Visibility --}}
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <label class="block relative">
          <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Depth</span>
          <input type="number" step="0.1" name="depth" x-model="depth" required
                 class="w-full rounded-xl px-4 py-2.5 pr-10 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
                 placeholder="e.g. 18"/>
          <span class="pointer-events-none absolute right-3 top-[38px] text-sm text-slate-300">m</span>
        </label>

        <label class="block relative">
          <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Duration</span>
          <input type="number" name="duration" x-model="duration" required
                 class="w-full rounded-xl px-4 py-2.5 pr-12 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
                 placeholder="e.g. 45"/>
          <span class="pointer-events-none absolute right-3 top-[38px] text-sm text-slate-300">min</span>
        </label>

        <label class="block relative">
          <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Visibility</span>
          <input type="number" step="0.1" name="visibility" x-model="visibility" required
                 class="w-full rounded-xl px-4 py-2.5 pr-10 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
                 placeholder="e.g. 10"/>
          <span class="pointer-events-none absolute right-3 top-[38px] text-sm text-slate-300">m</span>
        </label>
      </div>

      {{-- Next --}}
      <div class="mt-6 flex items-center justify-between">
        <span class="text-xs text-white/60">Step 1 of 2</span>
        <button type="button"
                @click="siteError = !selectedId; if (canGoStep2()) step = 2"
                :disabled="!canGoStep2()"
                class="group inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 font-semibold text-white
                       bg-gradient-to-r from-cyan-500/90 to-teal-400/90
                       hover:from-cyan-400/90 hover:to-teal-300/90
                       disabled:opacity-50 disabled:cursor-not-allowed
                       border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                       shadow-lg shadow-cyan-500/20 hover:-translate-y-0.5 transition">
          <span>Next</span> →
        </button>
      </div>
    </div>

    {{-- STEP 2 --}}
    <div x-show="step === 2" x-transition>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <input name="buddy" x-ref="buddy" placeholder="Dive Buddy"
               class="rounded-xl px-4 py-2.5 backdrop-blur-md">

        <input name="air_start" type="number" step="0.1" placeholder="Start Pressure (bar)"
               class="rounded-xl px-4 py-2.5 backdrop-blur-md">

        <input name="air_end" type="number" step="0.1" placeholder="End Pressure (bar)"
               class="rounded-xl px-4 py-2.5 backdrop-blur-md">

        <input name="temperature" type="number" step="0.1" placeholder="Water Temp (°C)"
               class="rounded-xl px-4 py-2.5 backdrop-blur-md">

        <input name="suit_type" placeholder="Wetsuit / Drysuit"
               class="rounded-xl px-4 py-2.5 backdrop-blur-md">

        <input name="tank_type" placeholder="Tank Type"
               class="rounded-xl px-4 py-2.5 backdrop-blur-md">

        <input name="weight_used" placeholder="Weight Used (kg)"
               class="rounded-xl px-4 py-2.5 backdrop-blur-md">

        <label class="sm:col-span-2">
          <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Rating</span>
          <select name="rating"
                  class="w-full rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10">
            <option value="">—</option>
            @for ($i=1; $i<=5; $i++)
              <option value="{{ $i }}">{{ $i }} star{{ $i>1?'s':'' }}</option>
            @endfor
          </select>
        </label>

        <textarea name="notes" rows="4" placeholder="Notes"
                  class="sm:col-span-2 rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"></textarea>
      </div>

      <div class="mt-6 flex items-center justify-between">
        <button type="button"
                @click="step = 1"
                class="inline-flex items-center justify-center rounded-full px-5 py-2.5 text-sm font-semibold
                       text-white/90 bg-white/10 hover:bg-white/15 border border-white/10 ring-1 ring-white/10 backdrop-blur-md transition">
          ← Back
        </button>

        <button type="submit"
                class="group inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 font-semibold text-white
                       bg-gradient-to-r from-emerald-500/90 to-lime-400/90
                       hover:from-emerald-400/90 hover:to-lime-300/90
                       border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                       shadow-lg shadow-emerald-500/20 hover:-translate-y-0.5 transition">
          <span>Add Dive</span>
        </button>
      </div>

      <p class="mt-2 text-xs text-white/60 text-right">Step 2 of 2</p>
    </div>
  </form>
</section>
@endsection

@push('scripts')
<script>
function createDive() {
  return {
    step: 1,

    query: '',
    selectedId: '',
    sites: [],
    open: false,
    focusedIndex: 0,
    siteError: false,
    loading: false,
    _t: null,

    // fields
    title: '',
    autoTitle: true,
    diveDate: '',
    depth: '',
    duration: '',
    visibility: '',

    async init() {
      // auto-fill date
      const now = new Date();
      now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
      this.diveDate = now.toISOString().slice(0, 16);

      this.$watch('query', () => {
        clearTimeout(this._t);
        this._t = setTimeout(() => this.searchSites(), 250);
      });
    },

    async searchSites() {
      const q = (this.query || '').trim();
      if (q.length < 3) {
        this.sites = [];
        this.loading = false;
        return;
      }

      this.loading = true;

      try {
        const res = await fetch(`/api/dive-sites/search?query=${encodeURIComponent(q)}`);
        const data = await res.json();
        this.sites = data.results || [];
      } catch (e) {
        console.error('Search failed:', e);
        this.sites = [];
      } finally {
        this.loading = false;
      }
    },

    move(d) {
      if (!this.sites.length) return;
      this.focusedIndex = (this.focusedIndex + d + this.sites.length) % this.sites.length;
    },

    async select(i) {
      const site = this.sites[i];
      if (!site) return;

      this.query = site.name;
      this.selectedId = site.id;
      this.open = false;
      this.siteError = false;

      // Auto-title smarter: include increment if repeats today
      if (this.autoTitle) {
        const today = this.diveDate?.split('T')[0];
        let title = site.name;

        if (today && site.id) {
          try {
            const res = await fetch(`/logbook/count?site_id=${site.id}&date=${today}`, {
              headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            const nextNum = (data.count || 0) + 1;
            title = `${site.name} Dive ${nextNum}`;
          } catch (err) {
            console.warn('Count fetch failed', err);
          }
        }

        this.title = title;
      }

      this.$nextTick(() => {
        this.$refs.search?.blur();
        setTimeout(() => { this.open = false }, 50);
      });
    },

    canGoStep2() {
      return !!this.selectedId &&
             (this.title||'').trim() !== '' &&
             !!this.diveDate && this.depth !== '' &&
             this.duration !== '' && this.visibility !== '';
    },

    focusStep2() {
      this.$nextTick(() => this.$refs?.buddy?.focus?.());
    },

    highlight(label, q) {
      if (!q) return label;
      const safe = (s) => s.replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
      const i = label.toLowerCase().indexOf(q.toLowerCase());
      if (i === -1) return safe(label);
      return safe(label.slice(0, i)) +
             '<mark class="bg-cyan-300/40 text-inherit rounded px-0.5">' +
             safe(label.slice(i, i + q.length)) +
             '</mark>' +
             safe(label.slice(i + q.length));
    },

    formatLocation(site) {
      const region = site.region || '';
      const country = site.country || '';
      if (region && country) return `${region}, ${country}`;
      return region || country || '';
    }
  };
}
</script>
@endpush