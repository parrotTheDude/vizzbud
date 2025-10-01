@extends('layouts.vizzbud')

@section('title', 'Log a New Dive | Vizzbud')
@section('meta_description', 'Record a new scuba dive including site, depth, duration, gear, and conditions. Keep your dive history organized with Vizzbud.')

@section('content')
<section class="max-w-2xl mx-auto px-4 sm:px-6 py-12">

    {{-- Back Button --}}
    <div class="mb-6">
        <a href="{{ route('logbook.index') }}"
            class="inline-flex items-center gap-2 text-cyan-400 hover:text-cyan-300 font-semibold text-sm sm:text-base transition">
            @include('components.icon', ['name' => 'back'])
            <span>Back to Dive Log</span>
        </a>
    </div>

    <form method="POST" action="{{ route('logbook.store') }}"
        class="bg-slate-800 p-6 rounded-xl shadow space-y-6"
        x-data="{
            step: 1,
            autoTitle: true,
            query: '',
            selectedId: '',
            diveSiteError: false,
            open: false,
            focusedIndex: 0,
            sites: @js($siteOptions),

            get filteredSites() {
                return this.sites.filter(site => site.name.toLowerCase().includes(this.query.toLowerCase()));
            },

            move(direction) {
                if (!this.filteredSites.length) return;
                this.focusedIndex = (this.focusedIndex + direction + this.filteredSites.length) % this.filteredSites.length;
            },

            select(index) {
                const site = this.filteredSites[index];
                if (site) {
                    this.query = site.name;
                    this.selectedId = site.id;
                    if (this.autoTitle) this.$refs.title.value = site.name;
                    this.open = false;
                    setTimeout(() => this.$refs.diveDate?.focus(), 100);
                }
            },

            validateDiveSite(show = false) {
                const valid = this.selectedId !== '';
                if (show) this.diveSiteError = !valid;
                return valid;
            },

            canProceedToStep2() {
                return this.validateDiveSite()
                    && this.$refs.title.value.trim() !== ''
                    && this.$refs.diveDate.value !== ''
                    && this.$refs.depth.value !== ''
                    && this.$refs.duration.value !== ''
                    && this.$refs.visibility.value !== '';
            }
        }"
        x-init="
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            $refs.diveDate.value = now.toISOString().slice(0, 16);
        "
        @submit.prevent="step === 1 ? (canProceedToStep2() ? step = 2 : null) : $el.submit()"
    >
        @csrf

        {{-- Step 1 --}}
        <div x-show="step === 1" x-transition>
            {{-- Dive Site --}}
            <label class="block relative">
                <span class="block mb-1 text-sm text-white">Dive Site <span class="text-red-500">*</span></span>
                <input type="text" x-model="query" @focus="open = true" @click.away="open = false"
                    @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                    @keydown.enter.prevent="select(focusedIndex)"
                    class="w-full rounded p-2 text-black" placeholder="Search dive sites...">
                <input type="hidden" name="dive_site_id" x-model="selectedId" x-ref="diveSiteId">
                <ul x-show="open && filteredSites.length"
                    class="absolute z-10 bg-white text-black rounded shadow w-full mt-1 max-h-60 overflow-y-auto border border-gray-300"
                    x-transition>
                    <template x-for="(site, index) in filteredSites" :key="site.id">
                        <li :class="{ 'bg-cyan-100': index === focusedIndex, 'px-4 py-2 cursor-pointer': true }"
                            @click="select(index)" @mouseover="focusedIndex = index"
                            x-text="site.name">
                        </li>
                    </template>
                </ul>
            </label>
            <p x-show="diveSiteError && step === 1" class="text-sm text-red-500 mt-1">Please select a dive site.</p>

            {{-- Title --}}
            <label class="block">
                <span class="block mb-1 text-sm text-white">Dive Title</span>
                <input type="text" name="title" x-ref="title" @input="autoTitle = false"
                    class="w-full rounded p-2 text-black" placeholder="e.g. Bare Island Fun Dive">
            </label>

            {{-- Date & Time --}}
            <label class="block">
                <span class="block mb-1 text-sm text-white">Dive Date & Time</span>
                <input type="datetime-local" name="dive_date" x-ref="diveDate" required
                    class="w-full rounded p-2 text-black">
            </label>

            {{-- Depth / Duration / Vis --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <label class="block relative">
                    <span class="block mb-1 text-sm text-white">Depth</span>
                    <input type="number" step="0.1" name="depth" x-ref="depth" required
                        class="w-full rounded p-2 pr-10 text-black" placeholder="e.g. 18">
                    <span class="absolute right-3 top-[38px] text-slate-500 pointer-events-none">m</span>
                </label>
                <label class="block relative">
                    <span class="block mb-1 text-sm text-white">Duration</span>
                    <input type="number" name="duration" x-ref="duration" required
                        class="w-full rounded p-2 pr-12 text-black" placeholder="e.g. 45">
                    <span class="absolute right-3 top-[38px] text-slate-500 pointer-events-none">min</span>
                </label>
                <label class="block relative">
                    <span class="block mb-1 text-sm text-white">Visibility</span>
                    <input type="number" step="0.1" name="visibility" x-ref="visibility" required
                        class="w-full rounded p-2 pr-12 text-black" placeholder="e.g. 10">
                    <span class="absolute right-3 top-[38px] text-slate-500 pointer-events-none">m</span>
                </label>
            </div>

            <button type="button"
                @click="if (validateDiveSite(true) && canProceedToStep2()) step = 2"
                :disabled="!canProceedToStep2()"
                class="mt-6 bg-cyan-500 hover:bg-cyan-600 disabled:opacity-50 disabled:cursor-not-allowed px-6 py-2 rounded font-semibold text-white w-full sm:w-auto">
                Next
            </button>
        </div>

        {{-- Step 2 --}}
        <div x-show="step === 2" x-transition>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <input name="buddy" placeholder="Dive Buddy" class="rounded p-2 text-black">
                <input name="air_start" type="number" step="0.1" placeholder="Air Start (bar)" class="rounded p-2 text-black">
                <input name="air_end" type="number" step="0.1" placeholder="Air End (bar)" class="rounded p-2 text-black">
                <input name="temperature" type="number" step="0.1" placeholder="Water Temp °C" class="rounded p-2 text-black">
                <input name="suit_type" placeholder="Wetsuit/Drysuit Type" class="rounded p-2 text-black">
                <input name="tank_type" placeholder="Tank Type" class="rounded p-2 text-black">
                <input name="weight_used" placeholder="Weight Used (kg)" class="rounded p-2 text-black">
                <textarea name="notes" rows="3" placeholder="Notes" class="rounded p-2 text-black sm:col-span-2"></textarea>

                <label class="block text-sm text-white sm:col-span-2">
                    Rating
                    <select name="rating" class="mt-1 w-full rounded p-2 text-black">
                        <option value="">—</option>
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                </label>
            </div>

            <div class="mt-6 flex justify-between">
                <button type="button" @click="step = 1"
                    class="bg-slate-700 hover:bg-slate-600 px-6 py-2 rounded font-semibold text-white">
                    ← Back
                </button>
                <button type="submit"
                    class="bg-green-500 hover:bg-green-600 px-6 py-2 rounded font-semibold text-white">
                    ✅ Add Dive
                </button>
            </div>
        </div>
    </form>
</section>
@endsection