@extends('layouts.vizzbud')

@section('title', 'Log a New Dive | Vizzbud')
@section('meta_description', 'Record a new scuba dive including site, depth, duration, gear, and conditions. Keep your dive history organized with Vizzbud.')

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
        class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-5 sm:p-6 space-y-6"
        x-data="createDive({ sites: @js($siteOptions) })"
        x-init="initDate()"
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

        <!-- add isolate + relative + z-[1] to make a local stacking context -->
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

            <!-- bump z-index to guarantee it sits on top -->
            <ul
            x-show="open && filtered.length"
            x-transition
            role="listbox"
            class="absolute z-50 mt-2 w-full max-h-60 overflow-y-auto rounded-xl
                    bg-slate-900/85 backdrop-blur-md border border-white/10 ring-1 ring-white/10 shadow-xl"
            >
            <template x-for="(site, i) in filtered" :key="site.id">
                <li
                :class="['px-4 py-2 cursor-pointer text-sm',
                        i===focusedIndex ? 'bg-white/10' : 'hover:bg-white/5']"
                @click="select(i)"
                @mouseover="focusedIndex=i"
                x-html="highlight(site.name, query)"
                ></li>
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

        <input name="air_start" type="number" step="0.1" placeholder="Air Start (bar)"
               class="rounded-xl px-4 py-2.5 backdrop-blur-md">

        <input name="air_end" type="number" step="0.1" placeholder="Air End (bar)"
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
function createDive({ sites }) {
  return {
    step: 1,

    // combobox
    raw: sites || [],
    query: '',
    selectedId: '',
    open: false,
    focusedIndex: 0,
    siteError: false,
    _t: null,

    // fields
    title: '',
    autoTitle: true,
    diveDate: '',
    depth: '',
    duration: '',
    visibility: '',

    get filtered() {
      const q = (this.query || '').trim().toLowerCase();
      if (!q) return this.raw.slice(0, 20);
      return this.raw
        .map(s => ({ s, score: this.score(s.name, q) }))
        .filter(r => r.score > 0)
        .sort((a,b) => b.score - a.score)
        .slice(0, 20)
        .map(r => r.s);
    },

    initDate() {
      const now = new Date();
      now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
      this.diveDate = now.toISOString().slice(0, 16);
    },

    debounceFilter() { clearTimeout(this._t); this._t = setTimeout(() => { this.focusedIndex = 0; }, 100); },
    move(d) { if (!this.filtered.length) return; this.focusedIndex = (this.focusedIndex + d + this.filtered.length) % this.filtered.length; },
    select(i) {
        const site = this.filtered[i];
        if (!site) return;
        this.query = site.name;
        this.selectedId = site.id;
        this.open = false;              // hard close
        this.siteError = false;
        if (this.autoTitle && !this.title) this.title = site.name;
        this.$nextTick(() => {
          this.$refs.search?.blur();
          // ensure dropdown stays closed even if blur triggers focus handlers
          setTimeout(() => { this.open = false }, 50);
        });
        },
    canGoStep2() {
      return !!this.selectedId && (this.title||'').trim() !== '' &&
             !!this.diveDate && this.depth !== '' && this.duration !== '' && this.visibility !== '';
    },
    focusStep2() { this.$nextTick(() => this.$refs?.buddy?.focus?.()); },

    // fuzzy-ish scoring + highlight
    score(name, q) {
      const n = (name||'').toLowerCase();
      if (n === q) return 100;
      if (n.startsWith(q)) return 80;
      if (n.includes(q)) return 50;
      let i=0,h=0; for (const c of n){ if (c===q[i]){ h++; i++; if(i>=q.length) break; } }
      return h >= Math.max(2, Math.ceil(q.length*0.6)) ? 25 : 0;
    },
    highlight(label, q) {
      if (!q) return this.escape(label);
      const L = (label||'').toString(), i = L.toLowerCase().indexOf(q.toLowerCase());
      if (i === -1) return this.escape(L);
      return this.escape(L.slice(0,i)) +
             '<mark class="bg-cyan-300/40 text-inherit rounded px-0.5">' +
             this.escape(L.slice(i, i+q.length)) + '</mark>' +
             this.escape(L.slice(i+q.length));
    },
    escape(s){ return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
  };
}
</script>
@endpush