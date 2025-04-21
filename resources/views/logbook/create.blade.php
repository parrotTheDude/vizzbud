@extends('layouts.vizzbud')

@section('content')
<section class="max-w-2xl mx-auto px-6 py-12">
    <h1 class="text-3xl font-bold text-white mb-6">➕ Log a Dive</h1>
    <form method="POST" action="{{ route('logbook.store') }}"
      class="space-y-4 bg-slate-800 p-6 rounded-xl shadow"
      x-data="{
          diveSiteError: false,
          validateDiveSite() {
              const valid = document.querySelector('[name=dive_site_id]').value !== '';
              this.diveSiteError = !valid;
              return valid;
          }
      }"
      @submit.prevent="if (validateDiveSite()) $el.submit();"
>
    @csrf

    <label class="block relative" x-data="diveSiteSelect({ sites: @js($siteOptions) })">
        <span class="block mb-1 text-sm text-white">Dive Site <span class="text-red-500">*</span></span>

        <input
            type="text"
            x-model="query"
            @focus="open = true"
            @click.away="open = false"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="select(focusedIndex)"
            placeholder="Search dive sites..."
            class="w-full rounded p-2 text-black"
        >

        <input type="hidden" name="dive_site_id" :value="selectedId">

        <ul x-show="open && filtered.length"
            class="absolute z-10 bg-white text-black rounded shadow w-full mt-1 max-h-60 overflow-y-auto border border-gray-300"
            x-transition>
            <template x-for="(site, index) in filtered" :key="site.id">
                <li
                    :class="{
                        'bg-cyan-100': index === focusedIndex,
                        'px-4 py-2 cursor-pointer': true
                    }"
                    @click="select(index)"
                    @mouseover="focusedIndex = index"
                    x-text="site.name"
                ></li>
            </template>
        </ul>
    </label>

    <!-- Inline error message -->
    <p x-show="diveSiteError" class="text-sm text-red-500 mt-1">Please select a dive site.</p>

        {{-- Date & Time --}}
        <label class="block">
            <span class="block mb-1 text-sm text-white">Dive Date</span>
            <input type="date"
                name="dive_date"
                required
                class="w-full rounded p-2 text-black"
                value="{{ old('dive_date', now()->format('Y-m-d')) }}"
                max="{{ now()->format('Y-m-d') }}">
            @error('dive_date')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </label>

        {{-- Depth & Duration --}}
<div class="grid grid-cols-2 gap-4">
    {{-- Depth Input with "m" --}}
    <label class="block relative">
        <span class="block mb-1 text-sm text-white">Depth</span>
        <input type="number" step="0.1" name="depth"
            class="w-full rounded p-2 pr-10 text-black"
            placeholder="e.g. 18"
            required />
        <span class="absolute right-3 top-[38px] text-slate-500 pointer-events-none">m</span>
    </label>

    {{-- Duration Input with "min" --}}
    <label class="block relative">
        <span class="block mb-1 text-sm text-white">Duration</span>
        <input type="number" name="duration"
            class="w-full rounded p-2 pr-12 text-black"
            placeholder="e.g. 45"
            required />
        <span class="absolute right-3 top-[38px] text-slate-500 pointer-events-none">min</span>
    </label>
</div>

        {{-- Expandable Extras --}}
        <details class="mt-4 bg-slate-700 rounded p-4">
            <summary class="cursor-pointer text-cyan-400 font-semibold">+ More dive details</summary>

            <div class="mt-4 grid gap-4">
                <input name="buddy" placeholder="Dive Buddy" class="rounded p-2 text-black" value="{{ old('buddy') }}">
                <input name="air_start" type="number" step="0.1" placeholder="Air Start (bar)" class="rounded p-2 text-black" value="{{ old('air_start') }}">
                <input name="air_end" type="number" step="0.1" placeholder="Air End (bar)" class="rounded p-2 text-black" value="{{ old('air_end') }}">
                <input name="temperature" type="number" step="0.1" placeholder="Water Temp °C" class="rounded p-2 text-black" value="{{ old('temperature') }}">
                <input name="suit_type" placeholder="Wetsuit/Drysuit Type" class="rounded p-2 text-black" value="{{ old('suit_type') }}">
                <input name="tank_type" placeholder="Tank Type" class="rounded p-2 text-black" value="{{ old('tank_type') }}">
                <input name="weight_used" placeholder="Weight Used (kg)" class="rounded p-2 text-black" value="{{ old('weight_used') }}">
                <input name="visibility" type="number" step="0.1" placeholder="Visibility (m)" class="rounded p-2 text-black" value="{{ old('visibility') }}">

                <textarea name="notes" rows="3" placeholder="Notes" class="rounded p-2 text-black">{{ old('notes') }}</textarea>

                <label class="block text-sm text-white">
                    Rating
                    <select name="rating" class="mt-1 w-full rounded p-2 text-black">
                        <option value="">—</option>
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" @selected(old('rating') == $i)>
                                {{ $i }} star{{ $i > 1 ? 's' : '' }}
                            </option>
                        @endfor
                    </select>
                </label>
            </div>
        </details>

        <button type="submit" class="mt-6 bg-green-500 hover:bg-green-600 px-6 py-2 rounded font-semibold text-white">
            ✅ Save Dive Log
        </button>
    </form>
</section>
@endsection

@push('scripts')
<script>
function diveSiteSelect({ sites }) {
    return {
        sites,
        query: '',
        open: false,
        selectedId: null,
        focusedIndex: 0,

        get filtered() {
            const q = this.query.toLowerCase();
            return this.sites.filter(site => site.name.toLowerCase().includes(q));
        },

        move(direction) {
            if (!this.filtered.length) return;
            this.focusedIndex = (this.focusedIndex + direction + this.filtered.length) % this.filtered.length;
        },

        select(index) {
            const site = this.filtered[index];
            if (site) {
                this.query = site.name;
                this.selectedId = site.id;
                this.open = false;

                // Autofocus next form field if desired
                setTimeout(() => {
                    const nextInput = document.querySelector('input[name="dive_date"]');
                    if (nextInput) nextInput.focus();
                }, 100);
            }
        }
    };
}
</script>
@endpush