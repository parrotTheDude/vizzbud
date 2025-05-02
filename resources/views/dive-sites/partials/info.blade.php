<template x-if="selectedSite">
    <div class="bg-white text-black p-6 rounded-lg space-y-6 text-left">
        <!-- 🧭 Site Name & Description -->
        <div>
            <h2 class="text-2xl font-bold text-cyan-600" x-text="selectedSite.name"></h2>
            <p class="text-sm text-gray-700 mt-1" x-text="selectedSite.description"></p>
        </div>

        <!-- 📌 Dive Site Info -->
        <div>
            <h3 class="text-md font-semibold mb-2 text-cyan-600">Dive Site Info</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex items-center space-x-3">
                    <img src="/icons/pool-depth.svg" class="w-5 h-5" alt="Average Depth">
                    <span><strong>Avg Depth:</strong> <span x-text="`${selectedSite.avg_depth}m`"></span></span>
                </li>
                <li class="flex items-center space-x-3">
                    <img src="/icons/under-water.svg" class="w-5 h-5 opacity-70" alt="Max Depth">
                    <span><strong>Max Depth:</strong> <span x-text="`${selectedSite.max_depth}m`"></span></span>
                </li>
                <li class="flex items-center space-x-3">
                    <img src="/icons/boat.svg" class="w-5 h-5" alt="Entry">
                    <span><strong>Entry:</strong> <span x-text="selectedSite.dive_type ? selectedSite.dive_type.charAt(0).toUpperCase() + selectedSite.dive_type.slice(1) : '—'"></span></span>
                </li>
                <li class="flex items-center space-x-3">
                    <img src="/icons/diver.svg" class="w-5 h-5" alt="Level">
                    <span><strong>Level:</strong> <span x-text="selectedSite.suitability ?? '—'"></span></span>
                </li>
            </ul>
        </div>

        <!-- 🌊 Current Conditions -->
        <div>
            <h3 class="text-md font-semibold mb-2 text-cyan-600">Current Conditions</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex items-center space-x-3">
                    <img src="/icons/temperature.svg" class="w-5 h-5" alt="Water Temp">
                    <span><strong>Water Temp:</strong> <span x-text="selectedSite.conditions?.waterTemperature?.noaa ?? '—'"></span> °C</span>
                </li>
                <li class="flex items-center space-x-3">
                    <img src="/icons/wave.svg" class="w-5 h-5" alt="Wave">
                    <span>
                        <strong>Swell:</strong>
                        <span x-text="selectedSite.conditions?.waveHeight?.noaa ?? '—'"></span> m @
                        <span x-text="selectedSite.conditions?.wavePeriod?.noaa ?? '—'"></span> s
                    </span>
                </li>
                <li class="flex items-center space-x-3">
                    <img src="/icons/compass.svg" class="w-5 h-5" alt="Direction">
                    <span class="flex items-center space-x-2">
                        <strong>Swell Direction:</strong>
                        <span x-text="compass(selectedSite.conditions?.waveDirection?.noaa)"></span>
                        <img 
                            src="/icons/arrow.svg" 
                            class="w-4 h-4 transform" 
                            :style="`transform: rotate(${(selectedSite.conditions?.waveDirection?.noaa || 0) + 90}deg)`"
                            alt="Wave Direction Arrow"
                        />
                    </span>
                </li>
                <li class="flex items-center space-x-3">
                    <img src="/icons/wind.svg" class="w-5 h-5" alt="Wind">
                    <span class="flex items-center space-x-2">
                        <strong>Wind:</strong>
                        <span x-text="formatWind(selectedSite.conditions?.windSpeed?.noaa, selectedSite.conditions?.windDirection?.noaa)"></span>
                        <img 
                            src="/icons/arrow.svg" 
                            class="w-4 h-4 transform" 
                            :style="`transform: rotate(${(selectedSite.conditions?.windDirection?.noaa || 0) + 90}deg)`"
                            alt="Wind Direction Arrow"
                        />
                    </span>
                </li>
                <li class="flex items-center space-x-3">
                    <img src="/icons/temperature.svg" class="w-5 h-5" alt="Air Temp">
                    <span><strong>Air Temp:</strong> <span x-text="selectedSite.conditions?.airTemperature?.noaa ?? '—'"></span> °C</span>
                </li>
                <li class="flex items-center space-x-3 text-xs text-gray-500">
                    <img src="/icons/update.svg" class="w-4 h-4" alt="Updated">
                    <span><strong>Updated:</strong> <span x-text="formatDate(selectedSite.retrieved_at)"></span></span>
                </li>
            </ul>
        </div>
    </div>
</template>