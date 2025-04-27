<div x-show="selectedSite" x-cloak>
    <div class="mb-4">
        <h2 class="text-xl font-semibold text-cyan-600" x-text="selectedSite.name"></h2>
        <p class="text-sm mt-1" x-text="selectedSite.description"></p>
    </div>
    <ul class="space-y-1 text-sm">
        <li>📏 <strong>Depth:</strong> <span x-text="`${selectedSite.avg_depth}m avg / ${selectedSite.max_depth}m max`"></span></li>
        <li>🚶 <strong>Entry:</strong> <span x-text="selectedSite.dive_type ?? '—'"></span></li>
        <li>🎓 <strong>Level:</strong> <span x-text="selectedSite.suitability ?? '—'"></span></li>
        <li>🌡️ <strong>Water Temp:</strong> <span x-text="selectedSite.conditions?.waterTemperature?.noaa ?? '—'"></span> °C</li>
        <li>🌊 <strong>Wave:</strong> <span x-text="selectedSite.conditions?.waveHeight?.noaa ?? '—'"></span> m @
            <span x-text="selectedSite.conditions?.wavePeriod?.noaa ?? '—'"></span> s</li>
        <li>🧭 <strong>Direction:</strong> <span x-text="compass(selectedSite.conditions?.waveDirection?.noaa)"></span></li>
        <li>🌬️ <strong>Wind:</strong> <span x-text="formatWind(selectedSite.conditions?.windSpeed?.noaa, selectedSite.conditions?.windDirection?.noaa)"></span></li>
        <li>📅 <strong>Updated:</strong> <span x-text="formatDate(selectedSite.retrieved_at)"></span></li>
    </ul>
</div>