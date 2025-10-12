<template x-if="selectedSite">
  <div class="space-y-3 sm:space-y-4 px-1 sm:px-4 text-slate-900 max-w-full">

    <!-- Header card: Name + Live status -->
    <section class="bg-white/35 backdrop-blur-xl border border-white/30 ring-1 ring-white/20 rounded-2xl shadow-sm p-4">
      <div class="flex items-center justify-between gap-3">
        <h2 class="text-xl sm:text-2xl font-bold text-cyan-700" x-text="selectedSite.name"></h2>

        <!-- Live status chip (same line) -->
        <div
          class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium
                 backdrop-blur-sm border shadow-sm"
          :class="(() => {
            const s = (selectedSite?.status || '').toLowerCase();
            if (s === 'green')  return 'bg-emerald-500/15 text-emerald-700 border-emerald-400/20';
            if (s === 'yellow') return 'bg-amber-400/20 text-amber-800 border-amber-400/30';
            if (s === 'red')    return 'bg-rose-500/15 text-rose-700 border-rose-400/20';
            return 'bg-slate-500/10 text-slate-700 border-slate-400/20';
          })()"
          x-show="selectedSite?.status"
        >
          <!-- Pulsing dot -->
          <span class="relative inline-flex w-2.5 h-2.5">
            <!-- soft pulse -->
            <span
              class="absolute inset-0 rounded-full opacity-60 animate-ping"
              :class="(() => {
                const s = (selectedSite?.status || '').toLowerCase();
                if (s === 'green')  return 'bg-emerald-400';
                if (s === 'yellow') return 'bg-amber-400';
                if (s === 'red')    return 'bg-rose-500';
                return 'bg-slate-400';
              })()"
            ></span>
            <!-- solid core -->
            <span
              class="relative inline-flex w-2.5 h-2.5 rounded-full"
              :class="(() => {
                const s = (selectedSite?.status || '').toLowerCase();
                if (s === 'green')  return 'bg-emerald-500';
                if (s === 'yellow') return 'bg-amber-400';
                if (s === 'red')    return 'bg-rose-500';
                return 'bg-slate-400';
              })()"
            ></span>
          </span>
          <span class="uppercase tracking-wide text-[11px] opacity-70">Live</span>
          <span x-text="(() => {
            const s = (selectedSite?.status || '').toLowerCase();
            if (s === 'green')  return 'Good now';
            if (s === 'yellow') return 'Fair now';
            if (s === 'red')    return 'Poor now';
            return 'Unavailable';
          })()"></span>
        </div>
      </div>

      <!-- Mini info pills -->
      <div class="mt-3 grid grid-cols-3 gap-2 text-center">
        <div class="rounded-lg px-3 py-2 text-xs bg-white/40 border border-white/30 backdrop-blur-md shadow-sm flex flex-col items-center">
          <img src="/icons/under-water.svg" class="w-4 h-4 mb-1" alt="Max Depth">
          <div class="font-medium">Max</div>
          <div x-text="`${selectedSite.max_depth ?? '—'}m`" class="text-[11px] text-slate-600"></div>
        </div>
        <div class="rounded-lg px-3 py-2 text-xs bg-white/40 border border-white/30 backdrop-blur-md shadow-sm flex flex-col items-center">
          <img :src="selectedSite.dive_type === 'shore' ? '/icons/beach.svg' : '/icons/boat.svg'" class="w-4 h-4 mb-1" alt="Entry">
          <div class="font-medium">Entry</div>
          <div class="text-[11px] text-slate-600"
               x-text="selectedSite.dive_type ? selectedSite.dive_type.charAt(0).toUpperCase() + selectedSite.dive_type.slice(1) : '—'"></div>
        </div>
        <div class="rounded-lg px-3 py-2 text-xs bg-white/40 border border-white/30 backdrop-blur-md shadow-sm flex flex-col items-center">
          <img src="/icons/diver.svg" class="w-4 h-4 mb-1" alt="Level">
          <div class="font-medium">Level</div>
          <div class="text-[11px] text-slate-600" x-text="selectedSite.suitability ?? '—'"></div>
        </div>
      </div>

      <!-- Full page link (brand pill) -->
      <div class="mt-3">
        <a
          href="#"
          @click.prevent="
            localStorage.setItem('vizzbud_last_site', selectedSite.slug);
            window.location.href = `/dive-sites/${selectedSite.slug}?lat=${map.getCenter().lat.toFixed(5)}&lng=${map.getCenter().lng.toFixed(5)}&zoom=${map.getZoom()}`
          "
          class="block w-full rounded-full px-5 py-2.5 text-center text-sm font-semibold 
                 text-white shadow-md transition-all duration-300 
                 bg-gradient-to-r from-cyan-600 via-cyan-500 to-cyan-600
                 hover:shadow-lg hover:scale-[1.02] hover:from-cyan-500 hover:to-cyan-400
                 focus:outline-none focus:ring-2 focus:ring-cyan-300"
        >
          <span class="flex items-center justify-center gap-2">
            View Full Dive Site
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </span>
        </a>
      </div>
    </section>

    <!-- 🌅 Today’s Diveability (matching Live style, adjusted colours) -->
    <section class="bg-white/30 backdrop-blur-xl border border-white/30 ring-1 ring-white/10 rounded-2xl shadow-sm p-4"
            x-show="selectedSite?.today_summary" x-transition>
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-800">Today’s Diveability</h3>
        <span class="text-[11px] text-slate-500">6AM–12PM • 12PM–5PM • 5PM–9PM</span>
      </div>

      <div class="mt-3 grid grid-cols-3 gap-2">
        <template x-for="part in ['morning','afternoon','night']" :key="part">
          <!-- pill -->
          <div
            class="inline-flex items-center justify-center gap-2 rounded-full px-3 py-2 text-xs font-medium
                  backdrop-blur-sm border shadow-sm transition-colors"
            :class="(() => {
              const s = (selectedSite?.today_summary?.[part] || '').toLowerCase();
              if (s === 'green')  return 'bg-emerald-400/30 text-emerald-800 border-emerald-500/30';
              if (s === 'yellow') return 'bg-amber-400/25 text-amber-800 border-amber-400/30';
              if (s === 'red')    return 'bg-rose-500/15 text-rose-700 border-rose-400/25';
              return 'bg-slate-500/10 text-slate-700 border-slate-400/20';
            })()"
            :title="`${part[0].toUpperCase()+part.slice(1)} — ${(selectedSite?.today_summary?.[part] || 'unknown').toUpperCase()}`"
          >
            <!-- icon (invert only for red) -->
            <img
              :src="`/icons/${part}.svg`"
              class="w-5 h-5"
              :style="(() => {
                const s = (selectedSite?.today_summary?.[part] || '').toLowerCase();
                // only invert for dark red backgrounds
                if (s === 'red') return 'filter: invert(1);';
                return '';
              })()"
              :alt="part + ' icon'"
            >
            <!-- label -->
            <span class="capitalize" x-text="part"></span>
          </div>
        </template>
      </div>
    </section>

   <!-- 🌊 Current Conditions -->
    <section class="bg-white/30 backdrop-blur-xl border border-white/30 ring-1 ring-white/10 rounded-2xl shadow-sm p-4">
      <h3 class="text-sm font-semibold text-slate-800">Current Conditions</h3>

      <div class="mt-3 grid grid-cols-2 gap-3 sm:gap-4 text-xs">
        <!-- 🌡️ Water -->
        <div class="flex items-center gap-3 rounded-lg p-3 bg-white/40 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/temperature.svg" class="w-5 h-5" alt="Water Temp">
          <span>
            <strong>Water</strong><br>
            <span x-text="selectedSite.conditions?.waterTemperature?.noaa ?? '—'"></span>°C
          </span>
        </div>

        <!-- 💨 Wind -->
        <div class="flex items-center gap-3 rounded-lg p-3 bg-white/40 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/wind.svg" class="w-5 h-5" alt="Wind">
          <div>
            <strong>Wind</strong><br>
            <div class="flex items-center gap-1">
              <span x-text="selectedSite.conditions?.windSpeed?.noaa
                  ? (selectedSite.conditions.windSpeed.noaa * 1.94384).toFixed(1)
                  : '—'"></span> kts
              <span class="text-slate-500 text-[11px]"
                    x-text="'(' + compass(selectedSite.conditions?.windDirection?.noaa) + ')'"></span>
              <img src="/icons/arrow.svg"
                  class="w-4 h-4 opacity-70"
                  :style="`transform: rotate(${(selectedSite.conditions?.windDirection?.noaa || 0) + 90}deg)`"
                  alt="Wind direction">
            </div>
          </div>
        </div>

        <!-- 🌊 Swell (Height & Direction) -->
        <div class="flex items-center gap-3 rounded-lg p-3 bg-white/40 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/wave.svg" class="w-5 h-5" alt="Swell">
          <div>
            <strong>Swell</strong><br>
            <div class="flex items-center gap-1">
              <span x-text="selectedSite.conditions?.waveHeight?.noaa ?? '—'"></span> m
              <span class="text-slate-400">•</span>
              <span class="text-slate-600" x-text="compass(selectedSite.conditions?.waveDirection?.noaa)"></span>
              <img src="/icons/arrow.svg"
                  class="w-4 h-4 opacity-70"
                  :style="`transform: rotate(${(selectedSite.conditions?.waveDirection?.noaa || 0) + 90}deg)`"
                  alt="Swell direction">
            </div>
          </div>
        </div>

        <!-- ⏱️ Period (Interval) -->
        <div class="flex items-center gap-3 rounded-lg p-3 bg-white/40 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/clock.svg" class="w-5 h-5" alt="Period">
          <div>
            <strong>Period</strong><br>
            <span x-text="selectedSite.conditions?.wavePeriod?.noaa ?? '—'"></span> s
            <span class="text-[11px] text-slate-500/80">(interval)</span>
          </div>
        </div>

        <!-- 🕒 Updated -->
        <div class="flex items-center justify-center gap-2 text-[11px] text-slate-600 col-span-2">
          <img src="/icons/update.svg" class="w-3 h-3" alt="Updated">
          <span><strong>Updated:</strong> <span x-text="formatDate(selectedSite.retrieved_at)"></span></span>
        </div>
      </div>
    </section>

    <!-- Forecast -->
    @php($chartId = $chartId ?? 'swellChart')
    <section class="bg-white/30 backdrop-blur-xl border border-white/30 ring-1 ring-white/10 rounded-2xl shadow-sm p-4"
             x-show="selectedSite.forecast && selectedSite.forecast.length">
      <h3 class="text-sm font-semibold text-slate-800 mb-3">Swell Forecast (Next 24h)</h3>
      <div class="w-full h-64 rounded-xl overflow-hidden bg-white/20 backdrop-blur-md border border-white/20 shadow-sm p-3">
        <canvas id="{{ $chartId }}"></canvas>
      </div>
      <div class="mt-2 text-[11px] text-slate-600 flex items-center justify-center gap-1">
        <img src="/icons/update.svg" class="w-3 h-3" alt="Updated">
        <span><strong>Updated:</strong> <span x-text="formatDate(selectedSite.forecast_updated_at)"></span></span>
      </div>
    </section>

  </div>
</template>