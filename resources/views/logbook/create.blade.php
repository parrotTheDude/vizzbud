@extends('layouts.vizzbud')

@section('content')
<section class="max-w-2xl mx-auto px-4 sm:px-6 py-12">

    {{-- Back Button --}}
    <div class="mb-6">
        <a href="{{ route('logbook.index') }}"
           class="inline-block bg-slate-700 hover:bg-slate-600 text-cyan-300 font-semibold px-4 py-2 rounded text-sm transition">
            ← Back to Dive Log
        </a>
    </div>

    <form method="POST" action="{{ route('logbook.store') }}"
        class="space-y-6 bg-slate-800 p-6 rounded-xl shadow"
        x-data="{
            diveSiteError: false,
            validateDiveSite() {
                const valid = document.querySelector('[name=dive_site_id]').value !== '';
                this.diveSiteError = !valid;
                return valid;
            }
        }"
        x-init="
            const now = new Date();
            const offset = now.getTimezoneOffset();
            now.setMinutes(now.getMinutes() - offset); // Convert to local timezone
            $refs.diveDate.value = now.toISOString().slice(0, 16);
        "
        @submit.prevent="if (validateDiveSite()) $el.submit();"
    >
        @csrf

        {{-- Dive Site Search --}}
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

        <p x-show="diveSiteError" class="text-sm text-red-500 mt-1">Please select a dive site.</p>

        {{-- Dive Date --}}
        <label class="block">
            <span class="block mb-1 text-sm text-white">Dive Date</span>
            <input type="datetime-local"
                name="dive_date"
                required
                class="w-full rounded p-2 text-black"
                x-ref="diveDate"
            >
            @error('dive_date')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </label>

        {{-- Depth, Duration & Visibility --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Depth --}}
            <label class="block relative">
                <span class="block mb-1 text-sm text-white">Depth</span>
                <input type="number" step="0.1" name="depth"
                    class="w-full rounded p-2 pr-10 text-black"
                    placeholder="e.g. 18"
                    required />
                <span class="absolute right-3 top-[38px] text-slate-500 pointer-events-none">m</span>
            </label>

            {{-- Duration --}}
            <label class="block relative">
                <span class="block mb-1 text-sm text-white">Duration</span>
                <input type="number" name="duration"
                    class="w-full rounded p-2 pr-12 text-black"
                    placeholder="e.g. 45"
                    required />
                <span class="absolute right-3 top-[38px] text-slate-500 pointer-events-none">min</span>
            </label>

            {{-- Visibility --}}
            <label class="block relative">
                <span class="block mb-1 text-sm text-white">Visibility</span>
                <input type="number" step="0.1" name="visibility"
                    class="w-full rounded p-2 pr-12 text-black"
                    placeholder="e.g. 10"
                    required />
                <span class="absolute right-3 top-[38px] text-slate-500 pointer-events-none">m</span>
            </label>
        </div>

        {{-- More Dive Details --}}
        <details class="mt-2 bg-slate-700 rounded p-4">
            <summary class="cursor-pointer text-cyan-400 font-semibold">More dive details</summary>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <input name="buddy" placeholder="Dive Buddy" class="rounded p-2 text-black" value="{{ old('buddy') }}">
                <input name="air_start" type="number" step="0.1" placeholder="Air Start (bar)" class="rounded p-2 text-black" value="{{ old('air_start') }}">
                <input name="air_end" type="number" step="0.1" placeholder="Air End (bar)" class="rounded p-2 text-black" value="{{ old('air_end') }}">
                <input name="temperature" type="number" step="0.1" placeholder="Water Temp °C" class="rounded p-2 text-black" value="{{ old('temperature') }}">
                <input name="suit_type" placeholder="Wetsuit/Drysuit Type" class="rounded p-2 text-black" value="{{ old('suit_type') }}">
                <input name="tank_type" placeholder="Tank Type" class="rounded p-2 text-black" value="{{ old('tank_type') }}">
                <input name="weight_used" placeholder="Weight Used (kg)" class="rounded p-2 text-black" value="{{ old('weight_used') }}">
                
                <textarea name="notes" rows="3" placeholder="Notes" class="rounded p-2 text-black sm:col-span-2">{{ old('notes') }}</textarea>

                <label class="block text-sm text-white sm:col-span-2">
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

        <button type="submit" class="mt-6 bg-green-500 hover:bg-green-600 px-6 py-2 rounded font-semibold text-white w-full sm:w-auto">
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

                // Focus the dive date field after selection
                setTimeout(() => {
                    this.open = false;
                    const nextInput = document.querySelector('input[name="dive_date"]');
                    if (nextInput) nextInput.focus();
                }, 150);
            }
        }
    };
}
</script>
@endpush