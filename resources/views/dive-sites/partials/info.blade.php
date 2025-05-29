<template x-if="selectedSite">
    <div class="bg-white text-black space-y-8 text-left h-full overflow-y-auto">

    <!-- 🧭 Site Name -->
    <div>
        <h2 class="text-3xl pt-2 font-bold text-cyan-600" x-text="selectedSite.name"></h2>
        <p class="text-sm text-gray-700 mt-1" x-text="selectedSite.description"></p>

        <!-- 🔗 View Full Dive Site Page -->
        <div class="pt-3">
            <a
                :href="`/dive-sites/${selectedSite.slug}`"
                class="block w-full bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold px-4 py-2 rounded-md text-center transition"
            >
                🔎 View Full Dive Site Page
            </a>
        </div>
    </div>

        <!-- 📌 Dive Site Info -->
        <div>
            <h3 class="text-lg font-semibold text-slate-700 mb-3">📍 Dive Site Info</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <!-- Avg Depth -->
                <div class="flex items-center space-x-3 bg-slate-100 p-3 rounded-md">
                    <img src="/icons/pool-depth.svg" class="w-5 h-5" alt="Avg Depth">
                    <span><strong>Avg Depth</strong><br><span x-text="`${selectedSite.avg_depth}m`"></span></span>
                </div>
                <!-- Max Depth -->
                <div class="flex items-center space-x-3 bg-slate-100 p-3 rounded-md">
                    <img src="/icons/under-water.svg" class="w-5 h-5 opacity-70" alt="Max Depth">
                    <span><strong>Max Depth</strong><br><span x-text="`${selectedSite.max_depth}m`"></span></span>
                </div>
                <!-- Entry -->
                <div class="flex items-center space-x-3 bg-slate-100 p-3 rounded-md">
                    <img 
                        :src="selectedSite.dive_type === 'shore' 
                            ? '/icons/beach.svg' 
                            : '/icons/boat.svg'" 
                        class="w-5 h-5" 
                        alt="Entry Type">
                    <span>
                        <strong>Entry</strong><br>
                        <span x-text="selectedSite.dive_type 
                            ? selectedSite.dive_type.charAt(0).toUpperCase() + selectedSite.dive_type.slice(1) 
                            : '—'"></span>
                    </span>
                </div>
                <!-- Level -->
                <div class="flex items-center space-x-3 bg-slate-100 p-3 rounded-md">
                    <img src="/icons/diver.svg" class="w-5 h-5" alt="Level">
                    <span><strong>Level</strong><br><span x-text="selectedSite.suitability ?? '—'"></span></span>
                </div>
            </div>
        </div>

        <!-- 🌊 Current Conditions -->
        <div>
            <h3 class="text-lg font-semibold text-slate-700 mb-3">🌊 Current Conditions</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <!-- Water Temp -->
                <div class="flex items-center space-x-3 bg-slate-100 p-3 rounded-md">
                    <img src="/icons/temperature.svg" class="w-5 h-5" alt="Water Temp">
                    <span><strong>Water Temp</strong><br><span x-text="selectedSite.conditions?.waterTemperature?.noaa ?? '—'"></span>°C</span>
                </div>
                <!-- Air Temp -->
                <div class="flex items-center space-x-3 bg-slate-100 p-3 rounded-md">
                    <img src="/icons/temperature.svg" class="w-5 h-5" alt="Air Temp">
                    <span><strong>Air Temp</strong><br><span x-text="selectedSite.conditions?.airTemperature?.noaa ?? '—'"></span>°C</span>
                </div>
                <!-- Swell -->
                <div class="flex items-center space-x-3 bg-slate-100 p-3 rounded-md">
                    <img src="/icons/wave.svg" class="w-5 h-5" alt="Swell">
                    <span>
                        <strong>Swell</strong><br>
                        <span x-text="selectedSite.conditions?.waveHeight?.noaa ?? '—'"></span>m @ 
                        <span x-text="selectedSite.conditions?.wavePeriod?.noaa ?? '—'"></span>s
                    </span>
                </div>
                <!-- Swell Direction -->
                <div class="flex items-center space-x-3 bg-slate-100 p-3 rounded-md">
                    <img src="/icons/compass.svg" class="w-5 h-5" alt="Swell Dir">
                    <div>
                        <strong>Swell Direction</strong><br>
                        <div class="flex items-center space-x-1">
                            <span x-text="compass(selectedSite.conditions?.waveDirection?.noaa)"></span>
                            <img src="/icons/arrow.svg" class="w-4 h-4"
                                :style="`transform: rotate(${(selectedSite.conditions?.waveDirection?.noaa || 0) + 90}deg)`"
                                alt="Arrow">
                        </div>
                    </div>
                </div>

                <!-- Wind Speed -->
                <div class="flex items-center space-x-3 bg-slate-100 p-3 rounded-md">
                    <img src="/icons/wind.svg" class="w-5 h-5" alt="Wind Speed">
                    <span>
                        <strong>Wind Speed</strong><br>
                        <span 
                            x-text="selectedSite.conditions?.windSpeed?.noaa 
                                ? (selectedSite.conditions.windSpeed.noaa * 1.94384).toFixed(1) 
                                : '—'">
                        </span>kts
                    </span>
                </div>

                <!-- Wind Direction -->
                <div class="flex items-center space-x-3 bg-slate-100 p-3 rounded-md">
                    <img src="/icons/compass.svg" class="w-5 h-5" alt="Wind Dir">
                    <div>
                        <strong>Wind Direction</strong><br>
                        <div class="flex items-center space-x-1">
                            <span x-text="compass(selectedSite.conditions?.windDirection?.noaa)"></span>
                            <img src="/icons/arrow.svg" class="w-4 h-4"
                                :style="`transform: rotate(${(selectedSite.conditions?.windDirection?.noaa || 0) + 90}deg)`"
                                alt="Arrow">
                        </div>
                    </div>
                </div>
                <!-- Updated -->
                <div class="flex items-center space-x-2 text-xs text-gray-500 sm:col-span-2">
                    <img src="/icons/update.svg" class="w-3 h-3" alt="Updated">
                    <span><strong>Updated:</strong> <span x-text="formatDate(selectedSite.retrieved_at)"></span></span>
                </div>
            </div>
        </div>

       <!-- 🌊 Swell Height Forecast Chart -->
       <div x-show="selectedSite.forecast && selectedSite.forecast.length" class="mt-8">
            <h3 class="text-lg font-semibold text-slate-700 mb-3">📈 Swell Height (Next 48 Hours)</h3>
            <div class="w-full h-64">
                <canvas id="swellChart"></canvas>
            </div>
            <div class="mt-2 text-xs text-gray-500 flex items-center gap-1">
                <img src="/icons/update.svg" class="w-3 h-3" alt="Updated">
                <span><strong>Updated:</strong> <span x-text="formatDate(selectedSite.forecast_updated_at)"></span></span>
            </div>
        </div>

    </div>
</template>