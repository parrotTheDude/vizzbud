@extends('layouts.vizzbud')

@php
  $displayNum = $log->dive_number ?? $log->id;
  $siteName   = $log->site->name ?? 'Unknown Site';
  $returnTo   = request('return', url()->previous());
@endphp

@section('title', "Edit Dive #{$displayNum} | {$siteName} | Vizzbud")
@section('meta_description', "Update details for Dive #{$displayNum} at {$siteName}. Edit depth, duration, gear, notes, and more in your dive log.")

@section('content')
<section class="max-w-2xl mx-auto px-4 sm:px-6 py-10 sm:py-12">

  {{-- Top bar: back + page title --}}
  <div class="mb-6 flex items-center justify-between gap-3">
    <a href="{{ $returnTo }}"
       class="group inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold
              text-white bg-white/10 border border-white/10 ring-1 ring-white/10 backdrop-blur-md
              hover:bg-white/15 transition">
      <span>← Back</span>
    </a>

    <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white">
      Edit Dive #{{ $displayNum }}
    </h1>
  </div>

  {{-- Validation errors --}}
  @if ($errors->any())
    <div class="mb-6 rounded-xl border border-rose-500/30 ring-1 ring-rose-400/20 bg-rose-500/10
                text-rose-100 px-4 py-3 backdrop-blur-md">
      <ul class="list-disc list-inside space-y-1 text-sm">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('logbook.update', ['log' => $log->id]) }}"
        class="space-y-6 rounded-2xl border border-white/10 ring-1 ring-white/10
               bg-white/10 backdrop-blur-xl shadow-xl p-5 sm:p-6"
        x-data="editDive({
          sites: @js($siteOptions),
          initialId: {{ $log->dive_site_id ?? 'null' }},
          initialName: @js($log->site->name ?? ''),
        })"
        @submit.prevent="if (validateDiveSite()) $el.submit()">

    @csrf
    @method('PUT')
    <input type="hidden" name="_return" value="{{ $returnTo }}"/>

    {{-- Dive Site (glassy combobox) --}}
    <label class="block">
      <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Dive Site <span class="text-rose-300">*</span></span>

      <div class="relative" @keydown.escape="open=false">
        <input
          type="text"
          x-model="query"
          @focus="open = true"
          @input="debounceFilter"
          @keydown.arrow-down.prevent="move(1)"
          @keydown.arrow-up.prevent="move(-1)"
          @keydown.enter.prevent="select(focusedIndex)"
          placeholder="Search dive sites…"
          :class="['w-full rounded-xl px-4 py-2.5 text-white placeholder-white/50',
                   'bg-white/10 backdrop-blur-md border ring-1',
                   diveSiteError ? 'border-rose-400/50 ring-rose-400/30' : 'border-white/10 ring-white/10']"
        />

        <input type="hidden" name="dive_site_id" :value="selectedId">

        <ul x-show="open && filtered.length"
            x-transition
            class="absolute z-20 mt-2 w-full max-h-60 overflow-y-auto rounded-xl
                   bg-slate-900/85 backdrop-blur-md border border-white/10 ring-1 ring-white/10 shadow-xl">
          <template x-for="(site, index) in filtered" :key="site.id">
            <li
              :class="['px-4 py-2 cursor-pointer text-sm',
                       index === focusedIndex ? 'bg-white/10' : 'hover:bg-white/5']"
              @click="select(index)"
              @mouseover="focusedIndex = index"
              x-html="highlight(site.name, query)"
            ></li>
          </template>
        </ul>
      </div>

      <p x-show="diveSiteError" class="mt-1 text-sm text-rose-300">Please select a valid dive site.</p>
    </label>

    {{-- Dive Date/Time --}}
    <label class="block">
      <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Dive Date</span>
      <input type="datetime-local"
             name="dive_date"
             required
             class="w-full rounded-xl px-4 py-2.5 text-white
                    bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
             value="{{ \Carbon\Carbon::parse($log->dive_date)->format('Y-m-d\TH:i') }}">
    </label>

    {{-- Depth / Duration / Visibility (units inside) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <label class="block relative">
        <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Depth</span>
        <input type="number" step="0.1" name="depth" required
               class="w-full rounded-xl px-4 py-2.5 pr-10 text-white
                      bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
               placeholder="e.g. 18"
               value="{{ old('depth', $log->depth) }}"/>
        <span class="pointer-events-none absolute right-3 top-[38px] text-sm text-slate-300">m</span>
      </label>

      <label class="block relative">
        <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Duration</span>
        <input type="number" name="duration" required
               class="w-full rounded-xl px-4 py-2.5 pr-12 text-white
                      bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
               placeholder="e.g. 45"
               value="{{ old('duration', $log->duration) }}"/>
        <span class="pointer-events-none absolute right-3 top-[38px] text-sm text-slate-300">min</span>
      </label>

      <label class="block relative">
        <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Visibility</span>
        <input type="number" step="0.1" name="visibility" required
               class="w-full rounded-xl px-4 py-2.5 pr-10 text-white
                      bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
               placeholder="e.g. 10"
               value="{{ old('visibility', $log->visibility) }}"/>
        <span class="pointer-events-none absolute right-3 top-[38px] text-sm text-slate-300">m</span>
      </label>
    </div>

    {{-- More details accordion --}}
    <details class="rounded-xl border border-white/10 ring-1 ring-white/10 bg-white/5 backdrop-blur-md p-4" open>
      <summary class="cursor-pointer text-cyan-300 font-semibold select-none">More dive details</summary>

      <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <input name="buddy" placeholder="Dive Buddy"
               class="rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
               value="{{ old('buddy', $log->buddy) }}">

        <input name="air_start" type="number" step="0.1" placeholder="Air Start (bar)"
               class="rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
               value="{{ old('air_start', $log->air_start) }}">

        <input name="air_end" type="number" step="0.1" placeholder="Air End (bar)"
               class="rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
               value="{{ old('air_end', $log->air_end) }}">

        <input name="temperature" type="number" step="0.1" placeholder="Water Temp (°C)"
               class="rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
               value="{{ old('temperature', $log->temperature) }}">

        <input name="suit_type" placeholder="Wetsuit / Drysuit"
               class="rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
               value="{{ old('suit_type', $log->suit_type) }}">

        <input name="tank_type" placeholder="Tank Type"
               class="rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
               value="{{ old('tank_type', $log->tank_type) }}">

        <input name="weight_used" placeholder="Weight Used (kg)"
               class="rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10"
               value="{{ old('weight_used', $log->weight_used) }}">

        <label class="sm:col-span-2">
          <span class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Rating</span>
          <select name="rating"
                  class="w-full rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10">
            <option value="">—</option>
            @for ($i = 1; $i <= 5; $i++)
              <option value="{{ $i }}" @selected(old('rating', $log->rating) == $i)>{{ $i }} star{{ $i>1?'s':'' }}</option>
            @endfor
          </select>
        </label>

        <textarea name="notes" rows="4" placeholder="Notes"
                  class="sm:col-span-2 rounded-xl px-4 py-2.5 text-white bg-white/10 backdrop-blur-md border border-white/10 ring-1 ring-white/10">{{ old('notes', $log->notes) }}</textarea>
      </div>
    </details>

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
      <button type="submit"
              class="group inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 font-semibold text-white
                     bg-gradient-to-r from-cyan-500/90 to-teal-400/90
                     hover:from-cyan-400/90 hover:to-teal-300/90
                     border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                     shadow-lg shadow-cyan-500/20 hover:-translate-y-0.5 transition">
        <span>Update Dive Log</span>
      </button>

      <a href="{{ $returnTo }}"
         class="inline-flex items-center justify-center rounded-full px-5 py-2.5 text-sm font-semibold
                text-white/90 bg-white/10 hover:bg-white/15 border border-white/10 ring-1 ring-white/10 backdrop-blur-md transition">
        Cancel
      </a>
    </div>
  </form>
</section>
@endsection

@push('scripts')
<script>
function editDive({ sites, initialId = null, initialName = '' }) {
  return {
    // state
    raw: sites || [],
    selectedId: initialId,
    query: initialName,
    open: false,
    focusedIndex: 0,
    diveSiteError: false,
    _t: null,

    // derived
    get filtered() {
      const q = (this.query || '').trim().toLowerCase();
      if (!q) return this.raw.slice(0, 20);
      return this.raw
        .map(s => ({ s, score: this.score(s.name, q) }))
        .filter(r => r.score > 0)
        .sort((a, b) => b.score - a.score)
        .slice(0, 20)
        .map(r => r.s);
    },

    // behaviors
    debounceFilter() {
      clearTimeout(this._t);
      this._t = setTimeout(() => { this.focusedIndex = 0; }, 120);
    },
    move(dir) {
      if (!this.filtered.length) return;
      this.focusedIndex = (this.focusedIndex + dir + this.filtered.length) % this.filtered.length;
    },
    select(index) {
      const site = this.filtered[index];
      if (!site) return;
      this.query = site.name;
      this.selectedId = site.id;
      this.open = false;
      this.diveSiteError = false;
    },
    validateDiveSite() {
      const ok = !!this.selectedId;
      this.diveSiteError = !ok;
      return ok;
    },

    // utils
    score(name, q) {
      const n = (name || '').toLowerCase();
      if (n === q) return 100;
      if (n.startsWith(q)) return 80;
      if (n.includes(q)) return 50;
      // lightweight subsequence
      let i = 0, hits = 0;
      for (const c of n) { if (c === q[i]) { hits++; i++; if (i >= q.length) break; } }
      return hits >= Math.max(2, Math.ceil(q.length * 0.6)) ? 25 : 0;
    },
    highlight(label, q) {
      if (!q) return this.escape(label);
      const L = (label || '').toString();
      const i = L.toLowerCase().indexOf(q.toLowerCase());
      if (i === -1) return this.escape(L);
      return this.escape(L.slice(0, i)) +
             '<mark class="bg-cyan-300/40 text-inherit rounded px-0.5">' +
             this.escape(L.slice(i, i + q.length)) +
             '</mark>' +
             this.escape(L.slice(i + q.length));
    },
    escape(s) {
      return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    },
  };
}
</script>
@endpush