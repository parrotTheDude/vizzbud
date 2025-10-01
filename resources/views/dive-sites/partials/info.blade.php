<template x-if="selectedSite">
  <!-- Wrapper: no background, just spacing & scroll -->
  <div class="space-y-6 text-left px-1 sm:px-2 text-slate-900">

    <!-- 🧭 Site Name (transparent) -->
    <div class="px-3 sm:px-4">
      <h2 class="text-2xl sm:text-3xl pt-1 font-bold text-cyan-700" x-text="selectedSite.name"></h2>
      <p class="text-xs sm:text-sm text-slate-700/90 mt-1" x-text="selectedSite.description"></p>

      <!-- 🔗 View Full Dive Site Page -->
      <div class="pt-3">
        <a
          href="#"
          @click.prevent="
            localStorage.setItem('vizzbud_last_site', selectedSite.slug);
            window.location.href = `/dive-sites/${selectedSite.slug}?lat=${map.getCenter().lat.toFixed(5)}&lng=${map.getCenter().lng.toFixed(5)}&zoom=${map.getZoom()}`
          "
          class="block w-full text-center text-sm font-semibold
                 rounded-full px-4 py-2
                 bg-cyan-500/90 hover:bg-cyan-500 text-white
                 shadow-md transition"
        >
          View Full Dive Site Page
        </a>
      </div>
    </div>

    <!-- 📌 Dive Site Info (glassy cards only) -->
    <div class="px-1 sm:px-2">
      <h3 class="px-2 text-base sm:text-lg font-semibold text-slate-800 mb-3">Dive Site Info</h3>
      <div class="grid grid-cols-2 gap-3 sm:gap-4 text-xs sm:text-sm">
        <div class="flex items-center gap-3 rounded-xl p-3
                    bg-white/30 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/pool-depth.svg" class="w-5 h-5" alt="Avg Depth">
          <span><strong>Avg Depth</strong><br><span x-text="`${selectedSite.avg_depth}m`"></span></span>
        </div>

        <div class="flex items-center gap-3 rounded-xl p-3
                    bg-white/30 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/under-water.svg" class="w-5 h-5 opacity-80" alt="Max Depth">
          <span><strong>Max Depth</strong><br><span x-text="`${selectedSite.max_depth}m`"></span></span>
        </div>

        <div class="flex items-center gap-3 rounded-xl p-3
                    bg-white/30 backdrop-blur-md border border-white/30 shadow-sm">
          <img :src="selectedSite.dive_type === 'shore' ? '/icons/beach.svg' : '/icons/boat.svg'"
               class="w-5 h-5" alt="Entry Type">
          <span>
            <strong>Entry</strong><br>
            <span x-text="selectedSite.dive_type
              ? selectedSite.dive_type.charAt(0).toUpperCase() + selectedSite.dive_type.slice(1)
              : '—'"></span>
          </span>
        </div>

        <div class="flex items-center gap-3 rounded-xl p-3
                    bg-white/30 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/diver.svg" class="w-5 h-5" alt="Level">
          <span><strong>Level</strong><br><span x-text="selectedSite.suitability ?? '—'"></span></span>
        </div>
      </div>
    </div>

    <!-- 🌊 Current Conditions (glassy cards only) -->
    <div class="px-1 sm:px-2">
      <h3 class="px-2 text-base sm:text-lg font-semibold text-slate-800 mb-3">Current Conditions</h3>
      <div class="grid grid-cols-2 gap-3 sm:gap-4 text-xs sm:text-sm">
        <div class="flex items-center gap-3 rounded-xl p-3
                    bg-white/30 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/temperature.svg" class="w-5 h-5" alt="Water Temp">
          <span><strong>Water Temp</strong><br>
            <span x-text="selectedSite.conditions?.waterTemperature?.noaa ?? '—'"></span>°C
          </span>
        </div>

        <div class="flex items-center gap-3 rounded-xl p-3
                    bg-white/30 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/temperature.svg" class="w-5 h-5" alt="Air Temp">
          <span><strong>Air Temp</strong><br>
            <span x-text="selectedSite.conditions?.airTemperature?.noaa ?? '—'"></span>°C
          </span>
        </div>

        <div class="flex items-center gap-3 rounded-xl p-3
                    bg-white/30 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/wave.svg" class="w-5 h-5" alt="Swell">
          <span>
            <strong>Swell</strong><br>
            <span x-text="selectedSite.conditions?.waveHeight?.noaa ?? '—'"></span>m @
            <span x-text="selectedSite.conditions?.wavePeriod?.noaa ?? '—'"></span>s
          </span>
        </div>

        <div class="flex items-center gap-3 rounded-xl p-3
                    bg-white/30 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/compass.svg" class="w-5 h-5" alt="Swell Dir">
          <div>
            <strong>Swell Direction</strong><br>
            <div class="flex items-center gap-1">
              <span x-text="compass(selectedSite.conditions?.waveDirection?.noaa)"></span>
              <img src="/icons/arrow.svg" class="w-4 h-4"
                   :style="`transform: rotate(${(selectedSite.conditions?.waveDirection?.noaa || 0) + 90}deg)`"
                   alt="Arrow">
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3 rounded-xl p-3
                    bg-white/30 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/wind.svg" class="w-5 h-5" alt="Wind Speed">
          <span>
            <strong>Wind Speed</strong><br>
            <span x-text="selectedSite.conditions?.windSpeed?.noaa
                ? (selectedSite.conditions.windSpeed.noaa * 1.94384).toFixed(1)
                : '—'"></span> kts
          </span>
        </div>

        <div class="flex items-center gap-3 rounded-xl p-3
                    bg-white/30 backdrop-blur-md border border-white/30 shadow-sm">
          <img src="/icons/compass.svg" class="w-5 h-5" alt="Wind Dir">
          <div>
            <strong>Wind Direction</strong><br>
            <div class="flex items-center gap-1">
              <span x-text="compass(selectedSite.conditions?.windDirection?.noaa)"></span>
              <img src="/icons/arrow.svg" class="w-4 h-4"
                   :style="`transform: rotate(${(selectedSite.conditions?.windDirection?.noaa || 0) + 90}deg)`"
                   alt="Arrow">
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 text-[11px] text-slate-600 sm:col-span-2">
          <img src="/icons/update.svg" class="w-3 h-3" alt="Updated">
          <span><strong>Updated:</strong> <span x-text="formatDate(selectedSite.retrieved_at)"></span></span>
        </div>
      </div>
    </div>

    <!-- 📈 Swell Height Forecast Chart (glassy container) -->
    <div class="px-1 sm:px-2" x-show="selectedSite.forecast && selectedSite.forecast.length">
      <h3 class="px-2 text-base sm:text-lg font-semibold text-slate-800 mb-3">📈 Swell Height (Next 48 Hours)</h3>
      <div class="w-full h-64 rounded-xl overflow-hidden
                  bg-white/20 backdrop-blur-md border border-white/20 shadow-sm p-3">
        <canvas id="swellChart"></canvas>
      </div>
      <div class="mt-2 text-[11px] text-slate-600 flex items-center gap-1 px-2">
        <img src="/icons/update.svg" class="w-3 h-3" alt="Updated">
        <span><strong>Updated:</strong> <span x-text="formatDate(selectedSite.forecast_updated_at)"></span></span>
      </div>
    </div>

  </div>
</template>