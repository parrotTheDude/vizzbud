@extends('layouts.vizzbud')

@section('title', 'How Vizzbud Works — A Diver’s Guide')
@section('meta_description', 'Learn how to use Vizzbud, interpret the map rings and colors, and where our live marine data comes from.')

@section('content')
<section class="relative max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">

  {{-- ambient glow --}}
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[36rem] h-[36rem] rounded-full bg-cyan-500/10 blur-3xl"></div>
  </div>

  {{-- header --}}
  <header class="mb-10 sm:mb-14 text-center">
    <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight">How Vizzbud Works</h1>
    <p class="mt-3 text-white/70 max-w-2xl mx-auto">
      Understand conditions at a glance, log your dives, and plan safer adventures—powered by clean data and a simple map.
    </p>
  </header>

  {{-- 1) Quick Start (how to use) --}}
  <section class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-6 sm:p-8 mb-8">
    <h2 class="text-xl sm:text-2xl font-bold mb-2 inline-flex items-center gap-2">
       <span>Quick Start</span>
    </h2>
    <p class="text-white/70 mb-6">Three steps to get value fast.</p>

    <div class="grid md:grid-cols-3 gap-6">
      <div class="rounded-xl bg-white/5 border border-white/10 p-5">
        <h3 class="font-semibold">1) Explore Dive Sites</h3>
        <p class="text-white/70 text-sm mt-1">Open the map, search a site, or tap a marker. On desktop the info panel opens on the left; on mobile it slides up from the bottom.</p>
      </div>
      <div class="rounded-xl bg-white/5 border border-white/10 p-5">
        <h3 class="font-semibold">2) Check Live Conditions</h3>
        <p class="text-white/70 text-sm mt-1">See swell height, period, and direction—plus wind and temperature. The forecast chart shows the next 24 hours with a “now” line.</p>
      </div>
      <div class="rounded-xl bg-white/5 border border-white/10 p-5">
        <h3 class="font-semibold">3) Log Your Dives</h3>
        <p class="text-white/70 text-sm mt-1">After your dive, record depth, duration, visibility, and notes to build your history over time.</p>
      </div>
    </div>
  </section>

  {{-- 2) Interpreting the Map (rings + colors) — unified card --}}
  <section class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-6 sm:p-8 mb-8">
    <h2 class="text-xl sm:text-2xl font-bold mb-2 inline-flex items-center gap-2">
      <span>Interpreting the Map</span>
    </h2>
    <p class="text-white/70">Markers use a cyan dot with a colored ring that summarises conditions at a glance.</p>

    <div class="mt-6 grid md:grid-cols-2 gap-6">
      <div class="rounded-xl bg-white/5 border border-white/10 p-5">
      <h3 class="font-semibold">Rings & Meaning</h3>
      <p class="text-white/70 text-sm mt-1">
        Each dive site shows a cyan dot with a coloured ring representing overall conditions.
      </p>

      {{-- responsive cards (no overflow on shrink) --}}
      <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
        @php
          $items = [
            ['label' => 'Green',  'color' => '#22c55e', 'shadow' => 'shadow-cyan-500/10',   'hint' => 'Calm / ideal'],
            ['label' => 'Yellow', 'color' => '#eab308', 'shadow' => 'shadow-yellow-400/10', 'hint' => 'Manageable — check wind/swell'],
            ['label' => 'Red',    'color' => '#ef4444', 'shadow' => 'shadow-red-500/10',    'hint' => 'Rough / not recommended'],
          ];
        @endphp

        @foreach ($items as $it)
          <div class="flex flex-col items-center text-center
                      bg-white/5 border border-white/10 rounded-xl p-4 sm:p-5
                      transition-all duration-300 hover:bg-white/10 hover:shadow-lg {{ $it['shadow'] }}
                      overflow-hidden min-w-0">
            {{-- SVG scales with viewport; never overflows --}}
            <svg viewBox="0 0 44 44" aria-hidden="true"
                class="mb-3 mx-auto block"
                style="width:clamp(40px, 6vw, 56px); height:clamp(40px, 6vw, 56px);">
              <circle cx="22" cy="22" r="6" fill="#0e7490"></circle>
              <circle cx="22" cy="22" r="12" fill="none" stroke="{{ $it['color'] }}" stroke-width="4"></circle>
            </svg>

            <div class="font-semibold">{{ $it['label'] }}</div>
            <div class="text-xs text-white/70 mt-1">{{ $it['hint'] }}</div>
          </div>
        @endforeach
      </div>

      <p class="mt-4 text-xs text-white/50 text-center sm:text-left">
        Status is based on swell height and wind speed forecasts. Local exposure still matters.
      </p>
    </div>

      {{-- Color thresholds --}}
      <div class="rounded-xl bg-white/5 border border-white/10 p-5">
        <h3 class="font-semibold">How Colours Are Chosen</h3>
        <p class="text-white/70 text-sm mt-1">We blend wave height and wind into a simple status.</p>

        <div class="mt-3 overflow-hidden rounded-lg border border-white/10">
          <table class="w-full text-sm">
            <thead class="bg-white/5 text-white/80">
              <tr>
                <th class="text-left p-2">Status</th>
                <th class="text-left p-2">Swell Height</th>
                <th class="text-left p-2">Wind Speed</th>
                <th class="text-left p-2">Guidance</th>
              </tr>
            </thead>
            <tbody>
              <tr class="border-t border-white/10">
                <td class="p-2"><span class="inline-flex items-center gap-2"><i class="w-2 h-2 rounded-full" style="background:#22c55e"></i> Green</span></td>
                <td class="p-2">&lt; 0.6 m</td>
                <td class="p-2">&lt; 12 kn</td>
                <td class="p-2">Great for most divers</td>
              </tr>
              <tr class="border-t border-white/10">
                <td class="p-2"><span class="inline-flex items-center gap-2"><i class="w-2 h-2 rounded-full" style="background:#eab308"></i> Yellow</span></td>
                <td class="p-2">&lt; 1.0 m</td>
                <td class="p-2">&lt; 18 kn</td>
                <td class="p-2">Okay — check local exposure</td>
              </tr>
              <tr class="border-t border-white/10">
                <td class="p-2"><span class="inline-flex items-center gap-2"><i class="w-2 h-2 rounded-full" style="background:#ef4444"></i> Red</span></td>
                <td class="p-2">≥ 1.0 m</td>
                <td class="p-2">≥ 18 kn</td>
                <td class="p-2">Challenging — likely not ideal</td>
              </tr>
            </tbody>
          </table>
        </div>

        <p class="text-xs text-white/50 mt-3">
          These are sensible defaults; exposure, currents, and local knowledge still matter.
        </p>
      </div>
    </div>
  </section>

  {{-- 3) Forecasts & Charts — simplified copy --}}
  <section class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-6 sm:p-8 mb-8">
    <h2 class="text-xl sm:text-2xl font-bold mb-2 inline-flex items-center gap-2">
      <span>Forecasts & Charts</span>
    </h2>

    <div class="grid md:grid-cols-2 gap-6">
      <div class="rounded-xl bg-white/5 border border-white/10 p-5">
        <h3 class="font-semibold">What you’ll see</h3>
        <ul class="mt-2 space-y-1 text-white/80 text-sm leading-6">
          <li>• <strong>Swell height</strong> (m) and <strong>period</strong> (s) over time</li>
          <li>• <strong>Direction arrows</strong> along the timeline</li>
          <li>• A fixed <strong>“Now” line</strong> two hours into the 24-hour window</li>
        </ul>
      </div>

      <div class="rounded-xl bg-white/5 border border-white/10 p-5">
        <h3 class="font-semibold">Quick tips</h3>
        <ul class="mt-2 space-y-1 text-white/80 text-sm leading-6">
          <li>• Taller lines = bigger swell; longer period can mean more power</li>
          <li>• Arrows show where the swell is coming from</li>
          <li>• Use site exposure and local knowledge to interpret the numbers</li>
        </ul>
      </div>
    </div>
  </section>

  {{-- 4) Data Sources --}}
  <section class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-6 sm:p-8 mb-8">
    <h2 class="text-xl sm:text-2xl font-bold mb-2 inline-flex items-center gap-2">
      <span>Data Sources</span>
    </h2>
    <div class="grid sm:grid-cols-2 gap-6">
      <div class="rounded-xl bg-white/5 border border-white/10 p-5">
        <h3 class="font-semibold">Marine & Weather</h3>
        <ul class="mt-2 space-y-1 text-white/80 text-sm leading-6">
          <li>• Open-Meteo Marine (swell height/period/direction, wind, temps)</li>
          <li>• Tide harmonics and local tide feeds (roadmap)</li>
        </ul>
        <p class="text-xs text-white/50 mt-3">We respect provider rate limits and cache responses per site/hour.</p>
      </div>
      <div class="rounded-xl bg-white/5 border border-white/10 p-5">
        <h3 class="font-semibold">Sites & Maps</h3>
        <ul class="mt-2 space-y-1 text-white/80 text-sm leading-6">
          <li>• Curated site details (type, suitability, notes)</li>
          <li>• Mapbox GL vector tiles for smooth interaction</li>
        </ul>
        <p class="text-xs text-white/50 mt-3">Status rings are computed server-side and re-evaluated as forecasts update.</p>
      </div>
    </div>
  </section>

  {{-- 5) FAQ --}}
  <section class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-6 sm:p-8">
    <h2 class="text-xl sm:text-2xl font-bold mb-4 inline-flex items-center gap-2">
      <span>FAQ</span>
    </h2>

    <div class="space-y-6">
      <details class="group rounded-xl bg-white/5 border border-white/10 ring-1 ring-white/10 p-4">
        <summary class="cursor-pointer font-semibold">
          Where do your forecasts come from?
        </summary>
        <p class="mt-2 text-white/70 text-sm">
          We fetch marine forecasts from Open-Meteo and update them hourly.
        </p>
      </details>

      <details class="group rounded-xl bg-white/5 border border-white/10 ring-1 ring-white/10 p-4">
        <summary class="cursor-pointer font-semibold">
          How often are conditions updated?
        </summary>
        <p class="mt-2 text-white/70 text-sm">
          Hourly. We also remove expired forecast rows to keep things lean.
        </p>
      </details>

      <details class="group rounded-xl bg-white/5 border border-white/10 ring-1 ring-white/10 p-4">
        <summary class="cursor-pointer font-semibold">
          Why does my site show red when it looks calm?
        </summary>
        <p class="mt-2 text-white/70 text-sm">
          Color reflects modelled swell + wind thresholds. Local exposure (headlands, bays) can differ—use Vizzbud as guidance, and pair it with local knowledge.
        </p>
      </details>
    </div>
  </section>

   {{-- CTA / Final Section --}}
  <section class="mt-12 text-center">
    <div class="max-w-3xl mx-auto rounded-2xl border border-white/10 ring-1 ring-white/10 bg-gradient-to-b from-white/10 to-white/5 backdrop-blur-xl shadow-xl p-10 sm:p-12">
      <h2 class="text-2xl sm:text-3xl font-extrabold mb-4">Ready to Dive Smarter?</h2>
      <p class="text-white/70 mb-8 text-base sm:text-lg leading-relaxed">
        Vizzbud helps divers make confident decisions before every dive —
        from live marine forecasts to personal logbook insights.
        It’s fast, private, and built by divers for divers.
      </p>

      <div class="flex flex-col sm:flex-row justify-center gap-4">
        <a href="{{ route('dive-sites.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3 font-semibold text-white
                  bg-gradient-to-r from-cyan-500/90 to-teal-400/90
                  hover:from-cyan-400/90 hover:to-teal-300/90
                  border border-white/10 ring-1 ring-white/10
                  backdrop-blur-md shadow-lg shadow-cyan-500/20
                  transition-all duration-300 hover:-translate-y-0.5">
          Explore Dive Sites
        </a>

        <a href="{{ route('register') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3 font-semibold text-white
                  bg-white/10 hover:bg-white/20 border border-white/10 ring-1 ring-white/10
                  backdrop-blur-md shadow-sm transition-all duration-300 hover:-translate-y-0.5">
          Create a Free Account
        </a>
      </div>

      <p class="mt-8 text-sm text-white/60">
        Built with ❤️ by divers. Data powered by <strong>Open-Meteo</strong> & <strong>Mapbox</strong>.
      </p>
    </div>
  </section>

</section>
@endsection